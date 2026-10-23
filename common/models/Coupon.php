<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\helpers\ArrayHelper;

class Coupon extends base\Coupon
{

    use AutoTimestampTrait;

    public static function getTypes(): array
    {
        return [
            parent::TYPE_F => 'Fixed Amount',
            parent::TYPE_P => 'Percentage',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            parent::IS_ACTIVE_Y => 'Active',
            parent::IS_ACTIVE_N => 'Inactive',
        ];
    }

    public function rules(): array
    {
        $rules = [
            [['name', 'code'], 'trim'],
            ['type', 'in', 'range' => array_keys(self::getTypes())],
            ['is_active', 'in', 'range' => array_keys(self::getStatuses())],
            [['amount', 'minimum_order'], 'number', 'min' => 0],
            [['uses_per_coupon', 'uses_per_user'], 'integer', 'min' => 0],
            ['amount', 'validateCouponAmount'],
            ['end_date', 'validateValidityPeriod'],
        ];
        return ArrayHelper::merge(parent::rules(), $rules);
    }

    public function beforeValidate(): bool
    {
        $this->code = strtoupper(trim((string) $this->code));
        foreach (['start_date', 'end_date'] as $attribute) {
            if (empty($this->$attribute)) {
                $this->$attribute = null;
                continue;
            }
            $timestamp = strtotime((string) $this->$attribute);
            if ($timestamp !== false) {
                $this->$attribute = date('Y-m-d H:i:s', $timestamp);
            }
        }
        return parent::beforeValidate();
    }

    public function validateCouponAmount(string $attribute): void
    {
        $amount = (float) $this->$attribute;
        if ($amount <= 0) {
            $this->addError($attribute, 'Discount must be greater than zero.');
            return;
        }
        if ($this->type === self::TYPE_PERCENTAGE && $amount > 100) {
            $this->addError($attribute, 'Percentage discount cannot exceed 100%.');
        }
    }

    public function validateValidityPeriod(string $attribute): void
    {
        if (empty($this->start_date) || empty($this->end_date)) {
            return;
        }
        $start = strtotime((string) $this->start_date);
        $expiry = strtotime((string) $this->end_date);
        if ($start !== false && $expiry !== false && $expiry < $start) {
            $this->addError($attribute, 'Expiry must be after the start date.');
        }
    }

    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? 'Unknown';
    }

    public function getStatusLabel(): string
    {
        return self::getStatuses()[$this->is_active] ?? 'Unknown';
    }

    public function getDiscountLabel(): string
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return number_format((float) $this->amount, 2) . '%';
        }
        return '₹' . number_format((float) $this->amount, 2);
    }

    #[\Override]
    public function attributeLabels(): array
    {
        $attributes = [
            'name' => 'Coupon Name',
            'code' => 'Coupon Code',
            'type' => 'Discount Type',
            'amount' => 'Discount Value',
            'minimum_order' => 'Minimum Order Amount',
            'start_date' => 'Starts At',
            'end_date' => 'Expires At',
            'uses_per_coupon' => 'Total Usage Limit',
            'uses_per_user' => 'Usage Limit Per Customer',
        ];
        return ArrayHelper::merge(parent::attributeLabels(), $attributes);
    }
}
