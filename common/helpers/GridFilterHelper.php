<?php

declare(strict_types=1);

namespace common\helpers;

use kartik\daterange\DateRangePicker;
use kartik\select2\Select2;
use yii\base\Model;

final class GridFilterHelper
{

    private function __construct()
    {
        
    }

    /**
     * Reusable searchable dropdown filter.
     */
    public static function select2(Model $model, string $attribute, array $items, string $placeholder = 'All'): string
    {
        return Select2::widget([
                    'model' => $model,
                    'attribute' => $attribute,
                    'data' => $items,
                    'options' => [
                        'placeholder' => $placeholder,
                        'class' => 'grid-filter-select',
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                        'width' => '100%',
                    ],
        ]);
    }

    /**
     * Reusable date-range GridView filter.
     */
    public static function dateRange(
            Model $model,
            string $attribute,
            string $placeholder = 'Select date range'
    ): string
    {
        return DateRangePicker::widget([
                    'model' => $model,
                    'attribute' => $attribute,
                    'convertFormat' => true,
                    'options' => [
                        'class' => 'form-control form-control-sm grid-filter-control',
                        'placeholder' => $placeholder,
                        'autocomplete' => 'off',
                    ],
                    'pluginOptions' => [
                        'autoUpdateInput' => false,
                        'locale' => [
                            'format' => 'Y-m-d',
                            'separator' => ' - ',
                            'cancelLabel' => 'Clear',
                        ],
                    ],
                    'pluginEvents' => [
                        'apply.daterangepicker' => <<<'JS'
function (event, picker) {
    $(this)
        .val(
            picker.startDate.format('YYYY-MM-DD')
            + ' - '
            + picker.endDate.format('YYYY-MM-DD')
        )
        .trigger('change');
}
JS,
                        'cancel.daterangepicker' => <<<'JS'
function () {
    $(this).val('').trigger('change');
}
JS,
                    ],
        ]);
    }
}
