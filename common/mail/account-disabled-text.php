<?php

declare(strict_types=1);

/** @var common\models\User $client */
?>
Hello <?= $client->username ?>,

Your account has been disabled because your billing details were not completed within a month of signing up and it has no active service.

To use it again, please contact us at <?= Yii::$app->params['supportEmail'] ?>.

Regards,
Miracle Web Technologies
