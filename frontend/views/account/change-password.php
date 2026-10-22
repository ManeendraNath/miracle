<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Change Password';

$this->params['breadcrumbs'][] = [
    'label' =>
    'My Profile',
    'url' => [
        'profile',
    ],
];

$this->params['breadcrumbs'][] = $this->title;

$form = ActiveForm::begin([
    'id' =>
    'change-password-form',
        ]);
?>

<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-lg-6">

            <div class="card shadow-sm">

                <div class="card-header">
                    <strong>
                        Change Password
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
                                    'currentPassword'
                            )
                            ->passwordInput([
                                'autocomplete' =>
                                'current-password',
                            ])
                    ?>

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'newPassword'
                            )
                            ->passwordInput([
                                'autocomplete' =>
                                'new-password',
                            ])
                            ->hint(
                                    'Use at least 8 characters.'
                            )
                    ?>

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'confirmPassword'
                            )
                            ->passwordInput([
                                'autocomplete' =>
                                'new-password',
                            ])
                    ?>

                </div>

                <div class="card-footer">

                    <?=
                    Html::submitButton(
                            'Change Password',
                            [
                                'class' =>
                                'btn btn-primary px-4',
                            ]
                    )
                    ?>

                    <?=
                    Html::a(
                            'Cancel',
                            ['profile'],
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