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
 * @property-read SupportTicket|null $supportTicket the job's ticket (services), opened once paid
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

    #[\Override]
    public function getSupportTicket(): ActiveQuery
    {
        return $this->hasOne(SupportTicket::class, ['id' => 'support_ticket_id']);
    }

    /** Paid and still so: a refunded invoice, or a payment taken back, closes the key and download. */
    public function isPaid(): bool
    {
        return $this->paid_at !== null && $this->invoice->status === Invoice::STATUS_PAID;
    }

    /** End of the support period: Product::SUPPORT_MONTHS from payment (31 Aug + 6 months is 28/29 Feb, not 3 Mar). */
    public function supportUntil(): ?int
    {
        if ($this->paid_at === null) {
            return null;
        }
        $paid = (new \DateTimeImmutable('@' . (int) $this->paid_at))->setTimezone(new \DateTimeZone(\Yii::$app->timeZone));
        $end = $paid->modify('+' . Product::SUPPORT_MONTHS . ' months');
        if ($end->format('j') !== $paid->format('j')) {
            $end = $paid->modify('last day of +' . Product::SUPPORT_MONTHS . ' months')->setTime((int) $paid->format('G'), (int) $paid->format('i'));
        }

        return $end->getTimestamp();
    }

    /**
     * paid; refunded (was paid, its money given back: key and download closed); checking (a payment proof under
     * review); due; or cancelled.
     */
    public function state(): string
    {
        if ($this->isPaid()) {
            return 'paid';
        }
        $status = $this->invoice->status;
        if ($this->paid_at !== null || $status === Invoice::STATUS_REFUNDED) {
            return 'refunded';
        }
        if (in_array($status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true)) {
            return (new \common\services\billing\InvoicePaymentService())->hasPendingProof((int) $this->invoice_id) ? 'checking' : 'due';
        }

        return 'cancelled';
    }

    /** e.g. "Single site"; "Fixed price" for a service */
    public function licenceLabel(): string
    {
        if ($this->product->isService()) {
            return 'Fixed price';
        }

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
            if ($order->product->isService()) {
                // The job: talked over and delivered in its own ticket, which staff resolve when it is done.
                $ticket = $order->openTicket();
                $order->support_ticket_id = (int) $ticket->id;
                $message = $order->product->name . ' is paid. We have opened ticket ' . $ticket->ticket_number . ' for the job: follow it on My Purchases.';
            } else {
                $order->licence_key = self::newKey();
                $message = $order->product->name . ' (' . strtolower($order->licenceLabel()) . ' licence) is paid. Your licence key and download are on My Purchases.';
            }
            $order->save(false);
            $userId = (int) $order->user_id;
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

    private function openTicket(): SupportTicket
    {
        $number = SupportTicket::newNumber();
        $ticket = new SupportTicket();
        $ticket->setAttributes([
            'ticket_number' => $number,
            'user_id' => (int) $this->user_id,
            'subject' => mb_substr('Order: ' . $this->product->name, 0, 255),
            'message' => trim((string) $this->brief) . ($this->website_url ? "\n\nWebsite: " . $this->website_url : '')
                . "\n\n(Paid order for " . $this->product->name . ', delivery in ' . (int) $this->product->delivery_days . ' working days.)',
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_OPEN,
            'created_at' => time(),
            'updated_at' => time(),
        ], false);
        $ticket->save(false);
        $username = (string) ($this->user->username ?? '');
        AfterCommit::run(static function () use ($ticket, $username): void {
            try {
                \common\services\admin\AdminAlertService::supportTicketCreated($ticket->ticket_number, $username, $ticket->subject);
            } catch (\Throwable $e) {
                \Yii::error(['message' => 'Service order ticket alert failed.', 'ticket' => $ticket->ticket_number, 'error' => $e->getMessage()], __METHOD__);
            }
        });

        return $ticket;
    }

    /** e.g. 3F9A2-C41B7-0D6E8-B52F1 */
    private static function newKey(): string
    {
        return implode('-', str_split(strtoupper(bin2hex(random_bytes(10))), 5));
    }
}
