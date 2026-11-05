<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\services\report\AccountingReportService;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $from */
/** @var string $to */

$this->title = 'Reports';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="card card-outline card-primary shadow-sm">
    <?= AdminIndexHelper::pageHeader('GST exports (CSV) of invoices and payments for a date range.') ?>
    <?= Html::beginForm(['download'], 'get') ?>
    <div class="card-body">
        <p class="text-muted">
            Invoices are selected by issue date (cancelled ones included), payments by the date they were captured or last
            refunded, so a refund of an earlier payment shows that payment again with its original paid date.
            Refunded To Date is a running total: don't add it up across months. Up to <?= AccountingReportService::MAX_RANGE_DAYS ?> days per export.
        </p>
        <div class="row">
            <div class="col-sm-6 col-lg-3 form-group">
                <label for="report-from">From</label>
                <input type="date" id="report-from" name="from" class="form-control" required value="<?= Html::encode($from) ?>">
            </div>
            <div class="col-sm-6 col-lg-3 form-group">
                <label for="report-to">To</label>
                <input type="date" id="report-to" name="to" class="form-control" required value="<?= Html::encode($to) ?>">
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" name="type" value="invoices" class="btn btn-primary">
            <i class="fas fa-file-invoice mr-1"></i> Invoices CSV
        </button>
        <button type="submit" name="type" value="payments" class="btn btn-outline-primary ml-2">
            <i class="fas fa-money-check-alt mr-1"></i> Payments CSV
        </button>
    </div>
    <?= Html::endForm() ?>
</div>
