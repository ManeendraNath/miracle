<?php

declare(strict_types=1);

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $client */
/** @var common\models\Invoice|null $invoice */
/** @var yii\base\DynamicModel $form */
/** @var array $back */
/** @var array<string, array{label: string, subject: string, message: string}> $templates */
$this->title = 'Email customer: ' . $client->username;
if ($invoice !== null) {
    $this->params['breadcrumbs'][] = ['label' => 'Invoices', 'url' => ['/invoice/index']];
    $this->params['breadcrumbs'][] = ['label' => $invoice->invoice_number, 'url' => $back];
} else {
    $this->params['breadcrumbs'][] = ['label' => 'Clients', 'url' => ['index']];
    $this->params['breadcrumbs'][] = ['label' => $client->username, 'url' => $back];
}
$this->params['breadcrumbs'][] = 'Email customer';
?>
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-body">
            <p class="text-muted">
                To: <strong><?= Html::encode($client->email ?: 'no email address on file') ?></strong>.
                A copy goes to the billing address. The message is sent in the standard email design with a link to the
                customer's <?= $invoice !== null ? 'invoices' : 'account' ?>.
            </p>
            <div class="mb-3">
                <label class="form-label" for="email-template">Template</label>
                <?= Html::dropDownList('template', null, array_map(static fn (array $t): string => $t['label'], $templates), ['id' => 'email-template', 'class' => 'form-select']) ?>
                <div class="form-text">Choosing a template replaces the subject and message below.</div>
            </div>
            <?php $active = ActiveForm::begin(); ?>
            <?= $active->field($form, 'subject')->textInput(['maxlength' => 200])->label('Subject') ?>
            <?= $active->field($form, 'message')->textarea(['rows' => 16])
                ->label('Message')->hint('Replace any [EDIT: ...] part with your own words. Plain text: line breaks are kept.') ?>
            <div class="d-flex gap-2">
                <?= Html::submitButton('<i class="fas fa-paper-plane me-1"></i> Send email', ['class' => 'btn btn-primary', 'data-confirm' => 'Send this email to the customer?']) ?>
                <?= Html::a('Cancel', $back, ['class' => 'btn btn-outline-secondary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
<?php
$this->registerJs('const t = ' . json_encode($templates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
document.getElementById("email-template").addEventListener("change", (e) => {
    document.getElementById("dynamicmodel-subject").value = t[e.target.value].subject;
    document.getElementById("dynamicmodel-message").value = t[e.target.value].message;
});');
