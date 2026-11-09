<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\web\IdentityInterface;

class AdminNotification extends base\AdminNotification
{
    /** Alert types about money; only Admins and Superadmins see them (Managers can't open those pages). */
    public const BILLING_TYPES = ['billing', 'invoice', 'payment'];

    /** Notifications $admin (the signed-in identity) may see: none when signed out, e.g. on the admin error page. */
    public static function visibleTo(?IdentityInterface $admin): ActiveQuery
    {
        $query = self::find();
        if (!$admin instanceof Admin) {
            return $query->andWhere('0 = 1');
        }
        if (!$admin->isAdmin()) {
            $query->andWhere(['not in', 'type', self::BILLING_TYPES]);
        }

        return $query;
    }
}
