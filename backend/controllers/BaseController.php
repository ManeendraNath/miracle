<?php

namespace backend\controllers;

use backend\models\Admin;
use common\models\{
    BillingEvent,
    Domains,
    Hosting,
    Invoice,
    InvoiceItem,
    Maintenance
};
use common\services\{
    admin\AdminAlertService,
    billing\AutoBillingService,
    billing\InvoicePaymentService,
    billing\RenewalFulfilmentService,
    billing\RenewalPeriodCalculator,
    billing\RenewalReleaseService
};
use Yii;
use yii\db\ActiveRecord;
use yii\web\Controller;
use yii\web\ErrorAction;
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
        'customer-preference' => 'Admin',
        // Right after creating a customer; below Admin it only takes what the customer could enter themselves.
        'customer-billing-profile/create' => 'Manager',
        'user-login-history' => 'Admin',
        'report' => 'Admin',
        'razorpay-test' => 'Admin',
        // Setting a customer's password or email lets staff sign in as them and see billing hidden from Managers.
        'user/credentials' => 'Admin',
        // One email to every customer is a big step: billing staff and up.
        'user/email-many' => 'Admin',
        // Managers can see leads; changing or deleting them is Admin work.
        'lead/update' => 'Admin',
        'lead/delete' => 'Admin',
        // Same for audit requests (customer-submitted enquiries).
        'audit-request/create' => 'Admin',
        'audit-request/update' => 'Admin',
        'audit-request/delete' => 'Admin',
        'hosting/cpanel-password' => 'Admin',
        // Public website content.
        'testimonial' => 'Admin',
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
    protected function loadService(Domains|Hosting|Maintenance $model): bool
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
        if ($model instanceof Hosting && !self::canAccess(Yii::$app->user->identity, 'hosting', 'cpanel-password')) {
            $model->new_cpanel_password = null;
        }
        $expiryChanged = $model->isAttributeChanged('current_expiry_date', false);
        $customerChanged = $model->isAttributeChanged('user_id', false);
        // 0 = Inactive / Cancelled for all three; a domain can also be marked Deleted. Suspended (hosting) and
        // Overdue (maintenance) are about non-payment: the renewal is still owed.
        // A blank status ("Select Status") is not a disable: it saves as the default, Active.
        $status = $model->getAttribute('status');
        $disabled = ($status !== '' && (int) $status === 0) || ($model instanceof Domains && (int) $model->status === Domains::STATUS_DELETED);
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
            $model->on(ActiveRecord::EVENT_AFTER_UPDATE, function () use ($model): void {
                $this->releaseDisabledService($model);
            });
        }
        // Staff set the expiry: that day becomes the renewal day (renewals themselves keep it).
        if ($expiryChanged && (string) $model->current_expiry_date !== '') {
            $model->renewal_anchor_day = RenewalPeriodCalculator::anchorDayFor((string) $model->current_expiry_date);
        }
        // Handlers run in order, so this comes after the ones above. A save that fails validation
        // releases at the end of the request (Mutex::$autoRelease).
        if ($locked) {
            $model->on(ActiveRecord::EVENT_AFTER_UPDATE, function (): void {
                $this->unlockBillingRun();
            });
        }
        return true;
    }

    /**
     * Releases a disabled service's renewal. Invoices are Admin work: below Admin they are only reported,
     * to an Admin as well since the Manager can't open them.
     */
    protected function releaseDisabledService(Domains|Hosting|Maintenance $model): void
    {
        [$type, $service] = match (true) {
            $model instanceof Domains => [InvoiceItem::TYPE_DOMAIN, (string) $model->domain_url],
            $model instanceof Hosting => [InvoiceItem::TYPE_HOSTING, (string) $model->primary_domain],
            default => [InvoiceItem::TYPE_MAINTENANCE, (string) $model->website_url],
        };
        $canCancel = self::canEditRenewalPricing();
        $warning = (new RenewalReleaseService())->releaseForDisabledService($type, (int) $model->id, $canCancel);
        if ($warning === null) {
            return;
        }
        if (!$canCancel) {
            try {
                AdminAlertService::renewalLeftOpen($service, (string) Yii::$app->user->identity->username, $warning);
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
        return Yii::$app->get('mutex')->acquire(AutoBillingService::LOCK_NAME, 0);
    }

    protected function unlockBillingRun(): void
    {
        Yii::$app->get('mutex')->release(AutoBillingService::LOCK_NAME);
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
    private function guardOpenRenewal(Domains|Hosting|Maintenance $model, bool $expiryChanged, bool $customerChanged): bool
    {
        $type = match (true) {
            $model instanceof Domains => InvoiceItem::TYPE_DOMAIN,
            $model instanceof Hosting => InvoiceItem::TYPE_HOSTING,
            default => InvoiceItem::TYPE_MAINTENANCE,
        };
        $where = ['item_type' => $type, 'item_id' => (int) $model->id];
        // Moving a service to another customer: a renewal already granted is still owed on an open invoice.
        $invoiced = [BillingEvent::STATUS_INVOICED];
        $fulfilled = [BillingEvent::STATUS_FULFILLED];
        $billedOn = static fn(array $statuses, array $eventStatuses): \yii\db\ActiveQuery => Invoice::find()->alias('i')
            ->innerJoin(['e' => BillingEvent::tableName()], 'e.invoice_id = i.id')
            ->where(['e.item_type' => $type, 'e.item_id' => (int) $model->id, 'e.status' => $eventStatuses])
            ->andWhere(['i.status' => $statuses]);
        // A held (Draft) invoice counts: once sent and paid it would renew whichever customer has the service then.
        $open = $billedOn([Invoice::STATUS_DRAFT, Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], $customerChanged ? [...$invoiced, ...$fulfilled] : $invoiced)->one();
        $attribute = $expiryChanged ? 'current_expiry_date' : 'user_id';
        if ($open !== null) {
            $model->addError($attribute, 'Renewal invoice ' . $open->reference() . ' is open for this service. It must be ' . ($open->status === Invoice::STATUS_DRAFT ? 'sent and paid' : 'paid') . ' or cancelled before the expiry date or customer can change.');
            return false;
        }
        $payments = new InvoicePaymentService();
        $carried = $billedOn([Invoice::STATUS_CARRIED_FORWARD], $invoiced)->one();
        // A granted renewal on a carried invoice stays Carried Forward once paid: only owed if the newer invoice is open again.
        if ($carried === null && $customerChanged) {
            foreach ($billedOn([Invoice::STATUS_CARRIED_FORWARD], $fulfilled)->all() as $paid) {
                if ($payments->openInvoiceCarrying((int) $paid->id) !== null) {
                    $carried = $paid;
                    break;
                }
            }
        }
        if ($carried !== null && $customerChanged) {
            $newer = $payments->openInvoiceCarrying((int) $carried->id);
            $model->addError('user_id', 'This renewal\'s balance is owed on invoice ' . ($newer?->reference() ?? $carried->reference()) . ' by the current customer. It must be paid before the customer can change.');
            return false;
        }
        if ($expiryChanged) {
            $refunded = (new RenewalFulfilmentService())->refundedEventIds($type, (int) $model->id);
            $paidUntil = BillingEvent::find()->select('MAX(period_end)')
                ->where($where + ['status' => BillingEvent::STATUS_FULFILLED])->andWhere(['not in', 'id', $refunded])->scalar();
            if (is_string($paidUntil) && (string) $model->current_expiry_date < $paidUntil) {
                $model->addError($attribute, 'A paid renewal runs until ' . Yii::$app->formatter->asDate($paidUntil, 'php:d M Y') . '. The expiry date cannot be earlier.');
                return false;
            }
            // Every renewal still Invoiced here is a carried one: open invoices were refused above.
            $carriedEnd = BillingEvent::find()->select('MAX(period_end)')->where($where + ['status' => BillingEvent::STATUS_INVOICED])->scalar();
            // Only once the change is saved: an edit that fails validation must not release anything.
            $model->on(ActiveRecord::EVENT_AFTER_UPDATE, static function () use ($where, $model, $refunded, $carriedEnd): void {
                BillingEvent::updateAll(
                    ['status' => BillingEvent::STATUS_CANCELLED, 'notes' => 'Expiry date changed by staff; the next billing run re-dates the renewal.', 'updated_at' => time()],
                    $where + ['status' => BillingEvent::STATUS_PENDING, 'invoice_id' => null]
                );
                // The period of a refunded renewal is billable again once the expiry is back before its end.
                $released = ['and', ['id' => $refunded, 'status' => BillingEvent::STATUS_FULFILLED], ['>', 'period_end', (string) $model->current_expiry_date]];
                // Its one-time price was cleared when it was paid, and still applies to the re-billed renewal: the next one billed is the earliest.
                $next = BillingEvent::find()->where($released)->orderBy(['period_end' => SORT_ASC])->one();
                if ($next !== null && (int) $next->is_manual_amount === 1 && $model->next_renewal_amount === null) {
                    $model->updateAttributes(['next_renewal_amount' => $next->amount]);
                }
                BillingEvent::updateAll(
                    ['status' => BillingEvent::STATUS_CANCELLED, 'notes' => 'Renewal refunded; expiry set back by staff.', 'updated_at' => time()],
                    $released
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

    /** Other tests sign in without a code; TwoFactorCest turns this on. */
    public static bool $requireTwoFactor = !YII_ENV_TEST;

    /** Every staff account sees customer data: until two-factor is on it can only reach My account to set it up. */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $user = Yii::$app->user->identity;
        $required = self::$requireTwoFactor
            && !Admin::twoFactorPaused()
            && $user instanceof Admin
            && !$user->hasTwoFactor()
            && Admin::canStoreTwoFactor();
        if (!$required || $action->uniqueId === 'admin/account' || $action instanceof ErrorAction) {
            return true;
        }
        Yii::$app->session->setFlash('warning', 'Please turn on two-factor sign-in to continue. It is required for every staff account.');
        $this->redirect(['/admin/account']);

        return false;
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
                    'release' => ['POST'],
                    'toggle-status' => ['POST'],
                    'cpanel-password' => ['POST'],
                    'verify' => ['POST'],
                ],
            ],
        ];
    }
}
