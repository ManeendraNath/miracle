<?php
declare(strict_types=1);

use common\models\SupportTicket;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Create Support Request';

$this->params['breadcrumbs'][] = [
    'label' =>
    'Support',
    'url' => [
        'index',
    ],
];

$this->params['breadcrumbs'][] = $this->title;

$form = ActiveForm::begin([
    'id' =>
    'support-ticket-form',
        ]);
?>

<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card shadow-sm">

                <div class="card-header">

                    <strong>
                        Create Support Request
                    </strong>

                </div>

                <div class="card-body">

<?=
$form->errorSummary(
        $model,
        [
            'class' =>
            'alert alert-danger',
        ]
)
?>

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'service_type'
                            )
                            ->dropDownList(
                                    [
                                        'domain' =>
                                        'Domain',
                                        'hosting' =>
                                        'Hosting',
                                        'maintenance' =>
                                        'Maintenance',
                                    ],
                                    [
                                        'prompt' =>
                                        'General Support',
                                    ]
                            )
                    ?>

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'service_id'
                            )
                            ->input(
                                    'number',
                                    [
                                        'min' => 1,
                                        'placeholder' =>
                                        'Optional service ID',
                                    ]
                            )
                    ?>

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'subject'
                            )
                            ->textInput([
                                'maxlength' =>
                                true,
                                'placeholder' =>
                                'How can we help?',
                            ])
                    ?>

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'priority'
                            )
                            ->dropDownList([
                                SupportTicket::PRIORITY_LOW =>
                                'Low',
                                SupportTicket::PRIORITY_NORMAL =>
                                'Normal',
                                SupportTicket::PRIORITY_HIGH =>
                                'High',
                            ])
                    ?>

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'message'
                            )
                            ->textarea([
                                'rows' => 7,
                                'placeholder' =>
                                'Describe the issue in detail...',
                            ])
                    ?>

                </div>

                <div class="card-footer">

                    <?=
                    Html::submitButton(
                            'Submit Request',
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

        </div>

    </div>

</div>

<?php ActiveForm::end(); ?>