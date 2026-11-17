<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $site */
/** @var string $confirmUrl */
echo $this->render('_card', [
    'tone' => 'info',
    'eyebrow' => 'Blog newsletter',
    'title' => 'Confirm your subscription',
    'intro' => 'Click the button to get new posts from the ' . $site . ' blog by email. We send an email only when a new post is published, and every email has an unsubscribe link.',
    'button' => ['Confirm my subscription', $confirmUrl],
    'footnote' => 'If you did not ask for this, ignore this email: you will not be subscribed.',
]);
