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
    ];

    private const ROLE_RANK = ['Manager' => 1, 'Admin' => 2, 'Superadmin' => 3];

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
                    'cancel' => ['POST'],
                    'resend-invoice' => ['POST'],
                    'toggle-status' => ['POST'],
                ],
            ],
        ];
    }
}
