<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Admin $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="admin-form">

    <?php $form = ActiveForm::begin(['id' => 'admin-profile-management-panel']); ?>

    <div class="row">
        <div class="col-md-6">
            <?=
            $form->field($model, 'username')->textInput([
                'readonly' => true,
                'class' => 'form-control bg-light font-weight-bold'
            ])
            ?>
        </div>
        <div class="col-md-6">
<?= $form->field($model, 'email')->input('email') ?>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <?=
            $form->field($model, 'role')->textInput([
                'readonly' => true,
                'class' => 'form-control bg-light text-uppercase font-weight-bold text-danger'
            ])
            ?>
        </div>
        <div class="col-md-6">
            <div class="form-group field-admin-new-password">
                <label class="control-label font-weight-bold text-dark">Update Password Key</label>
<?=
Html::input('password', 'new_password_string', '', [
    'class' => 'form-control',
    'placeholder' => 'Leave completely blank to retain current secure password'
])
?>
                <small class="text-muted text-xs d-block mt-1">Passwords are hashed using high-level native cryptographic algorithms automatically on save.</small>
            </div>
        </div>
    </div>

    <div class="form-group mt-4 text-right border-top pt-3">
<?= Html::submitButton('<i class="fas fa-check-circle mr-1"></i> Update Account Parameters', ['class' => 'btn btn-lg btn-danger px-4 shadow-sm']) ?>
    </div>

<?php ActiveForm::end(); ?>

</div>
