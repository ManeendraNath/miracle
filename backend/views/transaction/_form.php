<?php
declare(strict_types=1);

use common\models\Transaction;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin([
    'id' =>
    'transaction-form',
        ]);
?>

<?=
$form->errorSummary(
        $model,
        [
            'class' =>
            'alert alert-danger',
        ]
)
?>

<div class="card shadow-sm">

    <div class="card-header bg-light">
        <strong>Payment Transaction</strong>
    </div>

    <div class="card-body">

        <?=
                $form
                ->field(
                        $model,
                        'invoice_id'
                )
                ->input(
                        'number',
                        [
                            'min' => 1,
                        ]
                )
        ?>

        <?=
                $form
                ->field(
                        $model,
                        'gateway_id'
                )
                ->dropDownList(
                        [
                            2 =>
                            'Manual UPI / Bank Transfer',
                        ],
                        [
                            'prompt' =>
                            'Select Payment Method',
                        ]
                )
        ?>

        <?=
                $form
                ->field(
                        $model,
                        'gateway_payment_id'
                )
                ->textInput([
                    'maxlength' => true,
                    'placeholder' =>
                    'UTR / Payment Reference Number',
                ])
        ?>

        <?=
                $form
                ->field(
                        $model,
                        'amount'
                )
                ->input(
                        'number',
                        [
                            'min' => '0.01',
                            'step' => '0.01',
                        ]
                )
        ?>

        <?=
                $form
                ->field(
                        $model,
                        'user_payment_notes'
                )
                ->textarea([
                    'rows' => 4,
                    'placeholder' =>
                    'Optional payment notes',
                ])
        ?>

    </div>

    <div class="card-footer">

        <?=
        Html::submitButton(
                $model->isNewRecord ? 'Create Pending Transaction' : 'Update Transaction',
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