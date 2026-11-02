<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $title */
/** @var string $message */
echo $this->render('_card', [
    'tone' => 'warning',
    'eyebrow' => 'Admin alert',
    'title' => $title,
    'preformatted' => $message,
]);
