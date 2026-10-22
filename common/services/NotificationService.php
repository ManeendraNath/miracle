<?php

declare(strict_types=1);

namespace common\services;

use common\models\CustomerNotification;

final class NotificationService
{

    public function create(int $userId, string $type, string $title, string $message, ?string $route = null): CustomerNotification
    {
        $model = new CustomerNotification();
        $model->user_id = $userId;
        $model->type = $type;
        $model->title = $title;
        $model->message = $message;
        $model->route = $route;
        $model->is_read = 0;
        $model->read_at = null;
        $model->created_at = time();
        if (!$model->save()) {
            throw new \RuntimeException('Customer notification could not be created.');
        }
        return $model;
    }
}
