<?php

declare(strict_types=1);

namespace common\models;

use yii\helpers\ArrayHelper;

final class SupportTicketMessage extends base\SupportTicketMessage
{

    public const SENDER_CUSTOMER = 'customer';
    public const SENDER_ADMIN = 'admin';

    #[\Override]
    public function rules(): array
    {
        $rules = [
            ['message', 'trim'],
            ['message', 'string', 'min' => 1, 'max' => 10000],
            ['sender_type', 'in', 'range' => [self::SENDER_CUSTOMER, self::SENDER_ADMIN]],
        ];
        return ArrayHelper::merge(parent::rules(), $rules);
    }
}
