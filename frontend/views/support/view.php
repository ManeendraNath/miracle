<?php

declare(strict_types=1);

use common\models\SupportTicket;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var common\models\SupportTicket $model */
/** @var common\models\SupportTicketMessage[] $messages */
$this->title = $model->ticket_number;

$formatter = Yii::$app->formatter;
$statusClass = match ($model->status) {
    SupportTicket::STATUS_OPEN => 'bg-danger-subtle text-danger-emphasis',
    SupportTicket::STATUS_IN_PROGRESS => 'bg-warning-subtle text-warning-emphasis',
    SupportTicket::STATUS_RESOLVED => 'bg-success-subtle text-success-emphasis',
    default => 'bg-secondary-subtle text-secondary-emphasis',
};
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div class="min-w-0">
        <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2 flex-wrap">
            <?= Html::encode($model->ticket_number) ?>
            <span class="badge fs-6 fw-semibold <?= $statusClass ?>"><?= Html::encode($model->status) ?></span>
        </h1>
        <div class="text-body-secondary text-break"><?= Html::encode($model->subject) ?></div>
    </div>
    <div>
        <?= Html::a('<i class="fas fa-arrow-left me-1"></i> All tickets', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 fw-bold mb-0">Your request</h2>
        <div class="small text-body-secondary">
            <?= Html::encode($model->priority) ?> priority &middot; <?= $formatter->asDatetime($model->created_at) ?>
        </div>
    </div>
    <div class="card-body text-break">
        <?= nl2br(Html::encode($model->message)) ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent py-3">
        <h2 class="h6 fw-bold mb-0">Conversation</h2>
    </div>
    <div class="card-body">
        <?php if (!$messages): ?>
            <div class="text-center py-4 text-body-secondary">
                <i class="fas fa-comments fa-2x mb-2 opacity-50"></i>
                <div>No replies yet.</div>
            </div>
        <?php endif; ?>
        <?php foreach ($messages as $message): ?>
            <?php $customer = strtolower($message->sender_type) === 'customer'; ?>
            <div class="d-flex mb-3 <?= $customer ? 'justify-content-end' : 'justify-content-start' ?>">
                <div class="p-3 rounded-3 text-break <?= $customer ? 'bg-primary text-white' : 'bg-body-tertiary border' ?>" style="max-width: 80%;">
                    <div class="small fw-semibold mb-1 <?= $customer ? 'text-white-50' : 'text-body-secondary' ?>"><?= $customer ? 'You' : 'Support team' ?></div>
                    <?= nl2br(Html::encode($message->message)) ?>
                    <div class="small mt-2 <?= $customer ? 'text-white-50' : 'text-body-secondary' ?>">
                        <?= $formatter->asDatetime($message->created_at) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($model->status !== SupportTicket::STATUS_CLOSED): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent py-3">
            <h2 class="h6 fw-bold mb-0">Reply</h2>
        </div>
        <div class="card-body">
            <form method="post" action="<?= Url::to(['/support/reply', 'number' => $model->ticket_number]) ?>">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                <textarea name="message" rows="5" class="form-control mb-3" required aria-label="Your reply" placeholder="Write your reply..."></textarea>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Send Reply</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-secondary border-0 small mb-0">
        <i class="fas fa-lock me-1"></i> This ticket is closed. Create a new ticket if you need more help.
    </div>
<?php endif; ?>
