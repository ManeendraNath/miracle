<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $client */
echo $this->render('_card', [
    'tone' => 'warning',
    'eyebrow' => 'Your account',
    'title' => 'Your account has been disabled.',
    'intro' => 'Hello ' . $client->username . ', your account has been disabled because your billing details were not completed within a month of signing up and it has no active service.',
    'note' => ['To use it again, please contact us at ' . Yii::$app->params['supportEmail'] . '.'],
]);
