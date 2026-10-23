<?php

declare(strict_types=1);

namespace common\services\billing;

use Yii;
use common\models\Coupon;
use common\models\CouponApplicability;
use common\models\CouponAssignedUser;
use common\models\CouponRedemption;
use common\models\Invoice;
use common\models\InvoiceItem;

final class CouponService
{

    public function calculate(?string $code, int $userId, float $subtotal, ?int $excludeInvoiceId = null): float
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return 0.00;
        }
        if ($userId <= 0) {
            throw new \DomainException('A valid client is required before applying a coupon.');
        }
        $subtotal = round($subtotal, 2);
        if ($subtotal <= 0) {
            throw new \DomainException('Coupon cannot be applied to an empty invoice.');
        }
        $couponId = Yii::$app->db->createCommand('SELECT id FROM {{%coupon}} WHERE code = :code FOR UPDATE')->bindValue(':code', $code)->queryScalar();
        $coupon = $couponId !== false ? Coupon::findOne((int) $couponId) : null;
        if ($coupon === null) {
            throw new \DomainException('Coupon code is invalid.');
        }
        if ($coupon->is_active !== Coupon::IS_ACTIVE_Y) {
            throw new \DomainException('Coupon is not active.');
        }
        $now = time();
        if (!empty($coupon->start_date)) {
            $startsAt = strtotime($coupon->start_date);
            if ($startsAt !== false && $now < $startsAt) {
                throw new \DomainException('Coupon is not active yet.');
            }
        }
        if (!empty($coupon->end_date)) {
            $expiresAt = strtotime($coupon->end_date);
            if ($expiresAt !== false && $now > $expiresAt) {
                throw new \DomainException('Coupon has expired.');
            }
        }
        $minimumOrder = round((float) $coupon->minimum_order, 2);
        if ($subtotal < $minimumOrder) {
            throw new \DomainException('Invoice subtotal does not meet the minimum amount required for this coupon.');
        }
        $this->validateAssignedUser($coupon, $userId);
        $this->validateUsageLimits($coupon, $userId, $excludeInvoiceId);
        if ($coupon->type === Coupon::TYPE_P) {
            $discount = round($subtotal * ((float) $coupon->amount / 100), 2);
        } else {
            $discount = round((float) $coupon->amount, 2);
        }
        return min($subtotal, max(0.00, $discount));
    }

    public function record(Invoice $invoice): void
    {
        $code = strtoupper(trim((string) $invoice->coupon_code));
        if ($code === '') {
            return;
        }
        $coupon = Coupon::findOne(['code' => $code]);
        if ($coupon === null) {
            throw new \DomainException('Coupon no longer exists.');
        }
        $this->validateApplicability($coupon, $invoice);
        $existing = CouponRedemption::findOne(['invoice_id' => (int) $invoice->id]);
        if ($existing !== null) {
            $existing->coupon_id = (int) $coupon->id;
            $existing->user_id = (int) $invoice->user_id;
            $existing->coupon_code = $code;
            $existing->discount_amount = number_format((float) $invoice->discount_amount, 2, '.', '');
            if (!$existing->save()) {
                throw new \RuntimeException('Coupon redemption could not be updated.');
            }
            return;
        }

        $redemption = new CouponRedemption();
        $redemption->coupon_id = (int) $coupon->id;
        $redemption->user_id = (int) $invoice->user_id;
        $redemption->invoice_id = (int) $invoice->id;
        $redemption->coupon_code = $code;
        $redemption->discount_amount = number_format((float) $invoice->discount_amount, 2, '.', '');
        $redemption->redeemed_at = time();
        if (!$redemption->save()) {
            throw new \RuntimeException('Coupon redemption could not be recorded.');
        }
    }

    public function removeForInvoice(int $invoiceId): void
    {
        CouponRedemption::deleteAll(['invoice_id' => $invoiceId]);
    }

    private function validateUsageLimits(Coupon $coupon, int $userId, ?int $excludeInvoiceId): void
    {
        $globalQuery = CouponRedemption::find()->where(['coupon_id' => $coupon->id]);
        $userQuery = CouponRedemption::find()->where(['coupon_id' => $coupon->id, 'user_id' => $userId]);

        if ($excludeInvoiceId !== null) {
            $condition = ['<>', 'invoice_id', $excludeInvoiceId];
            $globalQuery->andWhere($condition);
            $userQuery->andWhere($condition);
        }
        $globalLimit = (int) $coupon->uses_per_coupon;
        if ($globalLimit > 0 && (int) $globalQuery->count() >= $globalLimit) {
            throw new \DomainException('Coupon usage limit has been reached.');
        }
        $userLimit = (int) $coupon->uses_per_user;
        if ($userLimit > 0 && (int) $userQuery->count() >= $userLimit) {
            throw new \DomainException('This customer has already reached the usage limit for this coupon.');
        }
    }

    private function validateApplicability(Coupon $coupon, Invoice $invoice): void
    {
        $allowedTypes = CouponApplicability::find()->select('item_type')->where(['coupon_id' => $coupon->id])->column();
        if (!$allowedTypes) {
            return;
        }
        $invoiceTypes = InvoiceItem::find()->select('item_type')->where(['invoice_id' => $invoice->id])->column();
        $matched = array_intersect($allowedTypes, $invoiceTypes);
        if (!$matched) {
            throw new \DomainException('Coupon cannot be used for this service.');
        }
    }

    private function validateAssignedUser(Coupon $coupon, int $userId): void
    {
        $assignments = CouponAssignedUser::find()->where(['coupon_id' => $coupon->id])->count();
        if ($assignments == 0) {
            return;
        }
        $allowed = CouponAssignedUser::find()->where(['coupon_id' => $coupon->id, 'user_id' => $userId])->exists();
        if (!$allowed) {
            throw new \DomainException('This coupon is not assigned to your account.');
        }
    }

    public function calculateForInvoice(Invoice $invoice, string $couponCode): float
    {
        if ($invoice->getIsNewRecord()) {
            throw new \DomainException('Invoice must exist before a coupon can be validated.');
        }

        if ((int) $invoice->user_id <= 0) {
            throw new \DomainException('Invoice customer is invalid.');
        }

        $subtotal = round((float) $invoice->subtotal_amount, 2);
        if ($subtotal <= 0) {
            throw new \DomainException('Invoice subtotal must be greater than zero.');
        }

        return $this->calculate($couponCode, (int) $invoice->user_id, $subtotal, (int) $invoice->id);
    }
}
