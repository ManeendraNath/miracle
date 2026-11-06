<?php

declare(strict_types=1);

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Notification Preferences';

$this->params['breadcrumbs'][] = ['label' => 'My Profile', 'url' => ['profile']];
$this->params['breadcrumbs'][] = $this->title;

$form = ActiveForm::begin();
?>

<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Notification Preferences</h1>
    <div class="text-body-secondary">Choose which optional customer notifications you would like to receive.</div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?= $form->field($model, 'renewal_reminders')->checkbox(['label' => 'Renewal reminders', 'switch' => true]) ?>
                <?= $form->field($model, 'payment_reminders')->checkbox(['label' => 'Payment reminders', 'switch' => true]) ?>
                <?php // service_expiry_notifications isn't offered: no message uses it yet (renewal reminders cover expiry). ?>
                <?= $form->field($model, 'support_notifications')->checkbox(['label' => 'Support request updates', 'switch' => true]) ?>

                <div class="alert alert-light border d-flex gap-2 mb-0">
                    <i class="fas fa-info-circle text-body-secondary mt-1"></i>
                    <div>
                        Essential account and financial messages, such as password resets, reminders to
                        complete your billing details, invoice delivery and payment confirmations, cannot be disabled.
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent py-3">
                <?= Html::submitButton('Save Preferences', ['class' => 'btn btn-primary px-4']) ?>
            </div>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>
