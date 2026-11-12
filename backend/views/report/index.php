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
$this->params['help'] = [
    'From / To' => 'The dates to export, both days included. They start as last month. Up to ' . AccountingReportService::MAX_RANGE_DAYS . ' days per file.',
    'Invoices CSV' => 'One row per invoice issued in those dates, cancelled ones included; invoices still waiting for review are left out. For sales totals add up Invoice Value (excl. arrears), not Total Payable, so carried-forward balances are not counted twice.',
    'Payments CSV' => 'One row per payment received or refunded in those dates, with method, reference, gateway fee and refunds. Unapplied payments are marked (unapplied).',
    'Refunds across months' => 'A payment refunded in a later month appears in both months\' files. Add up Refunded In Period across files, as it only counts refunds in that file\'s dates.',
    'The files open in Excel or Google Sheets, for your accountant\'s GST filing.',
];
?>

<div class="card card-outline card-primary shadow-sm">
    <?= AdminIndexHelper::pageHeader('GST exports (CSV) of invoices and payments for a date range.') ?>
    <?= Html::beginForm(['download'], 'get') ?>
    <div class="card-body">
        <p class="text-muted">
            Invoices are selected by issue date (cancelled ones included), payments by the date they were captured or refunded,
            so a refund of an earlier payment shows that payment again with its original paid date.
            Refunded In Period only counts refunds in the chosen dates, so it can be added up across months. Up to <?= AccountingReportService::MAX_RANGE_DAYS ?> days per export.
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
