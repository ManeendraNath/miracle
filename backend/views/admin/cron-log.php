<?php
use yii\helpers\Html;
/** @var yii\web\View $this */
/** @var array $logLines */
/** @var array $metrics */
/** @var string $logPath */

$this->title = 'Automation Run Logs';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cron-log-viewer container-fluid">
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
                    if (stripos($line, 'Successfully') !== false || stripos($line, 'complete') !== false) $color = '#2ecc71'; // Active emerald green
                    if (stripos($line, 'Skipping') !== false) $color = '#f1c40f'; // Warn gold text
                    if (stripos($line, 'error') !== false || stripos($line, 'failed') !== false) $color = '#e74c3c'; // Fire red
                    ?>
                    <div style="color: <?= $color ?>; border-bottom: 1px solid #2d2d2d;" class="py-1">
                        <span class="text-muted mr-2">[⚡]</span> <?= Html::encode($line) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
