<?php

use common\models\AuditRequest;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\AuditRequest $model */
/** @var yii\widgets\ActiveForm $form */

$isNew = $model->isNewRecord;
?>

<div class="audit-request-form">

    <?php $form = ActiveForm::begin([
        'options' => [
            'autocomplete' => 'off',
        ],
    ]); ?>

    <div class="card card-outline card-primary shadow-sm">

        <div class="card-header">

            <h3 class="card-title mb-0">
                <?= $isNew
                    ? 'Audit Request Details'
                    : 'Update Audit Request Details'
                ?>
            </h3>

        </div>

        <div class="card-body">

            <?= $form->errorSummary(
                $model,
                [
                    'class' => 'alert alert-danger',
                ]
            ) ?>


            <!-- Client Information -->

            <div class="mb-3">
                <h6 class="text-primary border-bottom pb-2">
                    Client Information
                </h6>
            </div>

            <div class="row">

                <div class="col-md-6">
                    <?= $form->field($model, 'name')
                        ->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Client name',
                        ]) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'email')
                        ->input('email', [
                            'maxlength' => true,
                            'placeholder' => 'client@example.com',
                        ]) ?>
                </div>

            </div>

            <div class="row">

                <div class="col-md-6">
                    <?= $form->field($model, 'phone')
                        ->input('tel', [
                            'maxlength' => true,
                            'placeholder' => 'Phone number',
                        ]) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'company_url')
                        ->input('url', [
                            'maxlength' => true,
                            'placeholder' => 'https://example.com',
                        ]) ?>
                </div>

            </div>


            <!-- Technical Information -->

            <div class="mt-3 mb-3">
                <h6 class="text-primary border-bottom pb-2">
                    Technical Information
                </h6>
            </div>

            <div class="row">

                <div class="col-md-6">
                    <?= $form->field($model, 'current_framework')
                        ->textInput([
                            'maxlength' => true,
                            'placeholder' =>
                                'e.g. WordPress, Laravel, Yii2',
                        ]) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'hosting_environment')
                        ->textInput([
                            'maxlength' => true,
                            'placeholder' =>
                                'e.g. Shared Hosting, VPS, AWS',
                        ]) ?>
                </div>

            </div>


            <!-- Request Information -->

            <div class="mt-3 mb-3">
                <h6 class="text-primary border-bottom pb-2">
                    Request Information
                </h6>
            </div>

            <div class="row">

                <div class="col-12">
                    <?= $form->field($model, 'message')
                        ->textarea([
                            'rows' => 5,
                            'placeholder' =>
                                'Enter audit request details...',
                        ]) ?>
                </div>

            </div>

            <div class="row">

                <div class="col-md-6">

                    <?= $form->field($model, 'status')
                        ->dropDownList(
                            AuditRequest::getStatuses(),
                            ['prompt' => 'Select Status']
                        ) ?>

                </div>

            </div>

        </div>


        <!-- Actions -->

        <div
            class="card-footer d-flex justify-content-between align-items-center"
        >

            <?= Html::a(
                '<i class="fas fa-arrow-left me-1"></i> Cancel',
                $isNew
                    ? ['index']
                    : ['view', 'id' => $model->id],
                [
                    'class' => 'btn btn-outline-secondary',
                ]
            ) ?>

            <?= Html::submitButton(
                $isNew
                    ? '<i class="fas fa-save me-1"></i> Save Audit Request'
                    : '<i class="fas fa-save me-1"></i> Update Audit Request',
                [
                    'class' => 'btn btn-primary px-4',
                ]
            ) ?>

        </div>

    </div>

    <?php ActiveForm::end(); ?>

</div>