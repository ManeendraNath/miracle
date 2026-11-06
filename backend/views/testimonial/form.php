<?php

declare(strict_types=1);

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Testimonial $model */

$this->title = $model->isNewRecord ? 'Add Testimonial' : 'Update Testimonial';
$this->params['breadcrumbs'][] = ['label' => 'Testimonials', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="testimonial-form">
    <div class="card card-outline card-primary shadow-sm" style="max-width: 760px;">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>
            <?= $form->field($model, 'client_name')->textInput(['maxlength' => 255]) ?>
            <div class="row">
                <div class="col-md-4"><?= $form->field($model, 'role')->textInput(['maxlength' => 255]) ?></div>
                <div class="col-md-4"><?= $form->field($model, 'company')->textInput(['maxlength' => 255]) ?></div>
                <div class="col-md-4"><?= $form->field($model, 'country')->textInput(['maxlength' => 100]) ?></div>
            </div>
            <?= $form->field($model, 'quote')->textarea(['rows' => 5, 'maxlength' => 1000])->hint('The client\'s own words, with their permission to publish them.') ?>
            <?= $form->field($model, 'sort_order')->textInput(['type' => 'number'])->hint('Lower numbers show first. The home page shows up to six.') ?>
            <?= $form->field($model, 'is_published')->checkbox() ?>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-light border']) ?>
                <?= Html::submitButton('<i class="fas fa-save me-1"></i> Save', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
