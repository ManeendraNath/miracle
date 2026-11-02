<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $serviceName */
/** @var string $expiryDate */
/** @var int $daysBefore */
/** @var common\models\User $client */
$baseUrl = (string) (Yii::$app->params['frontendBaseUrl'] ?? '');

echo $this->render('_card', [
    'tone' => 'warning',
    'eyebrow' => 'Renewal reminder',
    'title' => sprintf('Your service expires in %d day%s.', $daysBefore, $daysBefore === 1 ? '' : 's'),
    'intro' => 'Hello ' . $client->username . ', the following service is approaching its expiry date.',
    'rows' => [
        ['Service', $serviceName],
        ['Expiry date', Yii::$app->formatter->asDate($expiryDate, 'php:d M Y'), 'accent'],
    ],
    'note' => ['Please renew before the expiry date to avoid interruption or downtime.'],
    'button' => $baseUrl !== '' ? ['Open Customer Portal', $baseUrl . '/dashboard/index'] : null,
]);
