<?php
declare(strict_types=1);

use common\models\ServiceRenewalHistory;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Renewal History';

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container py-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header">
            <strong>Upcoming Renewals (<?= count($upcomingServices) ?>)</strong>
        </div>
        <div class="card-body">
            <?php if (empty($upcomingServices)): ?>
                <div class="text-center text-muted py-4">
                    No services require renewal soon.
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($upcomingServices as $service): ?>
                        <?php
                        $days = (int) floor((strtotime($service['expiry']) - strtotime(date('Y-m-d'))) / 86400);
                        if ($days < 0) {
                            $label = 'Expired';
                            $class = 'text-danger';
                        } elseif ($days === 0) {
                            $label = 'Expires Today';
                            $class = 'text-warning';
                        } else {
                            $label = "Expires in {$days} day" . ($days !== 1 ? 's' : '');
                            $class = 'text-danger';
                        }
                        if ($service['type'] == 1) {
                            $serviceClass = "badge bg-primary";
                        } elseif ($service['type'] == 2) {
                            $serviceClass = "badge bg-success";
                        } elseif ($service['type'] == 3) {
                            $serviceClass = "badge bg-warning text-dark";
                        } else {
                            $serviceClass = "";
                        }
                        ?>
                    
                        <div class="col-lg-4 col-md-6">
                            <div class="card border-warning h-100">
                                <div class="card-body">
                                    <div class="text-muted small mb-2">
                                        <?= $service['type'] ?>
                                    </div>
                                    <div class="fw-bold mb-2">
                                        <span class="<?= $serviceClass ?>"><?= Html::encode($service['name']) ?></span>
                                    </div>
                                    <div class="text-danger mb-3">
                                        <div class="<?= $class ?> mb-3"><?= $label ?></div>
                                        Expires in <?= $days ?> day<?= $days !== 1 ? 's' : '' ?>
                                    </div>
                                    <?= Html::a('Renew Now', ['/checkout/renew', 'type' => strtolower($service['type']), 'id' => $service['id']], ['class' => 'btn btn-primary btn-sm']) ?>
                                    <?= Html::a('View', ['/service/view', 'type' => strtolower($service['type']), 'id' => $service['id']], ['class' => 'btn btn-outline-secondary btn-sm me-2']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="mb-4">
        <h2 class="mb-1">Renewal History</h2>
        <div class="text-muted">
            Review successfully completed service renewals.
        </div>
    </div>
    <div class="card shadow-sm">
        <?=
        GridView::widget([
            'dataProvider' => $dataProvider,
            'layout' => "{items}\n{pager}",
            'tableOptions' => [
                'class' => 'table table-hover align-middle mb-0'],
            'columns' => [
                ['attribute' => 'item_type', 'label' => 'Service'],
                [
                    'attribute' => 'billed_amount',
                    'label' => 'Amount',
                    'value' => static fn(ServiceRenewalHistory $model): string => Yii::$app->formatter->asCurrency($model->billed_amount, 'INR'),
                ],
                [
                    'attribute' => 'start_date',
                    'label' => 'Period Start',
                    'format' => ['date', 'php:d M Y'],
                ],
                [
                    'attribute' => 'end_date',
                    'label' => 'Period End',
                    'format' => ['date', 'php:d M Y'],
                ],
                [
                    'attribute' => 'renewal_period_months',
                    'label' => 'Period',
                    'value' => static fn(ServiceRenewalHistory $model): string => $model->renewal_period_months . ' month(s)',
                ],
                [
                    'attribute' => 'logged_at',
                    'label' => 'Completed',
                    'format' => ['datetime', 'php:d M Y, h:i A'],
                ],
            ],
        ])
        ?>
    </div>
</div>