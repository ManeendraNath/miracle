<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $title */
/** @var string $message */
/** @var string|null $route admin page the alert is about, e.g. /invoice/view?number=... */
$this->params['staffEmail'] = true;
echo $this->render('_card', [
    'tone' => 'warning',
    'eyebrow' => 'Admin alert',
    'title' => $title,
    'preformatted' => $message,
    'button' => empty($route) ? null : ['Open in admin panel', Yii::$app->params['backendBaseUrl'] . $route],
]);
