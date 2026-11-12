<?php

declare(strict_types=1);

use common\models\CustomerBillingProfile;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin([
    'id' => 'customer-billing-profile-form',
]);
?>

<?= $form->errorSummary($model, [
    'class' => 'alert alert-danger',
]) ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <strong>Customer Billing Identity</strong>
    </div>

    <div class="card-body">

        <?= $form->field($model, 'user_id')->widget(Select2::class, [
            'data' => $usersList,
            'disabled' => !$canSetEverything || !$model->isNewRecord,
            'options' => [
                'placeholder' => 'Select Client',
            ],
            'pluginOptions' => [
                'allowClear' => false,
                'width' => '100%',
            ],
        ])->hint($model->isNewRecord ? '' : 'Locked: this profile\'s company name, GST number and address belong to this client. To fix wrong details, correct them below; another client\'s details go in their own profile.') ?>

        <?= $form->field($model, 'legal_name')->textInput([
            'maxlength' => true,
            'placeholder' => 'Legal / Company Name',
        ]) ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'address_line_1')->textInput([
                    'maxlength' => true,
                ]) ?>
            </div>

            <div class="col-md-6">
                <?= $form->field($model, 'address_line_2')->textInput([
                    'maxlength' => true,
                ]) ?>
            </div>
        </div>

        <?= $form->field($model, 'country')
            ->dropDownList(CustomerBillingProfile::countryOptions())
            ->hint('Outside India: export of services, no GST; state, GSTIN and PAN are not used.') ?>
        <?= $this->render('@common/views/_billing_country_js') ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'city')->textInput(['maxlength' => true]) ?>
            </div>

            <div class="col-md-6">
                <?= $form
                    ->field($model, 'state')
                    ->dropDownList(CustomerBillingProfile::stateOptions(), ['prompt' => 'Select State'])
                    ->hint('The GST state code is set from the state.') ?>
            </div>
        </div>

        <?= $form->field($model, 'postal_code')->textInput([
            'maxlength' => true,
        ]) ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'gstin')->textInput([
                    'maxlength' => true,
                ]) ?>
            </div>

            <div class="col-md-6">
                <?= $form->field($model, 'pan')->textInput([
                    'maxlength' => true,
                ]) ?>
            </div>
        </div>

        <?php if ($canSetEverything): ?>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'is_gst_applicable')->dropDownList([
                    0 => 'No',
                    1 => 'Yes',
                ]) ?>
            </div>

            <div class="col-md-6">
                <?= $form
                    ->field($model, 'default_discount_amount')
                    ->input('number', [
                        'min' => 0,
                        'step' => '0.01',
                        'placeholder' => '0.00',
                    ])
                    ->hint('Fixed discount applied to automatically generated invoices.') ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="card-footer">
        <?= Html::submitButton('Save Billing Profile', [
            'class' => 'btn btn-primary px-4',
        ]) ?>

        <?= Html::a(
            $customer !== null ? 'Customer Will Complete This' : 'Cancel',
            $customer !== null ? ['/user/view', 'id' => $customer->id] : ['index'],
            [
                'class' => 'btn btn-outline-secondary',
            ],
        ) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>
