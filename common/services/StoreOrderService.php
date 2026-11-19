<?php

declare(strict_types=1);

namespace common\services;

use common\models\CustomerBillingProfile;
use common\models\forms\ManualInvoiceItemForm;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Product;
use common\models\ProductOrder;
use common\models\User;
use common\services\billing\InvoiceCancellationService;
use common\services\billing\InvoicePaymentService;
use common\services\billing\ManualInvoiceService;
use common\services\mail\CustomerEmailService;
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

    /**
     * The invoice to pay: a new one, or the one already open for this same product and licence. A service needs the
     * buyer's brief (and may have their website); an open order for it takes the latest ones.
     */
    public function buy(int $userId, Product $product, string $licence, string $brief = '', string $website = ''): Invoice
    {
        if (!$product->isPublic()) {
            throw new \DomainException('This product is no longer for sale.');
        }
        $brief = trim($brief);
        $website = trim($website);
        if (!$product->hasLicence()) {
            if ($product->isService() && (mb_strlen($brief) < 20 || mb_strlen($brief) > 5000)) {
                throw new \DomainException('Tell us what you need done, in 20 to 5,000 characters.');
            }
            if (mb_strlen($brief) > 5000) {
                throw new \DomainException('Keep the note to 5,000 characters.');
            }
            if ($product->isPlan() && !self::plansRenew()) {
                throw new \DomainException('Care plans cannot be started online right now. Please contact us and we will set yours up.');
            }
            if ($product->isPlan() && $website === '') {
                throw new \DomainException('Give the address of the website the plan is for.');
            }
            if ($website !== '' && !preg_match('#^https?://#i', $website)) {
                $website = 'https://' . $website;
            }
            if ($website !== '' && (mb_strlen($website) > 255 || !(new \yii\validators\UrlValidator())->validate($website))) {
                throw new \DomainException('The website address does not look right, e.g. https://example.com');
            }
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
                if (!$product->hasLicence()) {
                    $open->updateAttributes(['brief' => $brief, 'website_url' => $website ?: null]);
                    $line = InvoiceItem::findOne(['invoice_id' => $open->invoice_id, 'item_type' => [InvoiceItem::TYPE_SERVICE_ORDER, InvoiceItem::TYPE_MAINTENANCE]]);
                    $line?->updateAttributes(['description' => $this->serviceLine($product, $website)]);
                }
                $transaction->commit();

                return $open->invoice;
            }
            $invoice = new Invoice(['user_id' => $userId, 'due_date' => date('Y-m-d', strtotime('+' . self::DAYS_TO_PAY . ' days'))]);
            $invoice->priceCurrency = $usd ? Invoice::CURRENCY_USD : Invoice::CURRENCY_INR;
            $item = new ManualInvoiceItemForm(!$product->hasLicence() ? [
                'category' => $product->isPlan() ? 'Maintenance' : 'Service',
                'description' => $this->serviceLine($product, $website),
                'unit_price' => $price,
                // A plan's first month is maintenance: coupons for maintenance apply, and the books file it there.
                'item_type' => $product->isPlan() ? InvoiceItem::TYPE_MAINTENANCE : InvoiceItem::TYPE_SERVICE_ORDER,
            ] : [
                'category' => Product::KINDS[$product->kind] . ' licence',
                'description' => $product->name . ' (' . $product->typeLabel() . '), ' . strtolower(Product::LICENCES[$licence]) . ' licence'
                    . ($product->version ? ', version ' . $product->version : '') . '. Includes setup help and 6 months of support.',
                'unit_price' => $price,
                'item_type' => InvoiceItem::TYPE_PRODUCT,
            ]);
            $this->invoices->create($invoice, [$item]);
            $order = new ProductOrder(['user_id' => $userId, 'product_id' => (int) $product->id, 'licence' => $licence, 'invoice_id' => (int) $invoice->id]
                + (!$product->hasLicence() ? ['brief' => $brief, 'website_url' => $website ?: null] : []));
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
        // Paid once (even if refunded since): not an abandoned order to cancel.
        if ($order->paid_at !== null || $order->isPaid() || $payments->getCapturedAmount((int) $order->invoice_id) > 0) {
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

    /**
     * Tells every buyer with a paid licence that a new version can be downloaded. Sent one by one: a failure is
     * logged and skipped.
     * ponytail: sent during the staff request; queue it if a product ever has hundreds of buyers.
     *
     * @return array{int, int} sent and failed
     */
    public function notifyUpdate(Product $product): array
    {
        $buyers = self::paidBuyers($product)->all();
        $version = $product->version ? ' version ' . $product->version : ' a new version';
        $body = "Hello {name},\n\n" . $product->name . ' has' . $version . " ready for you. Download it from My Purchases in your account: updates are free.\n\n"
            . ($product->changelog ? "What's new:\n" . $product->changelog . "\n\n" : '')
            . 'Need help updating? Reply to this email or raise a support ticket.';
        $mail = new CustomerEmailService();
        $subject = $product->name . ':' . $version . ' is ready';
        $sent = $failed = 0;
        $sentTo = [];
        foreach ($buyers as $buyer) {
            try {
                $mail->send($buyer, $subject, $body, false, false);
                $sent++;
                $sentTo[] = (string) $buyer->email;
            } catch (\Throwable $e) {
                $failed++;
                Yii::error(['message' => 'Store update email failed.', 'userId' => (int) $buyer->id, 'productId' => (int) $product->id, 'error' => $e->getMessage()], __METHOD__);
            }
        }

        if ($sentTo !== []) {
            try {
                $mail->sendCopy($subject, $body, $sentTo);
            } catch (\Throwable $e) {
                Yii::error(['message' => 'Store update copy email failed.', 'error' => $e->getMessage()], __METHOD__);
            }
        }

        return [$sent, $failed];
    }

    private function serviceLine(Product $product, string $website): string
    {
        if ($product->isPlan()) {
            return $product->name . ' (' . $product->typeLabel() . ') for ' . $website . ': the first month, from the day the payment is confirmed. Renews monthly; cancel any time before the next renewal.';
        }

        return $product->name . ' (' . $product->typeLabel() . '), fixed price, delivered in ' . (int) $product->delivery_days . ' working days.'
            . ($website !== '' ? ' Website: ' . $website : '');
    }

        /** Whether monthly renewals run (maintenance auto-billing and the daily billing job are on): a plan needs them. */
    public static function plansRenew(): bool
    {
        return (bool) \common\models\BillingSettings::forType(InvoiceItem::TYPE_MAINTENANCE)->is_active && \common\models\CronJob::isEnabled('cron/run-billing');
    }

    /** Customers holding a paid (not refunded) licence of the product, each once; deleted accounts left out. */
    public static function paidBuyers(Product $product): \yii\db\ActiveQuery
    {
        if (!$product->hasLicence()) {
            return User::find()->where('0=1'); // services and plans have no file to update
        }

        return User::find()->where(['id' => ProductOrder::find()->alias('o')->innerJoinWith('invoice i', false)
            ->select('o.user_id')->where(['o.product_id' => $product->id, 'i.status' => Invoice::STATUS_PAID])->andWhere(['not', ['o.paid_at' => null]])])
            ->andWhere(['not', ['status' => User::getStatusDeleted()]]);
    }

    private function openOrder(int $userId, int $productId, string $licence): ?ProductOrder
    {
        return ProductOrder::find()->alias('o')->innerJoinWith('invoice i')
            ->where(['o.user_id' => $userId, 'o.product_id' => $productId, 'o.licence' => $licence, 'o.paid_at' => null])
            ->andWhere(['i.status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]])
            ->one();
    }
}
