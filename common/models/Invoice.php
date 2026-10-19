<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;

class Invoice extends base\Invoice
{

    public function getItems(): ActiveQuery
{
    return $this->hasMany(
        InvoiceItem::class,
        [
            'invoice_id' => 'id',
        ]
    )->orderBy([
        'id' => SORT_ASC,
    ]);
}

    public function getTransactions(): ActiveQuery
    {
        return $this->hasMany(Transaction::class, ['invoice_id' => 'id']);
    }

    public function getBillingEvents(): ActiveQuery
    {
        return $this->hasMany(BillingEvent::class, ['invoice_id' => 'id']);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_UNPAID =>
            'Unpaid',
            self::STATUS_PARTIALLY_PAID =>
            'Partially Paid',
            self::STATUS_PAID =>
            'Paid',
            self::STATUS_CARRIED_FORWARD =>
            'Carried Forward',
            self::STATUS_CANCELLED =>
            'Cancelled',
            self::STATUS_REFUNDED =>
            'Refunded',
        ];
    }
}
