<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $subject */
/** @var string $body staff-written plain text */
/** @var array{0: string, 1: string} $button */
echo $this->render('_card', [
    'tone' => 'info',
    'title' => $subject,
    'preformatted' => $body,
    'button' => $button,
]);
