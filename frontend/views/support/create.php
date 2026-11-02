<?php

declare(strict_types=1);

use common\models\SupportTicket;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Json;

/** @var SupportTicket $model */
$this->title = 'Create Support Request';

$this->params['breadcrumbs'][] = ['label' => 'Support', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Create Support Ticket</h1>
    <div class="text-body-secondary">Tell us what you need help with and we'll get back to you.</div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?php $form = ActiveForm::begin(['id' => 'support-ticket-form']); ?>
        <div class="row g-md-3">
            <div class="col-md-6">
                <?= $form->field($model, 'service_type')->dropDownList(
                    [
                        '' => 'General Support',
                        'domain' => 'Domain',
                        'hosting' => 'Hosting',
                        'maintenance' => 'Maintenance',
                    ],
                    ['id' => 'service-type']
                ) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'service_id')->dropDownList([], ['prompt' => 'Select Service', 'id' => 'service-id']) ?>
            </div>
        </div>
        <?= $form->field($model, 'priority')->dropDownList(SupportTicket::getPriorities()) ?>
        <?= $form->field($model, 'subject')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'message')->textarea(['rows' => 8]) ?>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <?= Html::submitButton('<i class="fas fa-paper-plane me-1"></i> Submit Ticket', ['class' => 'btn btn-primary', 'data' => ['disable-with' => 'Submitting...']]) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$this->registerJs('
    const services = ' . Json::htmlEncode($services) . ';
    $("#service-type").on("change", function () {
        const type = $(this).val();
        const serviceSelect = $("#service-id");
        serviceSelect.empty();
        serviceSelect.append($("<option>").val("").text("Select Service"));
        if (!type || !services[type]) {
            return;
        }
        $.each(
            services[type],
            function(id, label) {
                serviceSelect.append($("<option>").val(id).text(label));
            }
        );
    });
    $("#service-type").trigger("change");
');
?>
