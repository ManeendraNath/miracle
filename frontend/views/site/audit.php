<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Deploy System Infrastructure Audit | Miracle Web Technologies';
?>
<div class="site-audit container py-5" style="font-family: 'Inter', sans-serif;">
    <div class="p-5 text-white mb-4 shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 0.75rem; border-bottom: 4px solid #00a3e0;">
        <h1 class="fw-bold m-0">System Architecture Intake Audit</h1>
        <p class="mt-2 mb-0 opacity-75">Provide your current development specifications below. Our engineers will audit the environment for containerization stability limits.</p>
    </div>

    <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 0.75rem;">
        <?php $form = ActiveForm::begin(['id' => 'infrastructure-audit-form']); ?>
            
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">👤 Point of Contact</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <?= $form->field($model, 'name')->textInput(['class' => 'form-control py-2', 'placeholder' => 'First & Last Name']) ?>
                </div>
                <div class="col-md-6 mb-3">
                    <?= $form->field($model, 'email')->textInput(['class' => 'form-control py-2', 'placeholder' => 'corporate@email.com']) ?>
                </div>
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3 mt-3">⚙️ Technical Scope Specifications</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <?= $form->field($model, 'company_url')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://example.com']) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $form->field($model, 'current_framework')->dropDownList([
                        'Yii1 Legacy' => 'Yii1 Legacy Framework',
                        'Yii2 Standard' => 'Yii2 Standard Stack',
                        'Core PHP' => 'Core PHP Stack Native',
                        'Laravel Engine' => 'Laravel Engine Layer',
                        'Other' => 'Other Custom System'
                    ], ['class' => 'form-select py-2']) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $form->field($model, 'hosting_environment')->dropDownList([
                        'Shared Host cPanel' => 'Shared Host / Reseller cPanel',
                        'AWS Cloud Instance' => 'AWS Cloud EC2 Clusters',
                        'DigitalOcean Droplet' => 'DigitalOcean Bare Metal VPS',
                        'Local Server Drive' => 'On-Premise Physical Server Hardware'
                    ], ['class' => 'form-select py-2']) ?>
                </div>
            </div>

            <div class="mb-3 mt-2">
                <?= $form->field($model, 'message')->textarea(['rows' => 5, 'placeholder' => 'Describe your scaling bottlenecks, current database user limits, or script compilation error records...'])->label('Detailed Constraints Scope') ?>
            </div>

            <div class="form-group text-end mt-4">
                <?= Html::submitButton('Submit Specification Matrix for Review ⚡', [
                    'class' => 'btn btn-lg fw-bold text-white px-5 py-3',
                    'style' => 'background-color: #00a3e0 !important; border: none; border-radius: 0.5rem;'
                ]) ?>
            </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
