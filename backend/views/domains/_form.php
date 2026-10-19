<?php

use common\models\User;
use common\models\Domains;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Domains $model */
/** @var yii\widgets\ActiveForm $form */
$isNew = $model->isNewRecord;

/**
 * User list for Domain ownership.
 *
 * Key   = user.id
 * Value = username (email)
 */
$users = ArrayHelper::map(
        User::find()
                ->orderBy([
                    'username' => SORT_ASC,
                ])
                ->all(),
        'id',
        static function (User $user): string {
            return $user->username
                    . ($user->email ? ' (' . $user->email . ')' : '');
        }
);
?>

<div class="domains-form">

    <?php
    $form = ActiveForm::begin([
        'options' => [
            'autocomplete' => 'off',
        ],
    ]);
    ?>

    <div class="card card-outline card-primary shadow-sm">

        <div class="card-header">
            <h3 class="card-title mb-0">
                <?= $isNew ? 'Domain Details' : 'Update Domain Details' ?>
            </h3>
        </div>

        <div class="card-body">
            <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
            <!-- Client / Domain -->
            <div class="row">
                <div class="col-md-6">
                    <?=
                            $form->field($model, 'user_id')
                            ->widget(Select2::class, [
                                'data' => $users,
                                'options' => [
                                    'placeholder' => 'Select Client',
                                ],
                                'pluginOptions' => [
                                    'allowClear' => true,
                                ],
                            ])
                    ?>
                </div>
                <div class="col-md-6">
                    <?=
                            $form->field($model, 'domain_url')
                            ->textInput([
                                'maxlength' => true,
                                'class' => 'form-control',
                                'placeholder' => 'example.com',
                            ])
                    ?>
                </div>

            </div>

            <!-- Registrar / Status -->
            <div class="row">

                <div class="col-md-6">
                    <?=
                    $form->field($model, 'registrar')->textInput([
                        'maxlength' => true,
                        'class' => 'form-control',
                        'placeholder' => 'e.g. GoDaddy, Namecheap, Cloudflare',
                    ])
                    ?>
                </div>

                <div class="col-md-6">
                    <?=
                    $form->field($model, 'status')->dropDownList(Domains::getStatuses(),
                            [
                                'prompt' => 'Select Status',
                                'class' => 'form-control',
                            ]
                    )
                    ?>
                </div>

            </div>

            <!-- Dates -->
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'registered_date')->input('date') ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'current_expiry_date')->input('date') ?>
                </div>
            </div>

            <div class="mt-4 mb-3">
                <h6 class="text-primary border-bottom pb-2">
                    Billing Override
                </h6>

                <small class="text-muted">
                    Leave these fields empty to use the global billing settings.
                </small>
            </div>

            <div class="row mt-3">

                <div class="col-md-6">
                    <?= $form->field($model, 'renewal_cycle_months')->textInput(['type' => 'number', 'min' => 1, 'placeholder' => 'Use global setting']) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'renewal_increase_percent')->textInput(['type' => 'number', 'min' => 0, 'max' => 100, 'step' => '0.01', 'placeholder' => 'Use global setting']) ?>
                </div>

            </div>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <?=
            Html::a(
                    '<i class="fas fa-arrow-left me-1"></i> Cancel',
                    $isNew ? ['index'] : ['view', 'id' => $model->id],
                    ['class' => 'btn btn-outline-secondary']
            )
            ?>
            <?=
            Html::submitButton(
                    $isNew ? '<i class="fas fa-save me-1"></i> Save Domain' : '<i class="fas fa-save me-1"></i> Update Domain',
                    ['class' => 'btn btn-primary px-4']
            )
            ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>