<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Contact General Inquiries | Miracle Web Technologies';
?>
<div class="site-contact container py-5" style="font-family: 'Inter', sans-serif;">
    <div class="p-5 text-white mb-4 shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 0.75rem;">
        <h1 class="fw-bold m-0">Let's Connect</h1>
        <p class="mt-2 mb-0 opacity-75">Send a quick message to our account executives for partnerships, pricing, or custom proposals.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 0.75rem;">
                <?php $form = ActiveForm::begin(['id' => 'contact-form']); ?>
                    <?= $form->field($model, 'name')->textInput(['class' => 'form-control py-2']) ?>
                    <?= $form->field($model, 'email')->textInput(['class' => 'form-control py-2']) ?>
                    <?= $form->field($model, 'subject')->textInput(['class' => 'form-control py-2']) ?>
                    <?= $form->field($model, 'body')->textarea(['rows' => 4, 'class' => 'form-control'])->label('Your Message') ?>
                    <div class="form-group text-end mt-4">
                        <?= Html::submitButton('Send Secure Message ✓', ['class' => 'btn btn-lg fw-bold text-white px-5', 'style' => 'background-color: #00a3e0 !important; border-radius: 0.5rem;']) ?>
                    </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 text-white h-100" style="background-color: #1e293b; border-radius: 0.75rem; border-left: 5px solid #dc2626 !important;">
                <h4 class="fw-bold text-white mb-3">Enterprise Channels</h4>
                <p class="small text-slate-300">Are you looking to scale an application or provision isolated hosting servers? Skip this line and deploy our direct analysis workflow.</p>
                <div class="mt-4">
                    <?= Html::a('Deploy Infrastructure Questionnaire ⚡', ['site/audit'], ['class' => 'btn w-100 fw-bold py-3 text-white', 'style' => 'background-color: #dc2626 !important; border-radius: 0.5rem;']) ?>
                </div>
            </div>
        </div>
    </div>
</div>
