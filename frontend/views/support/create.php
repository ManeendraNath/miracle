<?php
declare(strict_types=1);

use common\models\SupportTicket;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Json;

/** @var SupportTicket $model */
$this->title = 'Create Support Request';

$this->params['breadcrumbs'][] = ['label' => 'Support', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$form = ActiveForm::begin(['id' => 'support-ticket-form']);
?>

<div class="card border-0 shadow-sm">

    <div class="card-header">
        <strong>Create Support Ticket</strong>
    </div>

    <div class="card-body">
        <?=
        $form->field($model, 'service_type')->dropDownList(
                [
                    '' => 'General Support',
                    'domain' => 'Domain',
                    'hosting' => 'Hosting',
                    'maintenance' => 'Maintenance',
                ],
                [
                    'id' => 'service-type'
                ]
        )
        ?>
        <?=
        $form->field($model, 'service_id')->dropDownList(
                [],
                [
                    'prompt' => 'Select Service',
                    'id' => 'service-id',
                ]
        )
        ?>
        <?= $form->field($model, 'priority')->dropDownList(SupportTicket::getPriorities()) ?>
            <?= $form->field($model, 'subject')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'message')->textarea(['rows' => 8]) ?>
        <div class="mt-3">
        <?= Html::submitButton('Submit Ticket', ['class' => 'btn btn-primary', 'data' => ['disable-with' => 'Submitting...']]) ?>
        <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
<?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$this->registerJs('
    const services = ' . Json::htmlEncode($services) . ';
    $("#service-type").on("change", function () {
        const type = $(this).val();
        const serviceSelect = $("#service-id");
        serviceSelect.empty();
        serviceSelect.append($("<option>").val("").text("Select Service"));
        if (!type || !services[type]) {
            return;
        }
        $.each(
            services[type],
            function(id, label) {
                serviceSelect.append($("<option>").val(id).text(label));
            }
        );
    });
    $("#service-type").trigger("change");
');
?>