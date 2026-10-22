<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Notification Preferences';

$this->params['breadcrumbs'][] = [
    'label' =>
    'My Profile',
    'url' => [
        'profile',
    ],
];

$this->params['breadcrumbs'][] = $this->title;

$form = ActiveForm::begin();
?>

<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="card shadow-sm">

                <div class="card-header">

                    <strong>
                        Notification Preferences
                    </strong>

                </div>

                <div class="card-body">

                    <p class="text-muted">
                        Choose which optional customer
                        notifications you would like to receive.
                    </p>

<?=
        $form
        ->field(
                $model,
                'renewal_reminders'
        )
        ->checkbox([
            'label' =>
            'Renewal reminders',
        ])
?>

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'payment_reminders'
                            )
                            ->checkbox([
                                'label' =>
                                'Payment reminders',
                            ])
                    ?>

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'service_expiry_notifications'
                            )
                            ->checkbox([
                                'label' =>
                                'Service expiry notifications',
                            ])
                    ?>

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'support_notifications'
                            )
                            ->checkbox([
                                'label' =>
                                'Support request updates',
                            ])
                    ?>

                    <div class="
                         alert
                         alert-light
                         border
                         mb-0
                         ">

                        Essential account and financial
                        messages, such as password resets,
                        invoice delivery and payment
                        confirmations, cannot be disabled.

                    </div>

                </div>

                <div class="card-footer">

                    <?=
                    Html::submitButton(
                            'Save Preferences',
                            [
                                'class' =>
                                'btn btn-primary px-4',
                            ]
                    )
                    ?>

                </div>

            </div>

        </div>

    </div>

</div>

<?php ActiveForm::end(); ?>