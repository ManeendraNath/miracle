<?php

use common\models\Admin;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Admin $model */
/** @var yii\widgets\ActiveForm $form */

$roles = Admin::getRoles();
?>

<div class="admin-form">

    <?php $form = ActiveForm::begin(['id' => 'admin-profile-management-panel']); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'autocomplete' => 'off']) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'email')->input('email') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'role')->dropDownList($roles)->hint('Manager: customers, services, support, leads. Admin: + billing, payments, coupons. Superadmin: + admin accounts and site settings.') ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'status')->dropDownList(Admin::getStatuses()) ?>
        </div>
        <div class="col-md-4">
            <?=
            $form->field($model, 'password')->passwordInput([
                'autocomplete' => 'new-password',
                'placeholder' => $model->isNewRecord ? '' : 'Leave blank to keep current password',
            ])->hint('Minimum 8 characters.')
            ?>
        </div>
    </div>

    <div class="form-group text-right border-top pt-3">
        <?= Html::submitButton('<i class="fas fa-check-circle mr-1"></i> Save', ['class' => 'btn btn-primary px-4']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
