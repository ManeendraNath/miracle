<?php

declare(strict_types=1);

namespace common\models;

/**
 * One refund of a payment.
 *
 * @property int $id
 * @property int $transaction_id
 * @property float $amount
 * @property int $refunded_at
 * @property string|null $note
 */
final class TransactionRefund extends base\TransactionRefund
{
    public static function tableName(): string
    {
        return '{{%transaction_refund}}';
    }
}
