<?php

declare(strict_types=1);

namespace common\helpers;

use yii\grid\ActionColumn;
use yii\helpers\Html;

/**
 * The row action buttons every admin list uses: small outlined icon buttons with a tooltip.
 * GridViews use crudColumn(); hand-built tables call button() directly.
 */
final class GridActionHelper
{
    /** action => [icon, colour, tooltip] */
    private const BUTTONS = [
        'view' => ['fa-eye', 'info', 'View'],
        'update' => ['fa-edit', 'primary', 'Update'],
        'delete' => ['fa-trash', 'danger', 'Delete'],
        'disable' => ['fa-ban', 'warning', 'Disable'],
        'enable' => ['fa-check', 'success', 'Enable'],
        'resend' => ['fa-paper-plane', 'secondary', 'Resend Invoice'],
    ];

    private function __construct()
    {
    }

    /**
     * One action button. $type is a key of BUTTONS; $options are merged in (e.g. 'title', 'data').
     */
    public static function button(string $type, string|array $url, array $options = []): string
    {
        [$icon, $colour, $title] = self::BUTTONS[$type];

        return Html::a('<i class="fas ' . $icon . '"></i>', $url, array_merge([
            'class' => 'btn btn-xs btn-outline-' . $colour,
            'title' => $title,
            'data-bs-toggle' => 'tooltip',
            'data-bs-placement' => 'top',
        ], $options));
    }

    /**
     * @param callable|null $isEnabled fn($model): bool, used by the {toggle-status} button
     * @param callable|null $urlCreator fn(string $action, $model): array, when the routes aren't the default [action, 'id' => id]
     * @param array<string, callable|bool> $visibleButtons action => fn($model): bool, to hide a button on some rows
     */
    public static function crudColumn(
        string $template = '{view} {update} {delete}',
        string $deleteMessage = 'Are you sure you want to delete this record?',
        ?callable $urlCreator = null,
        ?callable $isEnabled = null,
        array $visibleButtons = [],
    ): array {
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
            'visibleButtons' => $visibleButtons,
            'buttons' => [
                'view' => static fn (string|array $url): string => self::button('view', $url),
                'update' => static fn (string|array $url): string => self::button('update', $url),
                'toggle-status' => static function (string|array $url, $model) use ($isEnabled): string {
                    $enabled = $isEnabled !== null ? (bool) $isEnabled($model) : true;

                    return self::button($enabled ? 'disable' : 'enable', $url, [
                        'data' => ['confirm' => $enabled ? 'Disable this record?' : 'Enable this record?', 'method' => 'post'],
                    ]);
                },
                'resend-invoice' => static fn (string|array $url): string => self::button('resend', $url, [
                    'data' => ['confirm' => 'Send this invoice to the customer again?', 'method' => 'post'],
                ]),
                'delete' => static fn (string|array $url): string => self::button('delete', $url, [
                    'data' => ['confirm' => $deleteMessage, 'method' => 'post'],
                ]),
            ],
        ];
        if ($urlCreator !== null) {
            $column['urlCreator'] = $urlCreator;
        }

        return $column;
    }
}
