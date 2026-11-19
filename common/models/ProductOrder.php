<?php

declare(strict_types=1);

namespace common\models;

use common\components\AfterCommit;
use common\models\traits\AutoTimestampTrait;
use common\services\notification\NotificationService;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * A customer's order of one licence of a store product, paid through its own invoice. Once that invoice is paid
 * (proof approved by staff, or Razorpay or PayPal) the order gets a licence key and its file can be downloaded.
 *
 * @property-read Product $product
 * @property-read Invoice $invoice
 */
class ProductOrder extends base\ProductOrder
{
    use AutoTimestampTrait;

    #[\Override]
    public function behaviors(): array
    {
        return ArrayHelper::merge(parent::behaviors(), [TimestampBehavior::class]);
    }

    #[\Override]
    public function getProduct(): ActiveQuery
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    #[\Override]
    public function getInvoice(): ActiveQuery
    {
        return $this->hasOne(Invoice::class, ['id' => 'invoice_id']);
    }

    /** Paid and still so: a refunded invoice, or a payment taken back, closes the key and download. */
    public function isPaid(): bool
    {
        return $this->paid_at !== null && $this->invoice->status === Invoice::STATUS_PAID;
    }

    /** e.g. "Single site" */
    public function licenceLabel(): string
    {
        return Product::LICENCES[$this->licence] ?? $this->licence;
    }

    /**
     * Called when an invoice is paid, with the invoices that payment covers (its own, and any carried into it):
     * their unpaid orders become paid and get a licence key. Runs in the payment's database transaction.
     *
     * @param int[] $invoiceIds
     */
    public static function markPaid(array $invoiceIds): void
    {
        foreach (self::find()->where(['invoice_id' => $invoiceIds, 'paid_at' => null])->with('product')->all() as $order) {
            $order->paid_at = time();
            $order->licence_key = self::newKey();
            $order->save(false);
            $userId = (int) $order->user_id;
            $message = $order->product->name . ' (' . strtolower($order->licenceLabel()) . ' licence) is paid. Your licence key and download are on My Purchases.';
            // After commit, and never able to undo the payment.
            AfterCommit::run(static function () use ($userId, $message): void {
                try {
                    (new NotificationService())->create($userId, 'store', 'Your purchase is ready', $message, '/purchase/index');
                } catch (\Throwable $e) {
                    \Yii::error(['message' => 'Purchase notification failed.', 'userId' => $userId, 'error' => $e->getMessage()], __METHOD__);
                }
            });
        }
    }

    /** e.g. 3F9A2-C41B7-0D6E8-B52F1 */
    private static function newKey(): string
    {
        return implode('-', str_split(strtoupper(bin2hex(random_bytes(10))), 5));
    }
}
