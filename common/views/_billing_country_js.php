<?php

declare(strict_types=1);

/**
 * Billing profile forms (admin and customer): outside India the GST fields are hidden. The state
 * select gets the "Foreign Country" option so its required check passes; the model sets the same.
 *
 * @var yii\web\View $this
 */
$foreign = json_encode(common\models\CustomerBillingProfile::FOREIGN_STATE);
$this->registerJs(<<<JS
(function () {
    var country = $('#customerbillingprofile-country'), state = $('#customerbillingprofile-state');
    var gstFields = $('.field-customerbillingprofile-state, .field-customerbillingprofile-gstin, .field-customerbillingprofile-pan, .field-customerbillingprofile-is_gst_applicable');
    function toggle() {
        var abroad = !!country.val() && country.val() !== 'India';
        gstFields.toggle(!abroad);
        state.find('option.foreign').remove();
        if (abroad) {
            state.append($('<option class="foreign">').val($foreign).text($foreign)).val($foreign);
            $('#customerbillingprofile-gstin, #customerbillingprofile-pan').val('');
        } else if (state.val() === null) {
            state.val('');
        }
    }
    country.on('change', toggle);
    toggle();
})();
JS);
