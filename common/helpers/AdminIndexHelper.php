<?php

declare(strict_types=1);

namespace common\helpers;

use yii\helpers\Html;

final class AdminIndexHelper
{

    public static function pageHeader(string $description, ?string $createUrl = null, ?string $createLabel = null): string
    {
        $button = '';
        if ($createUrl !== null && $createLabel !== null) {
            $button = Html::a(
                    '<i class="fas fa-plus me-1"></i> ' . $createLabel,
                    [$createUrl],
                    [
                        'class' => 'btn btn-sm btn-primary',
                    ]
            );
        }

        return
                '<div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">'
                . Html::encode($description)
                . '</div>
                    </div>
                    ' . $button . '
                </div>
            </div>';
    }
}
