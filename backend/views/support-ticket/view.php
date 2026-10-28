<?php
declare(strict_types=1);

use common\models\SupportTicket;
use common\models\SupportTicketMessage;
use yii\helpers\Html;

$this->title = 'Support ' . $model->ticket_number;

$this->params['breadcrumbs'][] = [
    'label' => 'Support Requests',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $model->ticket_number;

$statusClass = match ($model->status) {
    SupportTicket::STATUS_OPEN => 'bg-primary',
    SupportTicket::STATUS_IN_PROGRESS => 'bg-warning text-dark',
    SupportTicket::STATUS_RESOLVED => 'bg-success',
    SupportTicket::STATUS_CLOSED => 'bg-secondary',
    default => 'bg-secondary',
};

$priorityClass = match ($model->priority) {
    SupportTicket::PRIORITY_HIGH => 'bg-danger',
    SupportTicket::PRIORITY_LOW => 'bg-secondary',
    default => 'bg-info text-dark',
};
?>

<div class="support-ticket-view">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><?= Html::encode($model->subject) ?></h1>
            <div class="text-muted"><?= Html::encode($model->ticket_number) ?></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?= Html::a('Back', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>&nbsp;
            <?php if ($model->status === SupportTicket::STATUS_RESOLVED || $model->status === SupportTicket::STATUS_CLOSED): ?>
                <?=
                Html::a('Reopen',
                        ['reopen', 'number' => $model->ticket_number],
                        [
                            'class' => 'btn btn-sm btn-outline-primary',
                            'data' => ['method' => 'post', 'confirm' => 'Reopen this support request?'],
                        ]
                )
                ?>&nbsp;
            <?php else: ?>
                <?=
                Html::a('Resolve',
                        ['resolve', 'number' => $model->ticket_number],
                        [
                            'class' => 'btn btn-sm btn-success',
                            'data' => ['method' => 'post', 'confirm' => 'Mark this support request as resolved?'],
                        ]
                )
                ?>&nbsp;
                <?=
                Html::a('Close',
                        ['close', 'number' => $model->ticket_number],
                        [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'data' => ['method' => 'post', 'confirm' => 'Close this support request?'],
                        ]
                )
                ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <strong>Request Details</strong>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small">Customer</div>
                        <div class="fw-semibold">
                            <?= Html::encode($model->user?->username ?: '-') ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Email</div>
                        <div>
                            <?= $model->user ? Html::a(Html::encode($model->user->email), 'mailto:' . $model->user->email) : '-' ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Status</div>
                        <?= Html::tag('span', Html::encode($model->status), ['class' => 'badge ' . $statusClass]) ?>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Priority</div>
                        <?= Html::tag('span', Html::encode($model->priority), ['class' => 'badge ' . $priorityClass]) ?>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Service</div>
                        <div>
                            <?= Html::encode($model->service_type ?: 'General Support') ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Service ID</div>
                        <div><?= $model->service_id ?: '-' ?></div>
                    </div>
                    <div>
                        <div class="text-muted small">Created</div>
                        <div>
                            <?= Yii::$app->formatter->asDatetime($model->created_at, 'php:d M Y, h:i A') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <strong>Conversation</strong>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-start mb-4">
                        <div class="rounded-3 border bg-light p-3" style="max-width:80%;">
                            <div class="small fw-semibold mb-1">Customer</div>
                            <div><?= nl2br(Html::encode($model->message)) ?></div>
                            <div class="small text-muted mt-2">
                                <?= Yii::$app->formatter->asDatetime($model->created_at, 'php:d M Y, h:i A') ?>
                            </div>
                        </div>
                    </div>

                    <?php foreach ($model->messages as $message): ?>
                        <?php $isAdmin = $message->sender_type === SupportTicketMessage::SENDER_ADMIN; ?>
                        <div class="d-flex <?= $isAdmin ? 'justify-content-end' : 'justify-content-start' ?> mb-3">
                            <div class="rounded-3 p-3 <?= $isAdmin ? 'bg-primary text-white' : 'bg-light border' ?>" style="max-width:80%;">
                                <div class="small fw-semibold mb-1">
                                    <?= $isAdmin ? 'Support Team' : 'Customer' ?>
                                </div>
                                <div><?= nl2br(Html::encode($message->message)) ?></div>
                                <div class="small mt-2 <?= $isAdmin ? 'text-white-50' : 'text-muted' ?>">
                                    <?= Yii::$app->formatter->asDatetime($message->created_at, 'php:d M Y, h:i A') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($model->status !== SupportTicket::STATUS_CLOSED): ?>
                <?= Html::beginForm(['reply', 'number' => $model->ticket_number], 'post') ?>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <strong>Reply to Customer</strong>
                    </div>
                    <div class="card-body">
                        <?=
                        Html::textarea('message', '',
                                [
                                    'class' => 'form-control',
                                    'rows' => 7,
                                    'maxlength' => 10000,
                                    'required' => true,
                                    'placeholder' => 'Write your response...',
                                ]
                        )
                        ?>
                    </div>
                    <div class="card-footer">
                        <?= Html::submitButton('Send Reply', ['class' => 'btn btn-primary px-4']) ?>
                    </div>
                </div>
                <?= Html::endForm() ?>
            <?php endif; ?>
        </div>
    </div>
</div>