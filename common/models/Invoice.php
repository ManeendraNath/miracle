<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "invoice".
 *
 * @property int $id
 * @property string $invoice_number
 * @property string $client_name
 * @property string|null $client_address_line_1
 * @property string|null $client_address_line_2
 * @property float|null $discount_amount
 * @property float|null $cgst_percent
 * @property float|null $sgst_percent
 * @property float|null $igst_percent
 * @property string|null $status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property InvoiceItem[] $invoiceItems
 */
class Invoice extends base\Invoice
{

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }
}
