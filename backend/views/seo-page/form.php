<?php

declare(strict_types=1);

use common\models\SeoPage;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\SeoPage $model */

$this->title = 'SEO: ' . SeoPage::pages()[$model->route];
$this->params['breadcrumbs'][] = ['label' => 'SEO', 'url' => ['index']];
$this->params['breadcrumbs'][] = SeoPage::pages()[$model->route];
$this->params['help'] = [
    'Title' => 'Up to 70 characters; about 60 show in full. Put the words people search for first, e.g. "Custom Web Application Development". The site name is added after it when the whole still fits in 60 characters.',
    'Description' => 'Up to 170 characters; about 155 show. One or two sentences saying what the page offers and who it is for. Search engines may show their own extract instead.',
    'Empty' => 'Leave a box empty to use the page\'s built-in text.',
];
$this->registerJs(<<<'JS'
document.querySelectorAll('[data-count]').forEach((input) => {
    const out = document.getElementById(input.dataset.count);
    const show = () => { out.textContent = input.value.length + ' / ' + input.maxLength; };
    input.addEventListener('input', show);
    show();
});
JS);
?>
<div class="card card-outline card-primary shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'title')->textInput(['maxlength' => 70, 'data-count' => 'seo-title-count', 'placeholder' => 'Built-in title'])->hint('<span id="seo-title-count"></span>') ?>
        <?= $form->field($model, 'description')->textarea(['maxlength' => 170, 'rows' => 3, 'data-count' => 'seo-description-count', 'placeholder' => 'Built-in description'])->hint('<span id="seo-description-count"></span>') ?>
        <?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-light border ms-2']) ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
