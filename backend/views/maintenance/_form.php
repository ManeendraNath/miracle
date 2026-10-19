<?php
declare(strict_types=1);

use common\models\Maintenance;
use common\models\User;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$isNew = $model->isNewRecord;

$users = ArrayHelper::map(
        User::find()
                ->select([
                    'id',
                    'username',
                    'email',
                ])
                ->orderBy([
                    'username' => SORT_ASC,
                ])
                ->asArray()
                ->all(),
        'id',
        static function (
                array $user
        ): string {
            return $user['username']
                    . (
                    !empty($user['email']) ? ' (' . $user['email'] . ')' : ''
            );
        }
);

$form = ActiveForm::begin([
    'id' => 'maintenance-form',
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

    <div class="card-header">
        <strong>
            Maintenance Contract
        </strong>
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
                    'data' =>
                    $users,
                    'options' => [
                        'placeholder' =>
                        'Select Client',
                    ],
                    'pluginOptions' => [
                        'allowClear' =>
                        false,
                        'width' =>
                        '100%',
                    ],
                ]
        )
?>

        <?=
                $form
                ->field(
                        $model,
                        'website_url'
                )
                ->textInput([
                    'maxlength' => true,
                    'placeholder' =>
                    'https://example.com',
                ])
        ?>

        <div class="row">

            <div class="col-md-6">

                <?=
                        $form
                        ->field(
                                $model,
                                'start_date'
                        )
                        ->input(
                                'date'
                        )
                ?>

            </div>

            <div class="col-md-6">

                <?=
                        $form
                        ->field(
                                $model,
                                'current_expiry_date'
                        )
                        ->input(
                                'date'
                        )
                ?>

            </div>

        </div>

        <?=
                $form
                ->field(
                        $model,
                        'status'
                )
                ->dropDownList(
                        Maintenance::getStatuses()
                )
        ?>

        <hr>

        <h6 class="fw-bold">
            Renewal Billing
        </h6>

        <div class="text-muted mb-3">
            Leave cycle and increase empty to use global billing settings.
        </div>

        <?=
                $form
                ->field(
                        $model,
                        'renewal_base_amount'
                )
                ->input(
                        'number',
                        [
                            'min' => 0,
                            'step' => '0.01',
                            'placeholder' =>
                            'Initial renewal amount',
                        ]
                )
        ?>

        <div class="row">

            <div class="col-md-6">

                <?=
                        $form
                        ->field(
                                $model,
                                'renewal_cycle_months'
                        )
                        ->input(
                                'number',
                                [
                                    'min' => 1,
                                    'placeholder' =>
                                    'Use global setting',
                                ]
                        )
                ?>

            </div>

            <div class="col-md-6">

                <?=
                        $form
                        ->field(
                                $model,
                                'renewal_increase_percent'
                        )
                        ->input(
                                'number',
                                [
                                    'min' => 0,
                                    'max' => 100,
                                    'step' => '0.01',
                                    'placeholder' =>
                                    'Use global setting',
                                ]
                        )
                ?>

            </div>

        </div>

    </div>

    <div class="card-footer">

        <?=
        Html::a(
                'Cancel',
                $isNew ? ['index'] : [
                    'view',
                    'id' => $model->id,
                        ],
                [
                    'class' =>
                    'btn btn-outline-secondary',
                ]
        )
        ?>

<?=
Html::submitButton(
        $isNew ? 'Save Maintenance' : 'Update Maintenance',
        [
            'class' =>
            'btn btn-primary px-4',
        ]
)
?>

    </div>

</div>

<?php ActiveForm::end(); ?>