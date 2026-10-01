<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "invoice_item".
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $category
 * @property string $description
 * @property string|null $duration_quantity
 * @property float $unit_price
 * @property float $total_price
 *
 * @property Invoice $invoice
 */
class InvoiceItem extends base\InvoiceItem
{

}
