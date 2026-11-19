<?php

declare(strict_types=1);

namespace common\services;

use common\models\CustomerBillingProfile;
use common\models\forms\ManualInvoiceItemForm;
use common\models\Invoice;
use common\models\Product;
use common\models\ProductOrder;
use common\services\billing\InvoiceCancellationService;
use common\services\billing\InvoicePaymentService;
use common\services\billing\ManualInvoiceService;
use Yii;

/**
 * Buying from the store: an order of one licence and its own invoice, in rupees with GST (where it applies) for
 * buyers in India, in US dollars for buyers abroad. The invoice page then offers UPI or bank transfer always, and
 * Razorpay or PayPal when they are on.
 */
final class StoreOrderService
{
    /** Days to pay. Unpaid store invoices are never carried onto a renewal invoice. */
    public const DAYS_TO_PAY = 7;

    /** Days after the due date an unpaid order is cancelled with its invoice (cronned daily). */
    public const CANCEL_AFTER_DAYS = 7;

    public function __construct(private readonly ManualInvoiceService $invoices = new ManualInvoiceService())
    {
    }

    /** The invoice to pay: a new one, or the one already open for this same product and licence. */
    public function buy(int $userId, Product $product, string $licence): Invoice
    {
        if (!$product->isPublic()) {
            throw new \DomainException('This product is no longer for sale.');
        }
        $profile = CustomerBillingProfile::findOne(['user_id' => $userId]) ?? throw new \DomainException('Please complete your billing details first.');
        $usd = $profile->isForeign();
        $price = $product->price($licence, $usd) ?? throw new \DomainException('This licence is not offered for this product.');

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            // One buyer at a time, so a double click makes one order.
            $db->createCommand('SELECT id FROM {{%user}} WHERE id = :id FOR UPDATE')->bindValue(':id', $userId)->execute();
            $open = $this->openOrder($userId, (int) $product->id, $licence);
            if ($open !== null) {
                $transaction->commit();

                return $open->invoice;
            }
            $invoice = new Invoice(['user_id' => $userId, 'due_date' => date('Y-m-d', strtotime('+' . self::DAYS_TO_PAY . ' days'))]);
            $invoice->priceCurrency = $usd ? Invoice::CURRENCY_USD : Invoice::CURRENCY_INR;
            $item = new ManualInvoiceItemForm([
                'category' => Product::KINDS[$product->kind] . ' licence',
                'description' => $product->name . ' (' . $product->typeLabel() . '), ' . strtolower(Product::LICENCES[$licence]) . ' licence'
                    . ($product->version ? ', version ' . $product->version : '') . '. Includes setup help and 6 months of support.',
                'unit_price' => $price,
            ]);
            $this->invoices->create($invoice, [$item]);
            $order = new ProductOrder(['user_id' => $userId, 'product_id' => (int) $product->id, 'licence' => $licence, 'invoice_id' => (int) $invoice->id]);
            if (!$order->save()) {
                throw new \RuntimeException('Store order could not be saved: ' . json_encode($order->errors));
            }
            $transaction->commit();

            return $invoice;
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
    }

    /** The buyer no longer wants an unpaid order: its invoice is cancelled. */
    public function cancel(ProductOrder $order, string $reason = 'Store order cancelled by the customer.'): void
    {
        $payments = new InvoicePaymentService();
        if ($order->isPaid() || $payments->getCapturedAmount((int) $order->invoice_id) > 0) {
            throw new \DomainException('A payment was made for this order, so it cannot be cancelled here. Please contact us about a refund.');
        }
        if ($payments->hasPendingProof((int) $order->invoice_id)) {
            throw new \DomainException('We are checking your payment for this order, so it cannot be cancelled now. Please contact us if you no longer want it.');
        }
        (new InvoiceCancellationService())->cancel($order->invoice, $reason);
    }

    /** Abandoned checkouts: unpaid orders well past their due date, with no payment taken or awaiting review. */
    public function cancelAbandoned(): int
    {
        $orders = ProductOrder::find()->alias('o')->innerJoinWith('invoice i')
            ->where(['o.paid_at' => null, 'i.status' => Invoice::STATUS_UNPAID])
            ->andWhere(['<', 'i.due_date', date('Y-m-d', strtotime('-' . self::CANCEL_AFTER_DAYS . ' days'))])
            ->all();
        $cancelled = 0;
        foreach ($orders as $order) {
            try {
                $this->cancel($order, 'Store order not paid in time.');
                $cancelled++;
            } catch (\DomainException) {
                // A payment came in or is being checked: it stays.
            }
        }

        return $cancelled;
    }

    private function openOrder(int $userId, int $productId, string $licence): ?ProductOrder
    {
        return ProductOrder::find()->alias('o')->innerJoinWith('invoice i')
            ->where(['o.user_id' => $userId, 'o.product_id' => $productId, 'o.licence' => $licence, 'o.paid_at' => null])
            ->andWhere(['i.status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]])
            ->one();
    }
}
