<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Invoice $model */
/** @var yii\widgets\ActiveForm $form */
/** @var array $usersList Passed from InvoiceController */
?>

<div class="invoice-form card card-primary card-outline p-4">

    <?php $form = ActiveForm::begin(['id' => 'invoice-active-billing-form']); ?>

    <div class="row">
        <!-- 👥 Searchable/Selectable User Dropdown Field -->
        <div class="col-md-6">
            <?= $form->field($model, 'user_id')->dropDownList($usersList, [
                'prompt' => '-- Select Linked Account Profile --',
                'class' => 'form-control custom-select'
            ]) ?>
        </div>

        <!-- 🏷️ Invoice Unique Sequential Reference Code (Read-Only) -->
        <div class="col-md-6">
            <?= $form->field($model, 'invoice_number')->textInput([
                'maxlength' => true, 
                'readonly' => true,
                'class' => 'form-control bg-light font-weight-bold text-primary'
            ]) ?>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-md-4">
            <?= $form->field($model, 'client_name')->textInput(['maxlength' => true, 'placeholder' => 'Enter Business or Client Name']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'client_address_line_1')->textInput(['maxlength' => true, 'placeholder' => 'Street, Suite, Office Floor']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'client_address_line_2')->textInput(['maxlength' => true, 'placeholder' => 'City, State, Zip, Country']) ?>
        </div>
    </div>

    <hr class="my-4">
    <h5 class="text-secondary mb-3"><i class="fas fa-calculator mr-2"></i> Financial Ledger & Tax Matrix</h5>

    <div class="row">
        <div class="col-md-3">
            <?= $form->field($model, 'subtotal_amount')->textInput([
                'type' => 'number', 'step' => '0.01', 'id' => 'inv-subtotal', 'class' => 'form-control pricing-trigger'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'discount_amount')->textInput([
                'type' => 'number', 'step' => '0.01', 'id' => 'inv-discount', 'class' => 'form-control pricing-trigger'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'coupon_code')->textInput(['maxlength' => true, 'placeholder' => 'PROMO50']) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'status')->dropDownList([ 
                'Unpaid' => 'Unpaid', 
                'Paid' => 'Paid', 
                'Partially_Paid' => 'Partially Paid', 
                'Cancelled' => 'Cancelled', 
                'Refunded' => 'Refunded', 
            ], ['class' => 'form-control custom-select']) ?>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-md-3">
            <?= $form->field($model, 'cgst_percent')->textInput([
                'type' => 'number', 'step' => '0.01', 'id' => 'inv-cgst', 'class' => 'form-control pricing-trigger'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'sgst_percent')->textInput([
                'type' => 'number', 'step' => '0.01', 'id' => 'inv-sgst', 'class' => 'form-control pricing-trigger'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'igst_percent')->textInput([
                'type' => 'number', 'step' => '0.01', 'id' => 'inv-igst', 'class' => 'form-control pricing-trigger'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'due_date')->textInput(['type' => 'date', 'class' => 'form-control']) ?>
        </div>
    </div>

    <div class="row mt-4 p-3 bg-light border rounded align-items-center">
        <div class="col-md-8">
            <span class="text-muted text-sm"><i class="fas fa-info-circle mr-1"></i> Total payable amount automatically updates live using custom multi-tier tax adjustments calculations scripts.</span>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'total_payable')->textInput([
                'maxlength' => true, 
                'id' => 'inv-total-payable', 
                'readonly' => true,
                'class' => 'form-control form-control-lg font-weight-bold text-success border-success bg-white text-right'
            ]) ?>
        </div>
    </div>

    <!-- Hidden configuration field storage components -->
    <?= $form->field($model, 'coupon_id')->hiddenInput(['value' => 0])->label(false) ?>

    <div class="form-group mt-4 text-right">
        <?= Html::submitButton('<i class="fas fa-save mr-1"></i> Generate & Save Billing Record', ['class' => 'btn btn-lg btn-success px-4']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
// =====================================================================
// 🛠️ AUTOMATED REAL-TIME CLIENT CALCULATIONS ENGINE (JQUERY SCRIPT)
// =====================================================================
$script = <<< JS
$(document).on('input change', '.pricing-trigger', function() {
    let subtotal = parseFloat($('#inv-subtotal').val()) || 0.00;
    let discount = parseFloat($('#inv-discount').val()) || 0.00;
    
    // Process net taxable amount balance baseline
    let netTaxable = subtotal - discount;
    if (netTaxable < 0) netTaxable = 0.00;
    
    // Parse individual text input percent numbers
    let cgstPercent = parseFloat($('#inv-cgst').val()) || 0.00;
    let sgstPercent = parseFloat($('#inv-sgst').val()) || 0.00;
    let igstPercent = parseFloat($('#inv-igst').val()) || 0.00;
    
    // Calculate fractional values parameters matrices
    let cgstAmount = netTaxable * (cgstPercent / 100);
    let sgstAmount = netTaxable * (sgstPercent / 100);
    let igstAmount = netTaxable * (igstPercent / 100);
    
    // Compile dynamic composite final ledger numbers
    let totalPayable = netTaxable + cgstAmount + sgstAmount + igstAmount;
    
    // Output high precision string fields updates to view element components boxes
    $('#inv-total-payable').val(totalPayable.toFixed(2));
});
JS;
$this->registerJs($script);
?>
