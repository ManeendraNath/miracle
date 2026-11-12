<?php

declare(strict_types=1);

use kartik\select2\Select2;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\base\DynamicModel $form */
/** @var array<string, array{label: string, subject: string, message: string}> $templates */
/** @var array<int, string> $clients id => "username (email)", every client with an email address (status shown unless Active) */
/** @var int $activeCount */
$this->title = 'Email customers';
$this->params['breadcrumbs'][] = ['label' => 'Clients', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Send to' => 'Selected clients: only the ones you pick below. All active clients: everyone who can sign in. All clients: also inactive, suspended and deleted accounts, so use it rarely.',
    'Template' => 'Pick a ready-made message. Choosing one replaces whatever is in Subject and Message.',
    '{name}' => 'Becomes each client\'s name (their billing legal name, or username if they have none).',
    '[EDIT: ...]' => 'Parts you must replace with your own words. The email will not send while one is left.',
    'Send email' => 'Asks how many clients it will go to, then sends each one separately. If some fail, their usernames are listed so you can try them again with Selected clients.',
];
?>
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-body">
            <p class="text-muted">
                Each client gets their own email, greeted by name ({name} is filled in for each), so no one sees the
                other addresses. One copy, listing who got it, goes to the billing address.
            </p>
            <div class="mb-3">
                <label class="form-label" for="email-template">Template</label>
                <?= Html::dropDownList('template', null, array_map(static fn (array $t): string => $t['label'], $templates), ['id' => 'email-template', 'class' => 'form-select']) ?>
                <div class="form-text">Choosing a template replaces the subject and message below.</div>
            </div>
            <?php $active = ActiveForm::begin(); ?>
            <?= $active->field($form, 'recipients')->radioList([
                'selected' => 'Selected clients (one or more)',
                'all' => 'All active clients (' . $activeCount . ')',
                'everyone' => 'All clients – active, inactive, suspended and deleted (' . count($clients) . ')',
            ])->label('Send to') ?>
            <div id="email-pick">
                <?= $active->field($form, 'user_ids')->widget(Select2::class, [
                    'data' => $clients,
                    'options' => ['placeholder' => 'Choose clients', 'multiple' => true],
                    'pluginOptions' => ['allowClear' => true, 'width' => '100%'],
                ])->label('Clients') ?>
            </div>
            <?= $active->field($form, 'subject')->textInput(['maxlength' => 200])->label('Subject') ?>
            <?= $active->field($form, 'message')->textarea(['rows' => 16])
                ->label('Message')->hint('Replace any [EDIT: ...] part with your own words. Keep {name}: it becomes each client\'s name. Plain text: line breaks are kept.') ?>
            <div class="d-flex gap-2">
                <?= Html::submitButton('<i class="fas fa-paper-plane me-1"></i> Send email', ['class' => 'btn btn-primary', 'id' => 'email-send']) ?>
                <?= Html::a('Cancel', ['/dashboard/index'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
<?php
$this->registerJs('const t = ' . json_encode($templates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
const totals = {all: ' . $activeCount . ', everyone: ' . count($clients) . '};
document.getElementById("email-template").addEventListener("change", (e) => {
    document.getElementById("dynamicmodel-subject").value = t[e.target.value].subject;
    document.getElementById("dynamicmodel-message").value = t[e.target.value].message;
});
const pick = () => {
    const choice = document.querySelector("input[name=\'DynamicModel[recipients]\']:checked")?.value;
    document.getElementById("email-pick").style.display = choice === "selected" ? "" : "none";
};
document.querySelectorAll("input[name=\'DynamicModel[recipients]\']").forEach((r) => r.addEventListener("change", pick));
pick();
document.getElementById("email-send").addEventListener("click", (e) => {
    const choice = document.querySelector("input[name=\'DynamicModel[recipients]\']:checked")?.value;
    const n = choice === "selected" ? $("#dynamicmodel-user_ids").val().length : totals[choice];
    if (!confirm("Send this email to " + n + " client(s)?")) {
        e.preventDefault();
    }
});');
