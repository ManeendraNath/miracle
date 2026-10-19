<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\helpers\ArrayHelper;

/**
 * Audit Request model.
 */
class AuditRequest extends base\AuditRequest
{

    use AutoTimestampTrait;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_IN_REVIEW = 'In_Review';
    public const STATUS_CONTACTED = 'Contacted';
    public const STATUS_CLOSED = 'Closed';

    /**
     * Available Audit Request statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_REVIEW => 'In Review',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return ArrayHelper::merge(
                        parent::attributeLabels(),
                        [
                            'company_url' => 'Website',
                            'current_framework' => 'Framework',
                            'hosting_environment' => 'Hosting',
                            'user_ip' => 'IP Address',
                            'created_at' => 'Created At',
                            'updated_at' => 'Updated At',
                        ]
                );
    }
}
