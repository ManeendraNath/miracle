<?php

declare(strict_types=1);

namespace frontend\components;

use common\models\CustomerBillingProfile;
use common\models\Invoice;
use common\models\User;
use common\services\customer\IncompleteProfileReminderService;
use Yii;
use yii\base\ActionEvent;

/**
 * Billing details first: until they are complete a signed-in customer sees only the form, change password, support
 * and logout. A customer with something live (service, open invoice, held payment) must still reach it, so gets a
 * banner instead (account layout, 'profileBanner'). So does anyone with an invoice looking at invoices and payments:
 * paying the last open one must not lose them the receipt. An open ticket doesn't count: anyone can open one.
 */
final class ProfileGate
{
    /** Public pages, including logout. */
    private const OPEN = ['site', 'webhook'];
    private const OPEN_ROUTES = ['account/profile', 'account/change-password'];

    public static function check(ActionEvent $event): void
    {
        $identity = Yii::$app->user->identity;
        $controller = $event->action->controller->id;
        if (!$identity instanceof User || in_array($controller, self::OPEN, true) || in_array($event->action->getUniqueId(), self::OPEN_ROUTES, true) || self::isComplete($identity)) {
            return;
        }
        Yii::$app->view->params['profileBanner'] = true;
        $hasInvoices = in_array($controller, ['invoice', 'payment'], true) && Invoice::forCustomer()->andWhere(['user_id' => (int) $identity->id])->exists();
        if ($controller !== 'support' && !$hasInvoices && !IncompleteProfileReminderService::isInUse((int) $identity->id, false)) {
            Yii::$app->session->setFlash('warning', 'Please complete your billing details to continue.');
            Yii::$app->response->redirect(['/account/profile']);
            $event->isValid = false;
        }
    }

    /** Where to go straight after login: the home page has no gate. */
    public static function mustCompleteFirst(User $user): bool
    {
        return !self::isComplete($user) && !IncompleteProfileReminderService::isInUse((int) $user->id, false);
    }

    private static function isComplete(User $user): bool
    {
        return IncompleteProfileReminderService::missingFields(CustomerBillingProfile::findOne(['user_id' => (int) $user->id])) === [];
    }
}
