<?php
declare(strict_types=1);

use yii\helpers\Html;

/** @var common\models\SupportTicket $model */
/** @var common\models\SupportTicketMessage[] $messages */
$this->title = $model->ticket_number;
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong><?= Html::encode($model->ticket_number) ?></strong>
                <div class="small text-muted">
                    <?= Html::encode($model->subject) ?>
                </div>
            </div>
            <span class="badge bg-primary">
                <?= Html::encode($model->status) ?>
            </span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <?= nl2br(Html::encode($model->message)) ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header">
        <strong>Conversation</strong>
    </div>

    <div class="card-body">
        <?php
        foreach ($messages as $message):
            $customer = strtolower($message->sender_type) === 'customer';
            ?>
            <div class="d-flex mb-3 <?= $customer ? 'justify-content-end' : 'justify-content-start' ?>">
                <div class="p-3 rounded <?= $customer ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width:70%;">
                    <?= nl2br(Html::encode($message->message)) ?>
                    <div class="small mt-2 <?= $customer ? 'text-white-50' : 'text-muted' ?>">
                        <?= Yii::$app->formatter->asDatetime($message->created_at) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($model->status !== \common\models\SupportTicket::STATUS_CLOSED): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header">
        Reply
    </div>
    <div class="card-body">
        <form method="post" action="<?= \yii\helpers\Url::to(['/support/reply', 'number' => $model->ticket_number]) ?>">

            <?= Html::hiddenInput(
                Yii::$app->request->csrfParam,
                Yii::$app->request->getCsrfToken()
            ) ?>
            <textarea name="message" rows="5" class="form-control mb-3" required></textarea>
            <button type="submit" class="btn btn-primary">Send Reply</button>
        </form>
    </div>
</div>
<?php endif; ?>
