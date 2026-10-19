<?php

declare(strict_types=1);

namespace common\models\forms;

use yii\base\Model;

final class ManualInvoiceItemForm extends Model
{

    public ?int $id = null;
    public string $category = '';
    public string $description = '';
    public int $quantity = 1;
    public float $unit_price = 0.00;

    public function rules(): array
    {
        return [
            [
                [
                    'category',
                    'description',
                ],
                'trim',
            ],
            [
                [
                    'category',
                    'description',
                ],
                'required',
            ],
            [
                'category',
                'string',
                'max' => 100,
            ],
            [
                'description',
                'string',
            ],
            [
                'quantity',
                'integer',
                'min' => 1,
            ],
            [
                'unit_price',
                'number',
                'min' => 0.01,
            ],
            [
                'id',
                'integer',
            ],
        ];
    }

    public function getTotal(): float
    {
        return round(
                $this->quantity * $this->unit_price,
                2
        );
    }
}
