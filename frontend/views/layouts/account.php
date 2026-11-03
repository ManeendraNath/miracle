<?php

use common\models\CustomerNotification;
use common\models\SupportTicket;

/** @var yii\web\View $this */
/** @var string $content */

// Sidebar badges on every account page (a layout doesn't receive the page's variables).
$userId = (int) Yii::$app->user->id;
$unreadNotifications = (int) CustomerNotification::find()->where(['user_id' => $userId, 'is_read' => 0])->count();
$openSupportTickets = (int) SupportTicket::find()->where(['user_id' => $userId, 'status' => [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS]])->count();

$this->beginContent('@frontend/views/layouts/main.php');
?>

<div class="account-layout container pb-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?=
            $this->render(
                '_account_sidebar',
                [
                        'openSupportTickets' => $openSupportTickets,
                        'unreadNotifications' => $unreadNotifications,
                    ]
            )
            ?>
        </div>
        <div class="col-lg-9">
            <?= $content ?>
        </div>
    </div>
</div>
<?php $this->endContent(); ?>