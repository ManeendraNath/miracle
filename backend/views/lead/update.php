<?php

declare(strict_types=1);

use common\models\Lead;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Lead $model */

$this->title = 'Update Lead: ' . $model->lead_name;
$this->params['breadcrumbs'][] = ['label' => 'Leads', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->lead_name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Move the lead to its current stage.';
$this->params['help'] = [
    'Status' => 'Pending Review (new), In Progress (you are talking to them), Completed, Rejected, or Spam. Only the status can be changed here.',
    'Save' => 'Saves the status and goes back to the lead. Nothing is sent to the person.',
];
?>
<div class="lead-update">
    <div class="card card-outline card-primary shadow-sm" style="max-width: 600px;">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>

            <?php // Keep an older status that's not in the list selectable, so saving doesn't silently reset it. ?>
            <?= $form->field($model, 'status')->dropDownList(Lead::getStatuses() + [$model->status => $model->status]) ?>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <?= Html::a('Cancel', ['view', 'id' => $model->id], ['class' => 'btn btn-light border']) ?>
                <?= Html::submitButton('<i class="fas fa-save me-1"></i> Save', ['class' => 'btn btn-primary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
