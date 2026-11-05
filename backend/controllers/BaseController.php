<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;

class BaseController extends Controller
{
    /**
     * Lowest role allowed per route ("controller/action") or controller id. Anything not
     * listed is open to every staff role (Manager and up).
     *   Manager: day-to-day operations (customers, services, support, leads, renewals)
     *   Admin: + billing and money (invoices, payments, coupons, billing profiles), login history, cron log
     *   Superadmin: + administrator accounts and site settings
     */
    public const MIN_ROLE = [
        'admin/account' => 'Manager',
        'admin/cron-log' => 'Admin',
        'admin' => 'Superadmin',
        'site-setting' => 'Superadmin',
        'invoice' => 'Admin',
        'transaction' => 'Admin',
        'coupon' => 'Admin',
        'customer-billing-profile' => 'Admin',
        'user-login-history' => 'Admin',
        'report' => 'Admin',
        'razorpay-test' => 'Admin',
        // Setting a customer's password or email lets staff sign in as them and see billing hidden from Managers.
        'user/credentials' => 'Admin',
        // Managers can see leads; changing or deleting them is Admin work.
        'lead/update' => 'Admin',
        'lead/delete' => 'Admin',
        // Same for audit requests (customer-submitted enquiries).
        'audit-request/create' => 'Admin',
        'audit-request/update' => 'Admin',
        'audit-request/delete' => 'Admin',
        'hosting/cpanel-password' => 'Admin',
    ];

    private const ROLE_RANK = ['Manager' => 1, 'Admin' => 2, 'Superadmin' => 3];

    /** Service fields that set what renewal invoices charge: Admin and up only. */
    public const RENEWAL_PRICING_ATTRIBUTES = ['renewal_base_amount', 'next_renewal_amount', 'renewal_increase_percent', 'renewal_cycle_months'];

    /** A request value as text: a crafted array (x[]=1) reads as empty instead of a 500. */
    protected static function scalarParam(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /** Whether the signed-in staff member may set renewal pricing on services. */
    public static function canEditRenewalPricing(): bool
    {
        return self::canAccess(Yii::$app->user->identity, 'invoice');
    }

    /**
     * $model->load() for the domain/hosting/maintenance forms, ignoring posted renewal
     * pricing from roles below Admin (the form shows it read-only to them).
     */
    protected function loadService(\common\models\Domains|\common\models\Hosting|\common\models\Maintenance $model): bool
    {
        $kept = [];
        foreach (self::RENEWAL_PRICING_ATTRIBUTES as $attribute) {
            $kept[$attribute] = $model->$attribute;
        }
        if (!$model->load($this->request->post())) {
            return false;
        }
        if (!self::canEditRenewalPricing()) {
            $model->setAttributes($kept, false);
        }
        $expiryChanged = $model->isAttributeChanged('current_expiry_date', false);
        $customerChanged = $model->isAttributeChanged('user_id', false);
        // 0 = Inactive / Cancelled for all three; a domain can also be marked Deleted. Suspended (hosting) and
        // Overdue (maintenance) are about non-payment: the renewal is still owed.
        // A blank status ("Select Status") is not a disable: it saves as the default, Active.
        $status = $model->getAttribute('status');
        $disabled = ($status !== '' && (int) $status === 0) || ($model instanceof \common\models\Domains && (int) $model->status === \common\models\Domains::STATUS_DELETED);
        $disabling = $disabled && $model->isAttributeChanged('status', false);
        $locked = false;
        if (!$model->getIsNewRecord() && ($expiryChanged || $customerChanged || $disabling)) {
            if (!$this->lockBillingRun()) {
                $model->addError($expiryChanged ? 'current_expiry_date' : ($customerChanged ? 'user_id' : 'status'), 'The billing run is in progress. Save again in a minute.');
                return false;
            }
            $locked = true;
            if (($expiryChanged || $customerChanged) && !$this->guardOpenRenewal($model, $expiryChanged, $customerChanged)) {
                $this->unlockBillingRun();
                return false;
            }
        }
        // Disabled from the edit form (as with the toggle): the service is not renewed.
        if (!$model->getIsNewRecord() && $disabling) {
            $model->on(\yii\db\ActiveRecord::EVENT_AFTER_UPDATE, function () use ($model): void {
                $this->releaseDisabledService($model);
            });
        }
        // Staff set the expiry: that day becomes the renewal day (renewals themselves keep it).
        if ($expiryChanged && (string) $model->current_expiry_date !== '') {
            $model->renewal_anchor_day = \common\services\billing\RenewalPeriodCalculator::anchorDayFor((string) $model->current_expiry_date);
        }
        // Handlers run in order, so this comes after the ones above. A save that fails validation
        // releases at the end of the request (Mutex::$autoRelease).
        if ($locked) {
            $model->on(\yii\db\ActiveRecord::EVENT_AFTER_UPDATE, function (): void {
                $this->unlockBillingRun();
            });
        }
        return true;
    }

    /**
     * Releases a disabled service's renewal. Invoices are Admin work: below Admin they are only reported,
     * to an Admin as well since the Manager can't open them.
     */
    protected function releaseDisabledService(\common\models\Domains|\common\models\Hosting|\common\models\Maintenance $model): void
    {
        [$type, $service] = match (true) {
            $model instanceof \common\models\Domains => [\common\models\InvoiceItem::TYPE_DOMAIN, (string) $model->domain_url],
            $model instanceof \common\models\Hosting => [\common\models\InvoiceItem::TYPE_HOSTING, (string) $model->primary_domain],
            default => [\common\models\InvoiceItem::TYPE_MAINTENANCE, (string) $model->website_url],
        };
        $canCancel = self::canEditRenewalPricing();
        $warning = (new \common\services\billing\RenewalReleaseService())->releaseForDisabledService($type, (int) $model->id, $canCancel);
        if ($warning === null) {
            return;
        }
        if (!$canCancel) {
            try {
                \common\services\admin\AdminAlertService::renewalLeftOpen($service, (string) Yii::$app->user->identity->username, $warning);
            } catch (\Throwable $e) {
                Yii::error($e, __METHOD__);
            }
            $warning .= ' An administrator has been told.';
        }
        Yii::$app->session->setFlash('warning', $warning);
    }

    /**
     * Held from checking a service until its change is saved, so a billing run can't invoice the old
     * expiry or customer in between, and can't read them halfway. False while a run is in progress.
     */
    protected function lockBillingRun(): bool
    {
        return Yii::$app->get('mutex')->acquire(\common\services\billing\AutoBillingService::LOCK_NAME, 0);
    }

    protected function unlockBillingRun(): void
    {
        Yii::$app->get('mutex')->release(\common\services\billing\AutoBillingService::LOCK_NAME);
    }

    /**
     * A renewal on an open invoice was priced and dated from the current expiry and customer: changing either
     * would bill an overlapping second period (or the wrong customer), so it is refused until that invoice is
     * paid or cancelled. Once its balance was carried onto a newer invoice the expiry may change (the next run
     * won't bill a period overlapping it), but the customer can't until that balance is paid: only the customer
     * who owes it can pay it. An expiry before the end of a renewal already paid is refused (that period would
     * never be billed again), unless the money went back: a fully refunded renewal is released instead.
     * A renewal not invoiced yet is released after the save; the next run re-dates it.
     */
    private function guardOpenRenewal(\common\models\Domains|\common\models\Hosting|\common\models\Maintenance $model, bool $expiryChanged, bool $customerChanged): bool
    {
        $type = match (true) {
            $model instanceof \common\models\Domains => \common\models\InvoiceItem::TYPE_DOMAIN,
            $model instanceof \common\models\Hosting => \common\models\InvoiceItem::TYPE_HOSTING,
            default => \common\models\InvoiceItem::TYPE_MAINTENANCE,
        };
        $where = ['item_type' => $type, 'item_id' => (int) $model->id];
        $billedOn = static fn(array $statuses): \yii\db\ActiveQuery => \common\models\Invoice::find()->alias('i')
            ->innerJoin(['e' => \common\models\BillingEvent::tableName()], 'e.invoice_id = i.id')
            ->where(['e.item_type' => $type, 'e.item_id' => (int) $model->id, 'e.status' => \common\models\BillingEvent::STATUS_INVOICED])
            ->andWhere(['i.status' => $statuses]);
        $open = $billedOn([\common\models\Invoice::STATUS_UNPAID, \common\models\Invoice::STATUS_PARTIALLY_PAID])->one();
        $attribute = $expiryChanged ? 'current_expiry_date' : 'user_id';
        if ($open !== null) {
            $model->addError($attribute, 'Renewal invoice ' . $open->invoice_number . ' is open for this service. It must be paid or cancelled before the expiry date or customer can change.');
            return false;
        }
        $carried = $billedOn([\common\models\Invoice::STATUS_CARRIED_FORWARD])->one();
        if ($carried !== null && $customerChanged) {
            $newer = (new \common\services\billing\InvoicePaymentService())->openInvoiceCarrying((int) $carried->id);
            $model->addError('user_id', 'This renewal\'s balance is owed on invoice ' . ($newer->invoice_number ?? $carried->invoice_number) . ' by the current customer. It must be paid before the customer can change.');
            return false;
        }
        if ($expiryChanged) {
            $refunded = (new \common\services\billing\RenewalFulfilmentService())->refundedEventIds($type, (int) $model->id);
            $paidUntil = \common\models\BillingEvent::find()->select('MAX(period_end)')
                ->where($where + ['status' => \common\models\BillingEvent::STATUS_FULFILLED])->andWhere(['not in', 'id', $refunded])->scalar();
            if (is_string($paidUntil) && (string) $model->current_expiry_date < $paidUntil) {
                $model->addError($attribute, 'A paid renewal runs until ' . Yii::$app->formatter->asDate($paidUntil, 'php:d M Y') . '. The expiry date cannot be earlier.');
                return false;
            }
            // Every renewal still Invoiced here is a carried one: open invoices were refused above.
            $carriedEnd = \common\models\BillingEvent::find()->select('MAX(period_end)')->where($where + ['status' => \common\models\BillingEvent::STATUS_INVOICED])->scalar();
            // Only once the change is saved: an edit that fails validation must not release anything.
            $model->on(\yii\db\ActiveRecord::EVENT_AFTER_UPDATE, static function () use ($where, $model, $refunded, $carriedEnd): void {
                \common\models\BillingEvent::updateAll(
                    ['status' => \common\models\BillingEvent::STATUS_CANCELLED, 'notes' => 'Expiry date changed by staff; the next billing run re-dates the renewal.', 'updated_at' => time()],
                    $where + ['status' => \common\models\BillingEvent::STATUS_PENDING, 'invoice_id' => null]
                );
                // The period of a refunded renewal is billable again once the expiry is back before its end.
                \common\models\BillingEvent::updateAll(
                    ['status' => \common\models\BillingEvent::STATUS_CANCELLED, 'notes' => 'Renewal refunded; expiry set back by staff.', 'updated_at' => time()],
                    ['and', ['id' => $refunded], ['>', 'period_end', (string) $model->current_expiry_date]]
                );
                if (is_string($carriedEnd) && (string) $model->current_expiry_date < $carriedEnd) {
                    Yii::$app->session->setFlash('warning', 'A carried-forward renewal runs until ' . Yii::$app->formatter->asDate($carriedEnd, 'php:d M Y') . '. When its balance is paid, the expiry moves to that date.');
                }
            });
        }
        return true;
    }

    /** Whether $user (an Admin identity) may open the route; also used to hide menu links. */
    public static function canAccess(?object $user, string $controllerId, string $actionId = 'index'): bool
    {
        $rank = self::ROLE_RANK[$user->role ?? ''] ?? 0;
        $required = self::MIN_ROLE["{$controllerId}/{$actionId}"] ?? self::MIN_ROLE[$controllerId] ?? 'Manager';
        return $rank > 0 && $rank >= self::ROLE_RANK[$required];
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function ($rule, $action) {
                    if (Yii::$app->user->isGuest) {
                        return Yii::$app->response->redirect(['/auth/login']);
                    }
                    if (!isset(self::ROLE_RANK[Yii::$app->user->identity->role ?? ''])) {
                        // Not a staff role at all.
                        Yii::$app->user->logout();
                        Yii::$app->session->setFlash('error', 'Your account has no administrator role.');
                        return Yii::$app->response->redirect(['/auth/login']);
                    }
                    throw new ForbiddenHttpException('Your role does not have access to this section.');
                },
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static fn($rule, $action): bool => self::canAccess(Yii::$app->user->identity, $action->controller->id, $action->id),
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'approve' => ['POST'],
                    'reject' => ['POST'],
                    'refund' => ['POST'],
                    'apply' => ['POST'],
                    'cancel' => ['POST'],
                    'close' => ['POST'],
                    'resend-invoice' => ['POST'],
                    'toggle-status' => ['POST'],
                    'cpanel-password' => ['POST'],
                    'verify' => ['POST'],
                ],
            ],
        ];
    }
}
