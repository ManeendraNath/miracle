<?php
declare(strict_types=1);

use kartik\select2\Select2;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin(['id' => 'transaction-form']);
$isNew = $model->isNewRecord;
?>

<?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
<div class="card shadow-sm">
    <div class="card-header">
        <strong>Payment Transaction</strong>
    </div>
    <div class="card-body">
        <?= $form->field($model, 'invoice_id')->widget(
                        Select2::class,
                        [
                            'data' => $invoiceList,
                            'options' => ['placeholder' => 'Select Invoice'],
                            'pluginOptions' => ['allowClear' => false, 'width' => '100%'],
                        ]
                )
        ?>

        <?= $form->field($model, 'gateway_id')->dropDownList([2 => 'Manual UPI / Bank Transfer'], ['prompt' => 'Select Payment Method'])
        ?>

        <?= $form->field($model, 'gateway_payment_id')->textInput(['maxlength' => true, 'autocomplete' => 'off', 'placeholder' => 'UPI / UTR / Transaction Reference'])
        ?>

        <?= $form->field($model, 'amount')->input('number', ['min' => '0.01', 'step' => '0.01', 'placeholder' => '0.00'])
        ?>
        <?= $form->field($model, 'user_payment_notes')->textarea(['rows' => 4, 'placeholder' => 'Optional payment notes']) ?>
    </div>
    <div class="card-footer">
        <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::submitButton($isNew ? 'Create Pending Transaction' : 'Update Transaction', ['class' => 'btn btn-primary px-4']) ?>
    </div>

</div>

<?php ActiveForm::end(); ?>
