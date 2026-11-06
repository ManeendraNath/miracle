<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

/**
 * One refund of a payment.
 *
 * @property int $id
 * @property int $transaction_id
 * @property float $amount
 * @property int $refunded_at
 * @property string|null $note
 */
final class TransactionRefund extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%transaction_refund}}';
    }
}
