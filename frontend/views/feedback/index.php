<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Testimonial|null $model */
/** @var common\models\Testimonial|null $submitted */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Share Feedback';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 fw-bold mb-2"><?= Html::encode($this->title) ?></h1>

<?php if (isset($submitted)): ?>
    <p class="text-body-secondary"><?= $submitted->is_published ? 'Your feedback is on our website. Thank you.' : 'We have your feedback. We review it before it appears on our website.' ?></p>
    <div class="mw-panel">
        <blockquote class="mb-3"><?= Html::encode($submitted->quote) ?></blockquote>
        <p class="mb-0 fw-semibold"><?= Html::encode($submitted->client_name) ?></p>
        <p class="mb-0 text-body-secondary small"><?= Html::encode($submitted->byline()) ?></p>
    </div>
    <p class="mt-3 mb-0 small text-body-secondary">Need to change or remove it? <?= Html::a('Raise a support ticket', ['/support/index']) ?>.</p>
<?php else: ?>
    <p class="text-body-secondary">Tell other businesses what it was like to work with us. We review feedback before it appears on our website.</p>
    <div class="mw-panel">
        <?php $form = ActiveForm::begin(['id' => 'feedback-form']); ?>
            <?= $form->field($model, 'client_name')->textInput(['maxlength' => 255])->label('Your name') ?>
            <div class="row">
                <div class="col-md-4"><?= $form->field($model, 'role')->textInput(['maxlength' => 255])->label('Your role (optional)') ?></div>
                <div class="col-md-4"><?= $form->field($model, 'company')->textInput(['maxlength' => 255])->label('Company (optional)') ?></div>
                <div class="col-md-4"><?= $form->field($model, 'country')->textInput(['maxlength' => 100])->label('Country (optional)') ?></div>
            </div>
            <?= $form->field($model, 'quote')->textarea(['rows' => 5, 'maxlength' => 1000])->label('Your feedback') ?>
            <?= $form->field($model, 'consent')->checkbox() ?>
            <?= Html::submitButton('Send feedback', ['class' => 'btn btn-primary mt-2']) ?>
        <?php ActiveForm::end(); ?>
    </div>
<?php endif; ?>
