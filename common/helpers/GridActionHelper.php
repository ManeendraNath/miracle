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

    public static function crudColumn( string $template = '{view} {update} {delete}', string $deleteMessage = 'Are you sure you want to delete this record?'): array
    {
        return [
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
                'view' => static function (string $url): string {
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
                'update' => static function (string $url): string {
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
                'delete' => static function (string $url) use ($deleteMessage): string {
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
            'urlCreator' => static function (string $action, $model): string {
                return Url::to([$action, 'id' => $model->id]);
            },
        ];
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
