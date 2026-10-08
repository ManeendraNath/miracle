<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var common\models\SiteSetting $model */

$this->title = 'Global Systems Branding Settings';
?>
<div class="setting-index container py-4" style="font-family: 'Inter', system-ui, sans-serif;">
    
    <!-- 🚀 CORE CONTEXT BANNER -->
    <div class="p-5 text-white mb-4 shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 0.75rem;">
        <h1 class="fw-bold m-0"><?= Html::encode($this->title) ?></h1>
        <p class="mt-2 mb-0 opacity-75" style="color: #cbd5e1;">Configure dynamic layout definitions, support contacts, multi-channel email relays, and social indicators from a single panel.</p>
    </div>

    <!-- 📊 ARCHITECTURE ALIGNED INPUT MATRIX -->
    <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 0.75rem;">
        <?php $form = ActiveForm::begin([
            'id' => 'site-branding-matrix-form',
            'options' => ['class' => 'needs-validation']
        ]); ?>

        <!-- SECTION 1: SYSTEM IDENTITIES -->
        <h4 class="fw-bold mb-3 text-dark border-bottom pb-2" style="font-size: 1.1rem; color: #0f172a;">🏢 Base Identity Parameters</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'site_name')->textInput(['class' => 'form-control py-2', 'placeholder' => 'e.g., Miracle Web Technologies']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'site_url')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://miraclewebtechnologies.com']) ?>
            </div>
        </div>

        <!-- SECTION 2: COMMUNICATIONS CONNECTORS -->
        <h4 class="fw-bold mt-3 mb-3 text-dark border-bottom pb-2" style="font-size: 1.1rem; color: #0f172a;">📞 Support & Communication Links</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'mobile_1')->textInput(['class' => 'form-control py-2', 'type' => 'number', 'placeholder' => 'Primary Number']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'mobile_2')->textInput(['class' => 'form-control py-2', 'type' => 'number', 'placeholder' => 'Alternative Number (Optional)']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'email_1')->textInput(['class' => 'form-control py-2', 'placeholder' => 'info@company.com']) ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'email_2')->textInput(['class' => 'form-control py-2', 'placeholder' => 'support@company.com (Optional)']) ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'email_3')->textInput(['class' => 'form-control py-2', 'placeholder' => 'sales@company.com (Optional)']) ?>
            </div>
        </div>

        <!-- SECTION 3: PHYSICAL LOCATIONS MAP -->
        <h4 class="fw-bold mt-3 mb-3 text-dark border-bottom pb-2" style="font-size: 1.1rem; color: #0f172a;">📍 Physical Headquarters Address Blocks</h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'address_line_1')->textInput(['class' => 'form-control py-2', 'placeholder' => 'Suite, Building Floor']) ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'address_line_2')->textInput(['class' => 'form-control py-2', 'placeholder' => 'Street Area, Complex Path']) ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'address_line_3')->textInput(['class' => 'form-control py-2', 'placeholder' => 'City, State, Pin Code']) ?>
            </div>
        </div>

        <!-- SECTION 4: SOCIAL MEDIA GRAPH PARAMETERS -->
        <h4 class="fw-bold mt-3 mb-3 text-dark border-bottom pb-2" style="font-size: 1.1rem; color: #0f172a;">? Corporate Social Network Profiles</h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'facebook')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://facebook.com...']) ?>
            </div>
            <div class="col-md-4 mb-3">
                <!-- 💡 ALIGNED TO YOUR SCHEMA 'x' COLUMN -->
                <?= $form->field($model, 'x')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://x.com...'])->label('Twitter / X Profile') ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'linkedin')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://linkedin.com...']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'youtube')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://youtube.com...']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'instagram')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://instagram.com...']) ?>
            </div>
        </div>

        <!-- SAVE BUTTON CONTAINER -->
        <div class="form-group mt-4 pt-2 text-end">
            <?= Html::submitButton('Commit Changes to Infrastructure Matrix ✓', [
                'class' => 'btn btn-lg fw-bold px-5 text-white shadow-sm',
                'style' => 'background-color: #00a3e0 !important; border: none !important; border-radius: 0.5rem; transition: all 0.2s ease;'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
