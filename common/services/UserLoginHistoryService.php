<?php

declare(strict_types=1);

namespace common\services;

use common\models\UserLoginHistory;
use Yii;

final class UserLoginHistoryService
{

    public function record(
            int $userId
    ): void
    {
        try {
            $model = new UserLoginHistory();

            $model->user_id = $userId;

            $model->ip_address = Yii::$app->request
                    ->userIP;

            $model->user_agent = mb_substr(
                    (string)
                    Yii::$app->request
                    ->userAgent,
                    0,
                    1000
            );

            $model->logged_in_at = time();

            if (!$model->save(false)) {
                throw new \RuntimeException(
                                'Login history '
                                . 'could not be saved.'
                        );
            }
        } catch (\Throwable $e) {
            Yii::error(
                    [
                        'message' => 'Unable to record '
                        . 'login history.',
                        'userId' =>
                        $userId,
                        'error' =>
                        $e->getMessage(),
                    ],
                    __METHOD__
            );
        }
    }
}
