<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var common\models\AuditRequest $model */

$this->title = 'Modify System Intake Pipeline State: Lead #' . $model->id;
?>
<div class="lead-update container py-4" style="font-family: 'Inter', system-ui, sans-serif;">

    <div class="mb-3">
        <?= Html::a('Cancel & Exit', ['view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 0.75rem; max-width: 600px;">
        <h3 class="fw-bold text-dark mb-2">🎭 Transition Pipeline State</h3>
        <p class="text-muted small border-bottom pb-3 mb-4">Client: <strong><?= Html::encode($model->name) ?></strong></p>

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'status')->dropDownList([
            'Pending' => 'Pending Review',
            'In Progress' => 'Audit Processing / Environment Provisioning',
            'Completed' => 'Completed / Invoices Delivered',
            'Rejected' => 'Rejected / Disqualified'
        ], ['class' => 'form-select py-2'])->label('Assign Target Pipeline Stage') ?>

        <div class="form-group mt-4 text-end">
            <?= Html::submitButton('Update State Code ✓', [
                'class' => 'btn text-white fw-bold px-4 py-2',
                'style' => 'background-color: #00a3e0; border: none; border-radius: 0.5rem;'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
