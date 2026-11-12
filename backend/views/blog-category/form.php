<?php

declare(strict_types=1);

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\BlogCategory $model */

$this->title = $model->isNewRecord ? 'Add Category' : 'Edit Category';
$this->params['breadcrumbs'][] = ['label' => 'Blog Categories', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Name' => 'Shown on the blog, e.g. Web Development.',
    'Web address' => 'Made from the name. Changing it later breaks links to the old category page.',
];
?>
<div class="card card-outline card-primary shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'name')->textInput(['maxlength' => 100]) ?>
        <?= $form->field($model, 'slug', ['enableClientValidation' => false])->textInput(['maxlength' => 120, 'placeholder' => 'Made from the name']) ?>
        <?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-light border ms-2']) ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
