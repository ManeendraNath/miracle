<?php
declare(strict_types=1);

use common\models\Maintenance;
use yii\helpers\Html;

$this->title = $model->website_url ?: 'Maintenance #' . $model->id;

$this->params['breadcrumbs'][] = [
    'label' => 'Maintenance Contracts',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;

$statuses = Maintenance::getStatuses();

$status = (int) $model->status;

$statusLabel = $statuses[$status] ?? 'Unknown';

$statusClass = match ($status) {
    1 => 'bg-success',
    2 => 'bg-warning text-dark',
    0 => 'bg-secondary',
    default => 'bg-dark',
};
?>

<div class="maintenance-view">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h4 class="mb-1">
                <?= Html::encode($this->title) ?>
            </h4>

            <div class="text-muted small">
                Maintenance service and renewal details.
            </div>

        </div>

        <div class="d-flex gap-2">

            <?=
            Html::a(
                    'Back',
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

        <div class="col-lg-7 mb-3">

            <div class="card card-outline card-primary shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        Maintenance Details
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-4">
                            Client
                        </dt>

                        <dd class="col-sm-8">

                            <?php if ($model->user !== null): ?>

                                <div class="fw-semibold">
                                    <?=
                                    Html::encode(
                                            $model->user->username
                                    )
                                    ?>
                                </div>

                                <div class="text-muted small">
                                    <?=
                                    Html::encode(
                                            $model->user->email
                                    )
                                    ?>
                                </div>

                            <?php else: ?>

                                <span class="text-muted">
                                    -
                                </span>

                            <?php endif; ?>

                        </dd>

                        <dt class="col-sm-4">
                            Website
                        </dt>

                        <dd class="col-sm-8">

                            <?=
                            Html::a(
                                    Html::encode(
                                            $model->website_url
                                    ),
                                    $model->website_url,
                                    [
                                        'target' => '_blank',
                                        'rel' => 'noopener noreferrer',
                                    ]
                            )
                            ?>

                        </dd>

                        <dt class="col-sm-4">
                            Status
                        </dt>

                        <dd class="col-sm-8">

                            <?=
                            Html::tag(
                                    'span',
                                    Html::encode($statusLabel),
                                    [
                                        'class' =>
                                        'badge ' . $statusClass,
                                    ]
                            )
                            ?>

                        </dd>

                    </dl>

                </div>

            </div>

        </div>

        <div class="col-lg-5 mb-3">

            <div class="card card-outline card-success shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        Service Period
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-5">
                            Start Date
                        </dt>

                        <dd class="col-sm-7">

                            <?=
                            $model->start_date ? Yii::$app->formatter->asDate(
                                            $model->start_date,
                                            'php:d M Y'
                                    ) : '-'
                            ?>

                        </dd>

                        <dt class="col-sm-5">
                            Current Expiry
                        </dt>

                        <dd class="col-sm-7">

                            <?=
                            $model->current_expiry_date ? Yii::$app->formatter->asDate(
                                            $model->current_expiry_date,
                                            'php:d M Y'
                                    ) : '-'
                            ?>

                        </dd>

                    </dl>

                </div>

            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-lg-7 mb-3">

            <div class="card card-outline card-info shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        Renewal & Billing
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-5">
                            Base Renewal Amount
                        </dt>

                        <dd class="col-sm-7">

                            <?=
                            $model->renewal_base_amount !== null ? Yii::$app->formatter->asCurrency(
                                            $model->renewal_base_amount,
                                            'INR'
                                    ) : '-'
                            ?>

                        </dd>

                        <dt class="col-sm-5">
                            Renewal Cycle
                        </dt>

                        <dd class="col-sm-7">

                            <?php if ($model->renewal_cycle_months): ?>

                                <?=
                                Html::encode(
                                        $model->renewal_cycle_months
                                        . ' month(s)'
                                )
                                ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Global setting
                                </span>

                            <?php endif; ?>

                        </dd>

                        <dt class="col-sm-5">
                            Renewal Increase
                        </dt>

                        <dd class="col-sm-7">

                            <?php
                            if (
                                    $model->renewal_increase_percent !== null
                            ):
                                ?>

                                <?=
                                Html::encode(
                                        number_format(
                                                (float) $model->renewal_increase_percent,
                                                2
                                        ) . '%'
                                )
                                ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Global setting
                                </span>

                            <?php endif; ?>

                        </dd>

                    </dl>

                </div>

            </div>

        </div>

        <div class="col-lg-5 mb-3">

            <div class="card card-outline card-secondary shadow-sm h-100">

                <div class="card-header">
                    <h3 class="card-title">
                        System Information
                    </h3>
                </div>

                <div class="card-body">

                    <dl class="row mb-0">

                        <dt class="col-sm-5">
                            Record ID
                        </dt>

                        <dd class="col-sm-7">
                            <?= (int) $model->id ?>
                        </dd>

                        <dt class="col-sm-5">
                            Created
                        </dt>

                        <dd class="col-sm-7">

                            <?=
                            Yii::$app->formatter->asDatetime(
                                    $model->created_at,
                                    'php:d M Y, h:i A'
                            )
                            ?>

                        </dd>

                        <dt class="col-sm-5">
                            Updated
                        </dt>

                        <dd class="col-sm-7">

                            <?=
                            Yii::$app->formatter->asDatetime(
                                    $model->updated_at,
                                    'php:d M Y, h:i A'
                            )
                            ?>

                        </dd>

                    </dl>

                </div>

            </div>

        </div>

    </div>

    <div class="d-flex gap-2">
        <?=
        Html::a(
                '<i class="fas fa-calendar-check mr-1"></i> Renewal Monitoring',
                [
                    '/renewal-monitoring/index',
                    'type' => 'maintenance',
                    'user_id' => $model->user_id,
                    'window' => 'all',
                ],
                [
                    'class' => 'btn btn-outline-info',
                ]
        )
        ?>
    </div>
</div>