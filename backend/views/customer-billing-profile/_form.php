<?php
declare(strict_types=1);

use kartik\select2\Select2;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin([
    'id' => 'customer-billing-profile-form',
        ]);
?>

<?=
$form->errorSummary(
        $model,
        [
            'class' => 'alert alert-danger',
        ]
)
?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <strong>Customer Billing Identity</strong>
    </div>

    <div class="card-body">

        <?=
                $form
                ->field(
                        $model,
                        'user_id'
                )
                ->widget(
                        Select2::class,
                        [
                            'data' => $usersList,
                            'options' => [
                                'placeholder' =>
                                'Select Client',
                            ],
                            'pluginOptions' => [
                                'allowClear' => false,
                                'width' => '100%',
                            ],
                        ]
                )
        ?>

        <?=
                $form
                ->field(
                        $model,
                        'legal_name'
                )
                ->textInput([
                    'maxlength' => true,
                    'placeholder' =>
                    'Legal / Company Name',
                ])
        ?>

        <div class="row">
            <div class="col-md-6">
                <?=
                        $form
                        ->field(
                                $model,
                                'address_line_1'
                        )
                        ->textInput([
                            'maxlength' => true,
                        ])
                ?>
            </div>

            <div class="col-md-6">
                <?=
                        $form
                        ->field(
                                $model,
                                'address_line_2'
                        )
                        ->textInput([
                            'maxlength' => true,
                        ])
                ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
<?=
        $form
        ->field(
                $model,
                'city'
        )
        ->textInput([
            'maxlength' => true,
        ])
?>
            </div>

            <div class="col-md-4">
                <?=
                        $form
                        ->field(
                                $model,
                                'state'
                        )
                        ->textInput([
                            'maxlength' => true,
                        ])
                ?>
            </div>

            <div class="col-md-4">
        <?=
                $form
                ->field(
                        $model,
                        'state_code'
                )
                ->textInput([
                    'maxlength' => true,
                    'placeholder' =>
                    'e.g. 07',
                ])
        ?>
            </div>
        </div>

                <?=
                        $form
                        ->field(
                                $model,
                                'postal_code'
                        )
                        ->textInput([
                            'maxlength' => true,
                        ])
                ?>

        <div class="row">
            <div class="col-md-6">
                <?=
                        $form
                        ->field(
                                $model,
                                'gstin'
                        )
                        ->textInput([
                            'maxlength' => true,
                        ])
                ?>
            </div>

            <div class="col-md-6">
        <?=
                $form
                ->field(
                        $model,
                        'pan'
                )
                ->textInput([
                    'maxlength' => true,
                ])
        ?>
            </div>
        </div>

    </div>

    <div class="card-footer">
<?=
Html::submitButton(
        'Save Billing Profile',
        [
            'class' =>
            'btn btn-primary px-4',
        ]
)
?>

<?=
Html::a(
        'Cancel',
        ['index'],
        [
            'class' =>
            'btn btn-outline-secondary',
        ]
)
?>
    </div>
</div>

<?php ActiveForm::end(); ?>