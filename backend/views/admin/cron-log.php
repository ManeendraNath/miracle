<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $logLines */
/** @var array $metrics */
/** @var string $logPath */
/** @var array<string, common\models\CronJob> $cronJobs */
$canSwitch = \backend\controllers\BaseController::canAccess(Yii::$app->user->identity, 'admin', 'cron-switch');

$this->title = 'Cron Jobs & Logs';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cron-log-viewer container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-header"><h3 class="card-title m-0">Cron Jobs</h3></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th class="ps-3">Job</th><th>Runs</th><th>Status</th><th>Last changed</th><?= $canSwitch ? '<th></th>' : '' ?></tr></thead>
                    <tbody>
                        <?php foreach (\common\models\CronJob::JOBS as $job => [$name, $runs, $whenOff]): ?>
                            <?php
                            $row = $cronJobs[$job] ?? null;
                            $on = $row === null || (bool) $row->is_enabled;
                            ?>
                            <tr>
                                <td class="ps-3"><?= Html::encode($name) ?></td>
                                <td><?= Html::encode($runs) ?></td>
                                <td><span class="badge <?= $on ? 'bg-success' : 'bg-secondary' ?>"><?= $on ? 'On' : 'Off' ?></span></td>
                                <td class="small text-muted"><?= $row !== null ? Html::encode($row->updated_by . ', ' . Yii::$app->formatter->asDatetime($row->updated_at, 'php:d M Y, h:i A')) : '-' ?></td>
                                <?php if ($canSwitch): ?>
                                    <td class="text-end pe-3">
                                        <?= Html::a($on ? 'Turn Off' : 'Turn On', ['cron-switch'], [
                                            'class' => 'btn btn-sm ' . ($on ? 'btn-outline-danger' : 'btn-outline-success'),
                                            'data' => [
                                                'method' => 'post',
                                                'confirm' => $on ? "Turn off {$name}?\n\n{$whenOff}" : "Turn on {$name}?",
                                                'params' => ['job' => $job, 'enabled' => $on ? '0' : '1'],
                                            ],
                                        ]) ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Row 1: High Visibility Status Badges Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="info-box bg-success shadow-sm">
                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Successful Dispatches</span>
                    <span class="info-box-number fs-4"><?= $metrics['success'] ?> Actions</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-warning shadow-sm">
                <span class="info-box-icon text-white"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content text-white">
                    <span class="info-box-text">Skipped Notifications</span>
                    <span class="info-box-number fs-4"><?= $metrics['warnings'] ?> Warnings</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-danger shadow-sm">
                <span class="info-box-icon"><i class="fas fa-bug"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Logged Faults / Loops</span>
                    <span class="info-box-number fs-4"><?= $metrics['errors'] ?> Failures</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Console Log Screen Console Output Box -->
    <div class="card card-dark shadow">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h3 class="card-title m-0 text-monospace small"><i class="fas fa-terminal text-warning mr-2"></i> tail -n 150 <?= Html::encode($logPath) ?></h3>
            <button onclick="window.location.reload();" class="btn btn-xs btn-outline-light rounded-pill px-3 ml-auto"><i class="fas fa-sync-alt mr-1"></i> Refresh Live Terminal</button>
        </div>
        <div class="card-body bg-black p-4 text-monospace" style="max-height: 500px; overflow-y: auto; background-color: #1e1e1e; border-radius: 0 0 4px 4px;">
            <div class="terminal-shell-lines text-sm" style="line-height: 1.8; font-family: 'Courier New', Courier, monospace;">
                <?php foreach ($logLines as $line): ?>
                    <?php
                    $color = '#dcdcdc'; // Default gray-white line text text
                    if (stripos($line, 'Successfully') !== false || stripos($line, 'complete') !== false) {
                        $color = '#2ecc71'; // Active emerald green
                    }
                    if (stripos($line, 'Skipping') !== false) {
                        $color = '#f1c40f'; // Warn gold text
                    }
                    if (stripos($line, 'error') !== false || stripos($line, 'failed') !== false || stripos($line, 'failure') !== false) {
                        $color = '#e74c3c'; // Fire red
                    }
                    ?>
                    <div style="color: <?= $color ?>; border-bottom: 1px solid #2d2d2d;" class="py-1">
                        <span class="text-muted mr-2">[⚡]</span> <?= Html::encode($line) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
