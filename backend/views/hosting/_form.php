<?php

use common\models\Hosting;
use common\models\User;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Hosting $model */
/** @var yii\widgets\ActiveForm $form */
$isNew = $model->isNewRecord;

$users = ArrayHelper::map(
        User::find()
                ->orderBy([
                    'username' => SORT_ASC,
                ])
                ->all(),
        'id',
        static function (User $user): string {
            $label = $user->username;

            if (!empty($user->email)) {
                $label .= ' (' . $user->email . ')';
            }

            return $label;
        }
);
?>

<div class="hosting-form">

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
                <?=
                $isNew ? 'Hosting Details' : 'Update Hosting Details'
                ?>
            </h3>
        </div>

        <div class="card-body">

            <?=
            $form->errorSummary(
                    $model,
                    [
                        'class' => 'alert alert-danger',
                    ]
            )
            ?>

            <div class="mb-3">
                <h6 class="text-primary border-bottom pb-2">
                    Hosting Information
                </h6>
            </div>

            <div class="row">

                <div class="col-md-6">

                    <?=
                            $form
                            ->field($model, 'user_id')
                            ->widget(
                                    Select2::class,
                                    [
                                        'data' => $users,
                                        'options' => [
                                            'placeholder' =>
                                            'Select Client',
                                        ],
                                        'pluginOptions' => [
                                            'allowClear' => true,
                                            'width' => '100%',
                                        ],
                                    ]
                            )
                    ?>

                </div>

                <div class="col-md-6">

                    <?=
                            $form
                            ->field($model, 'primary_domain')
                            ->textInput([
                                'maxlength' => true,
                                'placeholder' => 'example.com',
                            ])
                    ?>

                </div>

            </div>

            <div class="mt-3 mb-3">
                <h6 class="text-primary border-bottom pb-2">
                    cPanel Information
                </h6>
            </div>

            <div class="row">

                <div class="col-md-4">

                    <?=
                            $form
                            ->field($model, 'cpanel_ip')
                            ->textInput([
                                'maxlength' => true,
                                'placeholder' => 'cPanel IP',
                            ])
                    ?>

                </div>

                <div class="col-md-4">

<?=
        $form
        ->field($model, 'cpanel_username')
        ->textInput([
            'maxlength' => true,
            'autocomplete' => 'off',
        ])
?>

                </div>

                <div class="col-md-4">

<?=
        $form
        ->field($model, 'cpanel_password')
        ->passwordInput([
            'maxlength' => true,
            'autocomplete' => 'new-password',
        ])
?>

                </div>

            </div>

            <div class="mt-3 mb-3">
                <h6 class="text-primary border-bottom pb-2">
                    Renewal Information
                </h6>
            </div>

            <div class="row">

                <div class="col-md-4">

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'current_expiry_date'
                            )
                            ->input('date')
                    ?>

                </div>

                <div class="col-md-4">

                    <?=
                            $form
                            ->field($model, 'status')
                            ->dropDownList(
                                    Hosting::getStatuses()
                            )
                    ?>

                </div>

                <div class="col-md-4">

<?=
        $form
        ->field(
                $model,
                'renewal_base_amount'
        )
        ->textInput([
            'type' => 'number',
            'min' => 0,
            'step' => '0.01',
            'placeholder' =>
            'Initial renewal amount',
        ])
?>

                </div>

            </div>

            <div class="mt-3 mb-3">

                <h6 class="text-primary border-bottom pb-2">
                    Billing Override
                </h6>

                <small class="text-muted">
                    Leave empty to use the global Hosting billing
                    settings.
                </small>

            </div>

            <div class="row mt-3">

                <div class="col-md-6">

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'renewal_cycle_months'
                            )
                            ->textInput([
                                'type' => 'number',
                                'min' => 1,
                                'placeholder' =>
                                'Use global setting',
                            ])
                    ?>

                </div>

                <div class="col-md-6">

<?=
        $form
        ->field(
                $model,
                'renewal_increase_percent'
        )
        ->textInput([
            'type' => 'number',
            'min' => 0,
            'max' => 100,
            'step' => '0.01',
            'placeholder' =>
            'Use global setting',
        ])
?>

                </div>

            </div>

        </div>

        <div
            class="card-footer d-flex justify-content-between align-items-center"
            >

            <?=
            Html::a(
                    '<i class="fas fa-arrow-left me-1"></i> Cancel',
                    $isNew ? ['index'] : [
                        'view',
                        'id' => $model->id,
                            ],
                    [
                        'class' => 'btn btn-outline-secondary',
                    ]
            )
            ?>

<?=
Html::submitButton(
        $isNew ? '<i class="fas fa-save me-1"></i> Save Hosting' : '<i class="fas fa-save me-1"></i> Update Hosting',
        [
            'class' => 'btn btn-primary px-4',
        ]
)
?>

        </div>

    </div>

<?php ActiveForm::end(); ?>

</div>