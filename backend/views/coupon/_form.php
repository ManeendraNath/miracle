<?php
declare(strict_types=1);

use common\models\Coupon;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$isNew = $model->isNewRecord;

$normaliseDatetime = static function (?string $value): string {
    if (empty($value)) {
        return '';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '';
    }
    return date('Y-m-d\TH:i', $timestamp);
};
?>

<div class="coupon-form">

    <?php
    $form = ActiveForm::begin(['id' => 'coupon-form', 'options' => ['autocomplete' => 'off']]);
    ?>

    <?=
    $form->errorSummary(
            $model,
            [
                'class' =>
                'alert alert-danger mb-3',
            ]
    )
    ?>

    <div class="card card-outline card-primary shadow-sm mb-3">

        <div class="card-header">
            <h3 class="card-title">
                Coupon Information
            </h3>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-lg-6">

                    <?=
                    $form->field(
                            $model,
                            'name'
                    )->textInput([
                        'maxlength' => true,
                        'placeholder' =>
                        'e.g. Festive Season Sale',
                    ])
                    ?>

                </div>

                <div class="col-lg-6">

                    <?=
                    $form->field(
                            $model,
                            'code'
                    )->textInput([
                        'maxlength' => true,
                        'placeholder' =>
                        'e.g. FESTIVE20',
                        'style' =>
                        'text-transform: uppercase;',
                    ])
                    ?>

                </div>

            </div>

            <?=
            $form->field(
                    $model,
                    'description'
            )->textarea([
                'rows' => 4,
                'placeholder' =>
                'Internal description of this promotion...',
            ])
            ?>

        </div>

    </div>

    <div class="card card-outline card-success shadow-sm mb-3">

        <div class="card-header">
            <h3 class="card-title">
                Discount Rules
            </h3>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-lg-4">

                    <?=
                    $form->field(
                            $model,
                            'type'
                    )->dropDownList(
                            Coupon::getTypes(),
                            [
                                'prompt' =>
                                'Select Discount Type',
                            ]
                    )
                    ?>

                </div>

                <div class="col-lg-4">

                    <?=
                    $form->field(
                            $model,
                            'amount'
                    )->input(
                            'number',
                            [
                                'min' => '0.01',
                                'step' => '0.01',
                                'placeholder' => '0.00',
                            ]
                    )->hint(
                            'For percentage coupon enter a value between 0 and 100.'
                    )
                    ?>

                </div>

                <div class="col-lg-4">

                    <?=
                    $form->field(
                            $model,
                            'minimum_order'
                    )->input(
                            'number',
                            [
                                'min' => 0,
                                'step' => '0.01',
                                'placeholder' => '0.00',
                            ]
                    )->hint(
                            'Use 0 when no minimum order value is required.'
                    )
                    ?>

                </div>

            </div>

        </div>

    </div>

    <div class="card card-outline card-info shadow-sm mb-3">

        <div class="card-header">
            <h3 class="card-title">
                Validity Period
            </h3>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-lg-6">

                    <?=
                    $form->field(
                            $model,
                            'start_date'
                    )->input(
                            'datetime-local',
                            [
                                'value' => $normaliseDatetime(
                                        $model->start_date
                                ),
                            ]
                    )
                    ?>

                </div>

                <div class="col-lg-6">

                    <?=
                    $form->field(
                            $model,
                            'end_date'
                    )->input(
                            'datetime-local',
                            [
                                'value' => $normaliseDatetime(
                                        $model->end_date
                                ),
                            ]
                    )
                    ?>

                </div>

            </div>

            <div class="text-muted small">
                Leave the validity fields empty when the coupon does not require a scheduled start or expiry.
            </div>

        </div>

    </div>

    <div class="card card-outline card-secondary shadow-sm mb-3">

        <div class="card-header">
            <h3 class="card-title">
                Usage & Availability
            </h3>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-lg-4">

                    <?=
                    $form->field(
                            $model,
                            'uses_per_coupon'
                    )->input(
                            'number',
                            [
                                'min' => 0,
                                'step' => 1,
                            ]
                    )->hint(
                            'Maximum allowed uses for this coupon.'
                    )
                    ?>

                </div>

                <div class="col-lg-4">

                    <?=
                    $form->field(
                            $model,
                            'uses_per_user'
                    )->input(
                            'number',
                            [
                                'min' => 0,
                                'step' => 1,
                            ]
                    )->hint(
                            'Use 0 when there is no per-customer limit.'
                    )
                    ?>

                </div>

                <div class="col-lg-4">

                    <?=
                    $form->field(
                            $model,
                            'is_active'
                    )->dropDownList(
                            Coupon::getStatuses()
                    )
                    ?>

                </div>

            </div>

        </div>

    </div>

    <div class="d-flex justify-content-end gap-2">

        <?=
        Html::a(
                'Cancel',
                $isNew ? ['index'] : [
                    'view',
                    'id' =>
                    $model->id,
                        ],
                [
                    'class' =>
                    'btn btn-outline-secondary',
                ]
        )
        ?>

        <?=
        Html::submitButton(
                $isNew ? 'Save Coupon' : 'Update Coupon',
                [
                    'class' =>
                    'btn btn-primary px-4',
                ]
        )
        ?>

    </div>

    <?php ActiveForm::end(); ?>

</div>
