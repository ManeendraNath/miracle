<?php

declare(strict_types=1);

namespace common\helpers;

use yii\grid\ActionColumn;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

final class GridActionHelper
{

    private function __construct()
    {
        
    }

    /**
     * @param callable|null $isEnabled fn($model): bool, used by the {toggle-status} button
     */
    public static function crudColumn(string $template = '{view} {update} {delete}', string $deleteMessage = 'Are you sure you want to delete this record?', ?callable $urlCreator = null, ?callable $isEnabled = null): array {
    $column = [
        'class' => ActionColumn::class,
        'header' => 'Actions',
        'template' => $template,
        'headerOptions' => [
            'class' => 'text-center',
            'style' => 'width: 135px;',
        ],
        'contentOptions' => [
            'class' => 'text-center text-nowrap',
        ],
        'buttons' => [
            'view' => static function (string|array $url): string {
                return Html::a(
                    '<i class="fas fa-eye"></i>',
                    $url,
                    [
                        'class' => 'btn btn-xs btn-outline-info',
                        'title' => 'View',
                        'data-bs-toggle' => 'tooltip',
                        'data-bs-placement' => 'top',
                    ]
                );
            },
            'update' => static function (string|array $url): string {
                return Html::a(
                    '<i class="fas fa-edit"></i>',
                    $url,
                    [
                        'class' => 'btn btn-xs btn-outline-primary',
                        'title' => 'Update',
                        'data-bs-toggle' => 'tooltip',
                        'data-bs-placement' => 'top',
                    ]
                );
            },
            'toggle-status' => static function (string|array $url, $model) use ($isEnabled): string {
                $enabled = $isEnabled !== null ? (bool) $isEnabled($model) : true;
                return Html::a(
                    $enabled ? '<i class="fas fa-ban"></i>' : '<i class="fas fa-check"></i>',
                    $url,
                    [
                        'class' => 'btn btn-xs ' . ($enabled ? 'btn-outline-warning' : 'btn-outline-success'),
                        'title' => $enabled ? 'Disable' : 'Enable',
                        'data-bs-toggle' => 'tooltip',
                        'data-bs-placement' => 'top',
                        'data' => [
                            'confirm' => $enabled ? 'Disable this record?' : 'Enable this record?',
                            'method' => 'post',
                        ],
                    ]
                );
            },
            'delete' => static function (string|array $url) use ($deleteMessage): string {
                return Html::a(
                    '<i class="fas fa-trash"></i>',
                    $url,
                    [
                        'class' => 'btn btn-xs btn-outline-danger',
                        'title' => 'Delete',
                        'data-bs-toggle' => 'tooltip',
                        'data-bs-placement' => 'top',
                        'data' => [
                            'confirm' => $deleteMessage,
                            'method' => 'post',
                        ],
                    ]
                );
            },
        ],
    ];
    if ($urlCreator !== null) {
        $column['urlCreator'] = $urlCreator;
    }
    return $column;
}

    /**
     * Registers Bootstrap 5 tooltips for GridView elements.
     *
     * Call once from an index page containing elements with:
     *
     * data-bs-toggle="tooltip"
     */
    public static function registerTooltips(View $view): void
    {
        $view->registerJs(<<<'JS'
if (
    typeof bootstrap !== 'undefined'
    && typeof bootstrap.Tooltip !== 'undefined'
) {
    document
        .querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach(function (element) {
            new bootstrap.Tooltip(element);
        });
}
JS);
    }
}
