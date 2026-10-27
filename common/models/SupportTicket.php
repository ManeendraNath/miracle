<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "support_ticket".
 *
 * @property int $id
 * @property string $ticket_number
 * @property int $user_id
 * @property string|null $service_type
 * @property int|null $service_id
 * @property string $subject
 * @property string $message
 * @property string $priority
 * @property string $status
 * @property string|null $admin_response
 * @property int|null $closed_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */
class SupportTicket extends base\SupportTicket
{

    public const STATUS_OPEN = 'Open';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_RESOLVED = 'Resolved';
    public const STATUS_CLOSED = 'Closed';
    public const PRIORITY_LOW = 'Low';
    public const PRIORITY_NORMAL = 'Normal';
    public const PRIORITY_HIGH = 'High';

    public function rules(): array
    {
        $rules = [
            [['subject', 'message', 'priority'], 'required'],
            [['subject', 'message'], 'trim'],
            ['subject', 'string', 'min' => 3, 'max' => 255],
            ['message', 'string', 'min' => 10, 'max' => 10000],
            ['priority', 'in', 'range' => [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH]],
            ['service_type', 'in', 'range' => ['domain', 'hosting', 'maintenance'], 'skipOnEmpty' => true],
            ['service_id', 'integer', 'min' => 1, 'skipOnEmpty' => true],
            ['service_id', 'required',
                'when' => static function ($model) {
                    return !empty($model->service_type);
                },
                'whenClient' => "function() {
        return $('#service-type').val() !== '';
    }",
            ],
        ];
        return ArrayHelper::merge(parent::rules(), $rules);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
        ];
    }

    public function getMessages(): ActiveQuery
    {
        return $this->hasMany(SupportTicketMessage::class, ['ticket_id' => 'id'])->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC]);
    }
}
