<?php

use common\models\User;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\User $model */
/** @var yii\widgets\ActiveForm $form */
$isNewRecord = $model->isNewRecord;
?>

<div class="user-form">

    <?php
    $form = ActiveForm::begin([
        'id' => 'user-form',
        'options' => [
            'class' => 'user-admin-form',
        ],
        'enableClientValidation' => true,
        'validateOnBlur' => true,
    ]);
    ?>

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-1">
                <?= $isNewRecord ? 'Create User' : 'Update User' ?>
            </h5>

            <div class="text-muted">
                <?= $isNewRecord ? 'Create a new client account.' : 'Update account information and access settings.' ?>
            </div>
        </div>

        <div class="card-body p-4">

            <?= $form->errorSummary($model, ['class' => 'alert alert-danger mb-4']) ?>

            <div class="row g-3">

                <div class="col-md-6">
                    <?=
                    $form->field($model, 'username')->textInput([
                        'maxlength' => true,
                        'class' => 'form-control',
                        'placeholder' => 'Enter username',
                        'autocomplete' => 'off',
                    ])
                    ?>
                </div>

                <div class="col-md-6">
                    <?=
                    $form->field($model, 'email')->input('email', [
                        'maxlength' => true,
                        'class' => 'form-control',
                        'placeholder' => 'Enter email address',
                        'autocomplete' => 'off',
                    ])
                    ?>
                </div>

                <div class="col-md-6">
                    <?=
                            $form->field($model, 'password')
                            ->passwordInput([
                                'maxlength' => true,
                                'class' => 'form-control',
                                'placeholder' => $isNewRecord ? 'Enter password' : 'Leave blank to keep current password',
                                'autocomplete' => 'new-password',
                            ])
                            ->hint($isNewRecord ? 'A minimum of 8 characters is required.' : 'Leave blank to preserve the existing password.')
                    ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'status')->dropDownList(User::getStatuses(), ['class' => 'form-control']) ?>
                </div>

            </div>

        </div>

        <div class="card-footer bg-white border-top py-3">
            <div class="d-flex justify-content-end gap-2">
                <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-light border']) ?>&nbsp;
                <?= Html::submitButton($isNewRecord ? 'Create User' : 'Save Changes', ['class' => 'btn btn-primary px-4']) ?>
            </div>
        </div>

    </div>

    <?php ActiveForm::end(); ?>

</div>