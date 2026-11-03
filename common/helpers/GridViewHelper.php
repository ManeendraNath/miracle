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
        // Tooltips are initialised for every page by layouts/main.php.
        GridPaginationHelper::registerPageSizeScript($view);
    }
}
