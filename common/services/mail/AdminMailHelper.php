<?php

declare(strict_types=1);

namespace common\services\mail;

use Yii;
use yii\mail\MessageInterface;

final class AdminMailHelper
{
    public static function addBillingBcc(MessageInterface $message): MessageInterface
    {
        $bcc = Yii::$app->params['adminBillingEmail'] ?? null;
        if (!empty($bcc)) {
            $message->setBcc($bcc);
        }
        return $message;
    }
}
