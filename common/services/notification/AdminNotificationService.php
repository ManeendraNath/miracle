<?php

declare(strict_types=1);

namespace common\services\notification;

use common\models\AdminNotification;

class AdminNotificationService
{

    public static function create(string $type, string $title, string $message, ?string $route = null): void
    {
        $model = new AdminNotification();
        $model->type = $type;
        $model->title = $title;
        $model->message = $message;
        $model->route = $route;
        $model->is_read = 0;
        $model->read_at = null;
        $model->created_at = time();
        if (!$model->save()) {
            throw new \RuntimeException('Admin notification could not be saved.');
        }
    }
}
