<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'My Profile';
$this->params['breadcrumbs'][] = $this->title;

$form = ActiveForm::begin(['id' => 'profile-form']);
?>

<div class="container py-4">
    <div class="mb-4">
        <h2 class="mb-1">
            My Profile
        </h2>
        <div class="text-muted">
            Manage your customer account and review billing information.
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>
                        Account Details
                    </strong>
                </div>
                <div class="card-body">
                    <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
                    <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'autocomplete' => 'username']) ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= Html::encode($user->email) ?>" readonly />
                        <div class="form-text">
                            Contact support if your account email needs to be changed.
                        </div>
                    </div>
                    <?= $form->field($model, 'mobile')->textInput(['maxlength' => true, 'placeholder' => '919876543210']) ?>
                    <?= $form->field($model, 'whatsapp')->textInput(['maxlength' => true, 'placeholder' => '919876543210']) ?>
                </div>
                <div class="card-footer">
                    <?= Html::submitButton('Save Profile', ['class' => 'btn btn-primary px-4']) ?>
                    <?= Html::a('Change Password', ['change-password'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>
                        Billing Details
                    </strong>
                </div>

                <div class="card-body">

                    <?php
                    if (
                            $billingProfile !== null
                    ):
                        ?>

                        <div class="mb-3">
                            <div class="text-muted small">
                                Legal Name
                            </div>

                            <div class="fw-semibold">
                                <?=
                                Html::encode(
                                        $billingProfile
                                        ->legal_name
                                )
                                ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">
                                Billing Address
                            </div>

                            <div>
                                <?=
                                Html::encode(
                                        implode(
                                                ', ',
                                                array_filter([
                                    $billingProfile
                                    ->address_line_1,
                                    $billingProfile
                                    ->address_line_2,
                                    $billingProfile
                                    ->city,
                                    $billingProfile
                                    ->state,
                                    $billingProfile
                                    ->postal_code,
                                                ])
                                        )
                                )
                                ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">
                                GST
                            </div>

                            <div>
                                <?=
                                (bool)
                                $billingProfile
                                ->is_gst_applicable ? 'Applicable' : 'Not Applicable'
                                ?>
                            </div>
                        </div>

                        <?php
                        if (
                                !empty(
                                        $billingProfile->gstin
                                )
                        ):
                            ?>

                            <div class="mb-3">
                                <div class="text-muted small">
                                    GSTIN
                                </div>

                                <div>
                                    <?=
                                    Html::encode(
                                            $billingProfile
                                            ->gstin
                                    )
                                    ?>
                                </div>
                            </div>

                        <?php endif; ?>

                        <?php
                        if (
                                !empty(
                                        $billingProfile->pan
                                )
                        ):
                            ?>

                            <div>
                                <div class="text-muted small">
                                    PAN
                                </div>

                                <div>
                                    <?=
                                    Html::encode(
                                            $billingProfile
                                            ->pan
                                    )
                                    ?>
                                </div>
                            </div>

                        <?php endif; ?>

<?php else: ?>

                        <div class="alert alert-warning mb-0">
                            Billing profile information has not been configured yet.
                            Please contact support before your next invoice is generated.
                        </div>

<?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

<?php ActiveForm::end(); ?>