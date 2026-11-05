<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\helpers\ArrayHelper;

class Coupon extends base\Coupon
{
    use AutoTimestampTrait;

    /** @var array<int|string>|string|null service types (InvoiceItem item_type) the coupon is limited to; null = not submitted */
    public $applies_to;
    /** @var array<int|string>|string|null customers the coupon is limited to; null = not submitted */
    public $assigned_user_ids;

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
            ['amount', 'required'],
            ['code', 'validateCodeUnchanged'],
            ['type', 'in', 'range' => array_keys(self::getTypes())],
            ['is_active', 'in', 'range' => array_keys(self::getStatuses())],
            [['amount', 'minimum_order'], 'number', 'min' => 0],
            [['uses_per_coupon', 'uses_per_user'], 'integer', 'min' => 0],
            ['amount', 'validateCouponAmount'],
            ['end_date', 'validateValidityPeriod'],
            // An empty checkbox list / select posts '' (its hidden "unselect" input).
            [['applies_to', 'assigned_user_ids'], 'filter', 'filter' => static fn($v): ?array => $v === null ? null : array_values(array_filter((array) $v, static fn($x): bool => (string) $x !== '')), 'skipOnEmpty' => false],
            ['applies_to', 'each', 'rule' => ['in', 'range' => array_map('strval', array_keys(InvoiceItem::getServiceTypes()))]],
            ['assigned_user_ids', 'each', 'rule' => ['exist', 'targetClass' => User::class, 'targetAttribute' => 'id']],
        ];
        return ArrayHelper::merge(parent::rules(), $rules);
    }

    public function beforeValidate(): bool
    {
        // This override replaces AutoTimestampTrait::beforeValidate(), so fill timestamps here
        // (without it every coupon save failed with "Created At cannot be blank").
        $this->prepareTimestampAttributes();
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

    /** Save and its restriction rows commit together. */
    public function transactions(): array
    {
        return [self::SCENARIO_DEFAULT => self::OP_INSERT | self::OP_UPDATE];
    }

    /** Fills the form fields from the restriction tables (call before showing the form). */
    public function loadRestrictions(): void
    {
        $this->applies_to = array_map('strval', CouponApplicability::find()->select('item_type')->where(['coupon_id' => $this->id])->column());
        $this->assigned_user_ids = array_map('strval', CouponAssignedUser::find()->select('user_id')->where(['coupon_id' => $this->id])->column());
    }

    public function getAppliesToLabels(): array
    {
        $types = InvoiceItem::getServiceTypes();
        return array_map(static fn($t): string => $types[(int) $t] ?? (string) $t, CouponApplicability::find()->select('item_type')->where(['coupon_id' => $this->id])->column());
    }

    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        $now = time();
        // Only when the form submitted them: status toggles and other saves leave restrictions alone.
        if ($this->applies_to !== null) {
            CouponApplicability::deleteAll(['coupon_id' => $this->id]);
            $rows = array_map(fn($type): array => [$this->id, (int) $type, $now, $now], array_unique((array) $this->applies_to));
            if ($rows) {
                self::getDb()->createCommand()->batchInsert(CouponApplicability::tableName(), ['coupon_id', 'item_type', 'created_at', 'updated_at'], $rows)->execute();
            }
        }
        if ($this->assigned_user_ids !== null) {
            // Keep existing assignments (and their assigned_at, which orders auto-applied coupons):
            // only remove users taken off and add new ones, so editing a coupon doesn't make it "newest".
            $wanted = array_values(array_unique(array_map('intval', (array) $this->assigned_user_ids)));
            CouponAssignedUser::deleteAll(['and', ['coupon_id' => $this->id], ['not in', 'user_id', $wanted ?: [0]]]);
            $existing = array_map('intval', CouponAssignedUser::find()->select('user_id')->where(['coupon_id' => $this->id])->column());
            $rows = array_map(fn(int $userId): array => [$this->id, $userId, $now, $now, $now], array_diff($wanted, $existing));
            if ($rows) {
                self::getDb()->createCommand()->batchInsert(CouponAssignedUser::tableName(), ['coupon_id', 'user_id', 'assigned_at', 'created_at', 'updated_at'], $rows)->execute();
            }
        }
    }

    /** Used invoices print the code they were given: renaming it would leave them pointing at nothing. */
    public function validateCodeUnchanged(string $attribute): void
    {
        if (!$this->isNewRecord && $this->isAttributeChanged($attribute) && CouponRedemption::find()->where(['coupon_id' => (int) $this->id])->exists()) {
            $this->addError($attribute, 'This coupon has been used, so its code cannot change. Create a new coupon instead.');
        }
    }

    public function validateCouponAmount(string $attribute): void
    {
        $amount = (float) $this->$attribute;
        if ($amount <= 0) {
            $this->addError($attribute, 'Discount must be greater than zero.');
            return;
        }
        if ($this->type === self::TYPE_P && $amount > 100) {
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
        if ($this->type === self::TYPE_P) {
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
            'applies_to' => 'Applies To',
            'assigned_user_ids' => 'Only For These Customers',
        ];
        return ArrayHelper::merge(parent::attributeLabels(), $attributes);
    }
}
