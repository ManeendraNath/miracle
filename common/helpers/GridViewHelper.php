<?php

declare(strict_types=1);

namespace common\helpers;

use yii\web\View;

/**
 * Registers functionality shared by application GridViews.
 */
final class GridViewHelper
{

    private function __construct()
    {
        
    }

    /**
     * Register shared GridView functionality.
     */
    public static function register(View $view): void
    {
        GridPaginationHelper::registerPageSizeScript($view);

        GridActionHelper::registerTooltips($view);
    }
}
