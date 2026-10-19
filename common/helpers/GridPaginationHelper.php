<?php

declare(strict_types=1);

namespace common\helpers;

use Yii;
use yii\helpers\Html;

/**
 * Description of GridPaginationHelper
 *
 * @author trivedi
 */

/**
 * Common pagination utilities for GridView/ListView pages.
 *
 * Provides:
 * - Standard page-size options.
 * - Safe page-size resolution from the request.
 * - Reusable page-size dropdown.
 *
 * Example:
 *
 * ActiveDataProvider:
 *
 * 'pagination' => [
 *     'pageSize' => GridPaginationHelper::getPageSize(),
 * ],
 *
 * View:
 *
 * GridPaginationHelper::pageSizeDropdown()
 */
final class GridPaginationHelper
{

    /**
     * Default records displayed per page.
     */
    public const DEFAULT_PAGE_SIZE = 20;

    /**
     * Allowed page sizes.
     *
     * Keeping this list controlled prevents users from requesting
     * excessively large result sets.
     */
    public const PAGE_SIZE_OPTIONS = [
        10 => '10',
        20 => '20',
        50 => '50',
        100 => '100',
    ];

    private function __construct()
    {
        
    }

    /**
     * Return the requested page size after validating it against
     * the allowed values.
     */
    public static function getPageSize(): int
    {
        $pageSize = (int) Yii::$app->request->get('per-page', self::DEFAULT_PAGE_SIZE);
        if (!array_key_exists($pageSize, self::PAGE_SIZE_OPTIONS)) {
            return self::DEFAULT_PAGE_SIZE;
        }
        return $pageSize;
    }

    /**
     * Render reusable records-per-page dropdown.
     */
    public static function pageSizeDropdown(): string
    {
        return Html::dropDownList(
                        'per-page',
                        self::getPageSize(),
                        self::PAGE_SIZE_OPTIONS,
                        [
                            'class' => 'form-select form-select-sm grid-page-size',
                            'style' => 'width: 85px;',
                            'aria-label' => 'Records per page',
                        ]
                );
    }

    /**
     * Register page-size dropdown behaviour.
     */
    public static function registerPageSizeScript(\yii\web\View $view): void
    {
        $view->registerJs(<<<'JS'

$(document).on('change', '.grid-page-size', function () {
    const url = new URL(window.location.href);

    url.searchParams.set(
        'per-page',
        $(this).val()
    );

    url.searchParams.delete('page');

    window.location.href = url.toString();
});

JS);
    }

    public static function gridLayout(): string
    {
        return '
        <div class="d-flex justify-content-between align-items-center px-3 pt-3 pb-2">
            <div>
                {summary}
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">
                    Show
                </span>

                ' . self::pageSizeDropdown() . '

                <span class="text-muted small">
                    entries
                </span>
            </div>
        </div>

        <div class="table-responsive">
            {items}
        </div>

        <div class="px-3 py-3">
            {pager}
        </div>
    ';
    }
}
