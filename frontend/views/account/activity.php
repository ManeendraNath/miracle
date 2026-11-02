<?php

use yii\helpers\Html;

$this->title = 'Activity Timeline';
$this->params['breadcrumbs'][] = $this->title;

$formatter = Yii::$app->formatter;
$groupedActivities = [];
foreach ($activities as $activity) {
    $groupedActivities[$formatter->asDate($activity['time'], 'php:Y-m-d')][] = $activity;
}
$today = $formatter->asDate(time(), 'php:Y-m-d');
$yesterday = $formatter->asDate(strtotime('-1 day'), 'php:Y-m-d');
?>

<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Activity Timeline</h1>
    <div class="text-body-secondary">Recent invoices, payments, renewals, tickets and notifications on your account.</div>
</div>

<div class="card border-0 shadow-sm">
    <?php if (empty($activities)): ?>
        <div class="text-center py-5 text-body-secondary">
            <i class="fas fa-history fa-2x mb-2 opacity-50"></i>
            <div>No activity available.</div>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush rounded">
            <?php foreach ($groupedActivities as $date => $items): ?>
                <?php
                if ($date === $today) {
                    $heading = 'Today';
                } elseif ($date === $yesterday) {
                    $heading = 'Yesterday';
                } else {
                    $heading = $formatter->asDate($date, 'php:d M Y');
                }
                ?>
                <div class="list-group-item bg-body-tertiary py-2 small fw-semibold text-uppercase text-body-secondary"><?= $heading ?></div>

                <?php foreach ($items as $activity): ?>
                    <?php
                    // The service supplies solid badge classes (e.g. "bg-warning text-dark"); show them in the subtle style.
                    $color = preg_match('/\bbg-(primary|secondary|success|danger|warning|info)\b/', $activity['badgeClass'], $m) ? $m[1] : 'secondary';
                    ?>
                    <a href="<?= Html::encode($activity['url']) ?>" class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3">
                        <span class="dash-stat-icon bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis">
                            <i class="<?= Html::encode($activity['icon']) ?>"></i>
                        </span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex flex-column flex-sm-row justify-content-between gap-sm-3">
                                <div class="fw-semibold text-break"><?= Html::encode($activity['title']) ?></div>
                                <div class="small text-body-secondary text-nowrap">
                                    <?= $formatter->asTime($activity['time'], 'php:h:i A') ?> &middot; <?= $formatter->asRelativeTime($activity['time']) ?>
                                </div>
                            </div>
                            <div class="small text-body-secondary text-break"><?= Html::encode($activity['description']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
