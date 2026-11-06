<?php

declare(strict_types=1);

use common\models\CustomerBillingProfile;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'My Profile';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-end gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">My Profile</h1>
        <div class="text-body-secondary">Manage your customer account and review billing information.</div>
    </div>
    <?= Html::a('<i class="fas fa-key me-1"></i> Change Password', ['change-password'], ['class' => 'btn btn-outline-secondary']) ?>
    <?= \frontend\components\ProfileGate::mustCompleteFirst($user) ? '' : Html::a('<i class="fas fa-bell me-1"></i> Notification Preferences', ['preferences'], ['class' => 'btn btn-outline-secondary']) ?>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <?php $form = ActiveForm::begin(['id' => 'profile-form']); ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3">
                <h2 class="h6 fw-bold mb-0">Account Details</h2>
            </div>
            <div class="card-body">
                <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
                <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'autocomplete' => 'username']) ?>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?= Html::encode($user->email) ?>" readonly />
                    <div class="form-text">Contact support if your account email needs to be changed.</div>
                </div>
                <?= $form->field($model, 'mobile')->textInput(['maxlength' => true, 'placeholder' => '919876543210'])->hint('Enter mobile number with country code.') ?>
                <?= $form->field($model, 'whatsapp')->textInput(['maxlength' => true, 'placeholder' => '919876543210'])->hint('Enter WhatsApp number with country code.') ?>
            </div>
            <div class="card-footer bg-transparent py-3">
                <?= Html::submitButton('Save Profile', ['class' => 'btn btn-primary px-4']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
    <div class="col-xl-5">
        <?php $billingForm = ActiveForm::begin(['id' => 'billing-form']); ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold mb-0">Billing Details</h2>
                <?php if (!$billingProfile->isNewRecord): ?>
                    <?php if ((bool) $billingProfile->is_gst_applicable): ?>
                        <span class="badge bg-success-subtle text-success-emphasis">GST Applicable</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">GST Not Applicable</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($billingProfile->isNewRecord): ?>
                    <div class="alert alert-warning">Add your billing details so we can issue your invoices.</div>
                <?php endif; ?>
                <?= $billingForm->errorSummary($billingProfile, ['class' => 'alert alert-danger']) ?>
                <?= $billingForm->field($billingProfile, 'legal_name')->textInput(['maxlength' => true, 'placeholder' => 'Legal / company name']) ?>
                <?= $billingForm->field($billingProfile, 'address_line_1')->textInput(['maxlength' => true]) ?>
                <?= $billingForm->field($billingProfile, 'address_line_2')->textInput(['maxlength' => true]) ?>
                <?= $billingForm->field($billingProfile, 'country')
                    ->dropDownList(CustomerBillingProfile::countryOptions())
                    ->hint('Invoices to customers outside India carry no GST. We review every change to billing details.') ?>
                <div class="row gx-3">
                    <div class="col-sm-6">
                        <?= $billingForm->field($billingProfile, 'city')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $billingForm->field($billingProfile, 'postal_code')->textInput(['maxlength' => 20]) ?>
                    </div>
                </div>
                <?= $billingForm->field($billingProfile, 'state')->dropDownList(CustomerBillingProfile::stateOptions(), ['prompt' => 'Select state']) ?>
                <?= $billingForm->field($billingProfile, 'gstin')->textInput(['maxlength' => 15, 'class' => 'form-control font-monospace text-uppercase'])->label('GSTIN <span class="text-body-secondary fw-normal">(optional)</span>') ?>
                <?= $billingForm->field($billingProfile, 'pan')->textInput(['maxlength' => 10, 'class' => 'form-control font-monospace text-uppercase'])->label('PAN <span class="text-body-secondary fw-normal">(optional)</span>') ?>
                <?= $this->render('@common/views/_billing_country_js') ?>
                <div class="form-text">Changes apply to future invoices only; invoices already issued keep the details they were issued with.</div>
            </div>
            <div class="card-footer bg-transparent py-3">
                <?= Html::submitButton('Save Billing Details', ['class' => 'btn btn-primary px-4']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
