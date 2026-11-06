<?php

declare(strict_types=1);

namespace frontend\components;

use common\models\CustomerBillingProfile;
use common\models\User;
use common\services\customer\IncompleteProfileReminderService;
use Yii;
use yii\base\ActionEvent;

/**
 * Billing details first: until they are complete a signed-in customer sees only the form, support and logout.
 * A customer with something live (service, open invoice, open ticket, held payment) must still reach it,
 * so gets a banner instead (account layout, 'profileBanner').
 */
final class ProfileGate
{
    /** Public pages, including logout. */
    private const OPEN = ['site', 'webhook'];

    public static function check(ActionEvent $event): void
    {
        $identity = Yii::$app->user->identity;
        $controller = $event->action->controller->id;
        if (!$identity instanceof User || in_array($controller, self::OPEN, true) || $event->action->getUniqueId() === 'account/profile' || self::isComplete($identity)) {
            return;
        }
        Yii::$app->view->params['profileBanner'] = true;
        if ($controller !== 'support' && !IncompleteProfileReminderService::isInUse((int) $identity->id)) {
            Yii::$app->session->setFlash('warning', 'Please complete your billing details to continue.');
            Yii::$app->response->redirect(['/account/profile']);
            $event->isValid = false;
        }
    }

    /** Where to go straight after login: the home page has no gate. */
    public static function mustCompleteFirst(User $user): bool
    {
        return !self::isComplete($user) && !IncompleteProfileReminderService::isInUse((int) $user->id);
    }

    private static function isComplete(User $user): bool
    {
        return IncompleteProfileReminderService::missingFields(CustomerBillingProfile::findOne(['user_id' => (int) $user->id])) === [];
    }
}
