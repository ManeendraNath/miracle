<?php

declare(strict_types=1);

use common\models\ServiceRenewalHistory;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Renewal History';

$this->params['breadcrumbs'][] = $this->title;

$formatter = Yii::$app->formatter;
$today = strtotime(date('Y-m-d'));
$expiryLabel = static function (string $expiry) use ($today): array {
    $days = (int) floor((strtotime($expiry) - $today) / 86400);
    return match (true) {
        $days < 0 => ['bg-danger-subtle text-danger-emphasis', 'Expired'],
        $days === 0 => ['bg-danger-subtle text-danger-emphasis', 'Expires Today'],
        default => [$days <= 15 ? 'bg-warning-subtle text-warning-emphasis' : 'bg-info-subtle text-info-emphasis', "Expires in {$days} day" . ($days !== 1 ? 's' : '')],
    };
};
?>

<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Renewals</h1>
    <div class="text-body-secondary">Renew services that expire soon and review completed renewals.</div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex align-items-center gap-2 py-3">
        <h2 class="h6 fw-bold mb-0">Upcoming Renewals</h2>
        <span class="badge rounded-pill <?= $upcomingServices ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= count($upcomingServices) ?></span>
    </div>
    <?php if (empty($upcomingServices)): ?>
        <div class="text-center py-5 text-body-secondary">
            <i class="fas fa-check-circle fa-2x mb-2 opacity-50"></i>
            <div>No services require renewal soon.</div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="small text-body-secondary">
                    <tr>
                        <th class="ps-3">Service</th>
                        <th class="d-none d-md-table-cell">Expires</th>
                        <th class="d-none d-md-table-cell">Status</th>
                        <th class="pe-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingServices as $service): ?>
                        <?php [$badge, $label] = $expiryLabel($service['expiry']) ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold text-break"><?= Html::encode($service['name']) ?></div>
                                <div class="small text-body-secondary"><?= Html::encode($service['type']) ?><span class="d-md-none"> · <?= $formatter->asDate($service['expiry'], 'php:d M Y') ?></span></div>
                                <span class="badge <?= $badge ?> d-md-none mt-1"><?= $label ?></span>
                            </td>
                            <td class="text-nowrap d-none d-md-table-cell"><?= $formatter->asDate($service['expiry'], 'php:d M Y') ?></td>
                            <td class="d-none d-md-table-cell"><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                            <td class="text-end text-nowrap pe-3">
                                <?= Html::a('View', ['/service/view', 'type' => strtolower($service['type']), 'id' => $service['id']], ['class' => 'btn btn-sm btn-outline-secondary me-1']) ?>
                                <?= $this->render('_action', ['type' => strtolower($service['type']), 'id' => $service['id'], 'renewal' => $service['renewal']]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent py-3">
        <h2 class="h6 fw-bold mb-0">Renewal History</h2>
        <div class="small text-body-secondary">Review successfully completed service renewals.</div>
    </div>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'layout' => "<div class=\"table-responsive\">{items}</div>\n<div class=\"d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 px-3 py-3\">{summary}{pager}</div>",
        'summaryOptions' => ['class' => 'small text-body-secondary'],
        'pager' => ['class' => \yii\bootstrap5\LinkPager::class, 'options' => ['class' => 'mb-0'], 'listOptions' => ['class' => 'pagination pagination-sm mb-0']],
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'headerRowOptions' => ['class' => 'small text-body-secondary'],
        'emptyText' => '<div class="text-center py-5 text-body-secondary"><i class="fas fa-history fa-2x mb-2 opacity-50"></i><div>No completed renewals yet.</div></div>',
        'emptyTextOptions' => ['class' => ''],
        'columns' => [
            [
                'label' => 'Service',
                'headerOptions' => ['class' => 'ps-3'],
                'contentOptions' => ['class' => 'ps-3 fw-semibold'],
                'value' => static fn(ServiceRenewalHistory $model): string => ucfirst((string) $model->item_type),
            ],
            [
                'label' => 'Amount',
                'headerOptions' => ['class' => 'text-end'],
                'contentOptions' => ['class' => 'text-end text-nowrap'],
                'value' => static fn(ServiceRenewalHistory $model): string => $formatter->asCurrency($model->billed_amount, 'INR'),
            ],
            [
                'label' => 'Period Start',
                'value' => 'start_date',
                'format' => ['date', 'php:d M Y'],
                'contentOptions' => ['class' => 'text-nowrap'],
            ],
            [
                'label' => 'Period End',
                'value' => 'end_date',
                'format' => ['date', 'php:d M Y'],
                'contentOptions' => ['class' => 'text-nowrap'],
            ],
            [
                'label' => 'Period',
                'headerOptions' => ['class' => 'd-none d-md-table-cell'],
                'contentOptions' => ['class' => 'd-none d-md-table-cell text-nowrap'],
                'value' => static fn(ServiceRenewalHistory $model): string => $model->renewal_period_months . ' month(s)',
            ],
            [
                'label' => 'Completed',
                'value' => 'logged_at',
                'format' => 'datetime',
                'headerOptions' => ['class' => 'pe-3'],
                'contentOptions' => ['class' => 'text-nowrap small text-body-secondary pe-3'],
            ],
        ],
    ]) ?>
</div>
