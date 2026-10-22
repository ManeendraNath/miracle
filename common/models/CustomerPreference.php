<?php

declare(strict_types=1);

namespace common\models;

use yii\helpers\ArrayHelper;

class CustomerPreference extends base\CustomerPreference
{

    public function rules(): array
    {
        $rules = [
            [['renewal_reminders', 'payment_reminders', 'service_expiry_notifications', 'support_notifications'], 'boolean'],
        ];
        return ArrayHelper::merge(parent::rules(), $rules);
    }

    public static function forUser(int $userId): self
    {
        $model = static::findOne(['user_id' => $userId]);
        if ($model !== null) {
            return $model;
        }
        $now = time();
        $model = new self();
        $model->user_id = $userId;
        $model->renewal_reminders = 1;
        $model->payment_reminders = 1;
        $model->service_expiry_notifications = 1;
        $model->support_notifications = 1;
        $model->created_at = $now;
        $model->updated_at = $now;
        if (!$model->save()) {
            throw new \RuntimeException('Customer preferences could not be created.');
        }
        return $model;
    }
}
