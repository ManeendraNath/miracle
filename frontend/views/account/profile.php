<?php

declare(strict_types=1);

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'My Profile';
$this->params['breadcrumbs'][] = $this->title;

$form = ActiveForm::begin(['id' => 'profile-form']);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-end gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">My Profile</h1>
        <div class="text-body-secondary">Manage your customer account and review billing information.</div>
    </div>
    <?= Html::a('<i class="fas fa-key me-1"></i> Change Password', ['change-password'], ['class' => 'btn btn-outline-secondary']) ?>
</div>

<div class="row g-4">
    <div class="col-xl-7">
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
    </div>
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3">
                <h2 class="h6 fw-bold mb-0">Billing Details</h2>
            </div>
            <div class="card-body">
                <?php if ($billingProfile !== null): ?>
                    <?php
                    $address = implode(', ', array_filter([
                        $billingProfile->address_line_1,
                        $billingProfile->address_line_2,
                        $billingProfile->city,
                        $billingProfile->state,
                        $billingProfile->postal_code,
                    ]));
                    ?>
                    <div class="mb-3">
                        <div class="small text-uppercase text-body-secondary fw-semibold mb-1">Legal Name</div>
                        <div class="fw-semibold text-break"><?= Html::encode($billingProfile->legal_name) ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="small text-uppercase text-body-secondary fw-semibold mb-1">Billing Address</div>
                        <div class="text-break"><?= Html::encode($address) ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="small text-uppercase text-body-secondary fw-semibold mb-1">GST</div>
                        <?php if ((bool) $billingProfile->is_gst_applicable): ?>
                            <span class="badge bg-success-subtle text-success-emphasis">Applicable</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">Not Applicable</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($billingProfile->gstin)): ?>
                        <div class="mb-3">
                            <div class="small text-uppercase text-body-secondary fw-semibold mb-1">GSTIN</div>
                            <div class="font-monospace"><?= Html::encode($billingProfile->gstin) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($billingProfile->pan)): ?>
                        <div>
                            <div class="small text-uppercase text-body-secondary fw-semibold mb-1">PAN</div>
                            <div class="font-monospace"><?= Html::encode($billingProfile->pan) ?></div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">
                        Billing profile information has not been configured yet.
                        Please contact support before your next invoice is generated.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>
