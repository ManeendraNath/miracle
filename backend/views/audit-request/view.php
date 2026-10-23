<?php
declare(strict_types=1);

use common\models\AuditRequest;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\AuditRequest $model */
$this->title = $model->name;

$this->params['breadcrumbs'][] = [
    'label' => 'Audit Requests',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;

$statusClass = match ($model->status) {
    AuditRequest::STATUS_PENDING => 'bg-warning text-dark',
    AuditRequest::STATUS_IN_REVIEW => 'bg-info text-dark',
    AuditRequest::STATUS_CONTACTED => 'bg-primary',
    AuditRequest::STATUS_CLOSED => 'bg-success',
    default => 'bg-secondary',
};

$statusLabel = AuditRequest::getStatuses()[$model->status] ?? $model->status;
?>

<div class="audit-request-view">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h4 class="mb-1">
<?= Html::encode($this->title) ?>
            </h4>

            <div class="text-muted small">
                Website audit request and technical assessment details.
            </div>

        </div>

        <div class="d-flex gap-2">

<?=
Html::a(
        '<i class="fas fa-arrow-left mr-1"></i> Back',
        ['index'],
        [
            'class' => 'btn btn-light border',
        ]
)
?>

            <?=
            Html::a(
                    '<i class="fas fa-edit mr-1"></i> Update',
                    [
                        'update',
                        'id' => $model->id,
                    ],
                    [
                        'class' => 'btn btn-primary',
                    ]
            )
            ?>

        </div>

    </div>

    <div class="row">

        <div class="col-lg-6 mb-3">

            <div class="card card-outline card-primary shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        Client Information
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-4">
                            Name
                        </dt>

                        <dd class="col-sm-8">
<?= Html::encode($model->name) ?>
                        </dd>

                        <dt class="col-sm-4">
                            Email
                        </dt>

                        <dd class="col-sm-8">

                            <?=
                            $model->email ? Html::a(
                                            Html::encode($model->email),
                                            'mailto:' . $model->email
                                    ) : '-'
                            ?>

                        </dd>

                        <dt class="col-sm-4">
                            Phone
                        </dt>

                        <dd class="col-sm-8">
                            <?=
                            Html::encode(
                                    $model->phone ?: '-'
                            )
                            ?>
                        </dd>

                        <dt class="col-sm-4">
                            Website
                        </dt>

                        <dd class="col-sm-8">

                            <?php if ($model->company_url): ?>

                                <?=
                                Html::a(
                                        Html::encode(
                                                $model->company_url
                                        ),
                                        $model->company_url,
                                        [
                                            'target' => '_blank',
                                            'rel' => 'noopener noreferrer',
                                        ]
                                )
                                ?>

<?php else: ?>

                                -

<?php endif; ?>

                        </dd>

                    </dl>

                </div>

            </div>

        </div>

        <div class="col-lg-6 mb-3">

            <div class="card card-outline card-info shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        Technical Information
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-5">
                            Framework
                        </dt>

                        <dd class="col-sm-7">
<?=
Html::encode(
        $model->current_framework ?: '-'
)
?>
                        </dd>

                        <dt class="col-sm-5">
                            Hosting Environment
                        </dt>

                        <dd class="col-sm-7">
<?=
Html::encode(
        $model->hosting_environment ?: '-'
)
?>
                        </dd>

                        <dt class="col-sm-5">
                            Status
                        </dt>

                        <dd class="col-sm-7">

<?=
Html::tag(
        'span',
        Html::encode($statusLabel),
        [
            'class' => 'badge '
            . $statusClass,
        ]
)
?>

                        </dd>

                    </dl>

                </div>

            </div>

        </div>

    </div>

    <div class="card card-outline card-secondary shadow-sm mb-3">

        <div class="card-header">
            <h3 class="card-title">
                Request Information
            </h3>
        </div>

        <div class="card-body">

<?php if (!empty($model->message)): ?>

                <div class="mb-3">
                <?=
                nl2br(
                        Html::encode($model->message)
                )
                ?>
                </div>

<?php else: ?>

                <div class="text-muted mb-3">
                    No additional request details provided.
                </div>

                    <?php endif; ?>

            <hr>

            <dl class="row mb-0">

                <dt class="col-lg-2 col-sm-4">
                    IP Address
                </dt>

                <dd class="col-lg-4 col-sm-8">
                    <?=
                    Html::encode(
                            $model->user_ip ?: '-'
                    )
                    ?>
                </dd>

                <dt class="col-lg-2 col-sm-4">
                    Created
                </dt>

                <dd class="col-lg-4 col-sm-8">

                    <?=
                    $model->created_at ? Yii::$app->formatter->asDatetime(
                                    $model->created_at,
                                    'php:d M Y, h:i A'
                            ) : '-'
                    ?>

                </dd>

                <dt class="col-lg-2 col-sm-4">
                    Updated
                </dt>

                <dd class="col-lg-4 col-sm-8">

<?=
$model->updated_at ? Yii::$app->formatter->asDatetime(
                $model->updated_at,
                'php:d M Y, h:i A'
        ) : '-'
?>

                </dd>

                <dt class="col-lg-2 col-sm-4">
                    Record ID
                </dt>

                <dd class="col-lg-4 col-sm-8">
<?= (int) $model->id ?>
                </dd>

            </dl>

        </div>

    </div>

</div>