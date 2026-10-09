<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\User $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6"><?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?></div>
        <div class="col-md-6"><?= $form->field($model, 'email')->input('email') ?></div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="form-group field-user-password_input required">
                <label class="control-label">Account Password</label>
                <?= Html::input('password', 'password_input', '', ['class' => 'form-control', 'required' => true]) ?>
            </div>
        </div>
        <div class="col-md-6">
            <!-- Checking your existing schema variable column settings -->
            <?=
            $form->field($model, 'role')->dropDownList([
                'Client' => 'Standard Client / Customer',
                'Superadmin' => 'Administrative Controller'
                    ], ['class' => 'form-control custom-select'])
            ?>
        </div>
    </div>

    <div class="form-group mt-4 text-right">
    <?= Html::submitButton('<i class="fas fa-save mr-1"></i> Register System Account', ['class' => 'btn btn-success px-4']) ?>
    </div>

<?php ActiveForm::end(); ?>

</div>
