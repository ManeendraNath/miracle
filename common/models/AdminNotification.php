<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;

class AdminNotification extends base\AdminNotification
{
    /** Alert types about money; only Admins and Superadmins see them (Managers can't open those pages). */
    public const BILLING_TYPES = ['billing', 'invoice', 'payment'];

    /** Notifications $admin may see. */
    public static function visibleTo(?Admin $admin): ActiveQuery
    {
        $query = self::find();
        if ($admin === null || !$admin->isAdmin()) {
            $query->andWhere(['not in', 'type', self::BILLING_TYPES]);
        }

        return $query;
    }
}
