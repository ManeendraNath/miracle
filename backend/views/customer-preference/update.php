<?php

use common\models\CustomerPreference;
use common\models\User;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var CustomerPreference $model
 * @var User $user
 */
$this->title = 'Notification Preferences';

$this->params['breadcrumbs'][] = [
    'label' => 'Notification Preferences',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $user->username;
?>

<div class="customer-preference-update">

    <div class="row">

        <div class="col-lg-8">

            <div class="card card-outline card-primary shadow-sm">

                <div class="card-header">

                    <h3 class="card-title">
                        Notification Preferences
                    </h3>

                </div>

                <div class="card-body">

                    <div class="mb-4">

                        <div class="fw-semibold">
<?=
Html::encode(
        $user->username
)
?>
                        </div>

                        <div class="text-muted small">
                            <?=
                            Html::encode(
                                    $user->email
                            )
                            ?>
                        </div>

                    </div>

<?php $form = ActiveForm::begin(); ?>

                    <div class="border rounded p-3 mb-3">

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

                        <div class="text-muted small">
                            Receive reminders relating
                            to service renewals.
                        </div>

                    </div>

                    <div class="border rounded p-3 mb-3">

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

                        <div class="text-muted small">
                            Receive invoice payment and
                            overdue reminders.
                        </div>

                    </div>

                    <div class="border rounded p-3 mb-3">

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

                        <div class="text-muted small">
                            Receive notifications for
                            upcoming service expiries.
                        </div>

                    </div>

                    <div class="border rounded p-3 mb-4">

                        <?=
                                $form
                                ->field(
                                        $model,
                                        'support_notifications'
                                )
                                ->checkbox([
                                    'label' =>
                                    'Support notifications',
                                ])
                        ?>

                        <div class="text-muted small">
                            Receive notifications when
                            support requests are updated.
                        </div>

                    </div>

                    <div class="d-flex gap-2">

                        <?=
                        Html::submitButton(
                                'Save Preferences',
                                [
                                    'class' =>
                                    'btn btn-primary',
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

<?php ActiveForm::end(); ?>

                </div>

            </div>

        </div>

    </div>

</div>