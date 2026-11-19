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
 * @property-read SupportTicket|null $supportTicket the job's ticket (services and plans), opened once paid
 * @property-read Maintenance|null $maintenance the maintenance a paid care plan or software subscription started
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
    public function getMaintenance(): ActiveQuery
    {
        return $this->hasOne(Maintenance::class, ['id' => 'maintenance_id']);
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

    /** e.g. "Single site"; "Fixed price" for a service; "Monthly" for a care plan */
    public function licenceLabel(): string
    {
        if ($this->product->isPlan()) {
            return 'Monthly';
        }
        if ($this->product->isService()) {
            return 'Fixed price';
        }

        return $this->product->licenceName((string) $this->licence);
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
            if ($order->product->isPlan()) {
                // The first month is paid: the plan carries on as a maintenance service, billed every month by the
                // usual renewals. Its ticket is where the customer sends access and asks for work.
                $order->maintenance_id = (int) $order->startMaintenance(1)->id;
                $ticket = $order->openTicket();
                $order->support_ticket_id = (int) $ticket->id;
                $message = $order->product->name . ' has started. Send us access to your website in ticket ' . $ticket->ticket_number . ': it is on My Purchases.';
            } elseif ($order->product->isSoftware()) {
                // A subscription: the key works while its maintenance (monthly or yearly) is paid up.
                $order->licence_key = self::newKey();
                $order->licence_site = self::hostOf((string) $order->website_url);
                $order->maintenance_id = (int) $order->startMaintenance(Product::SUBSCRIPTION_MONTHS[$order->licence] ?? 1)->id;
                $message = $order->product->name . ' (' . strtolower($order->licenceLabel()) . ' subscription) is paid. Your licence key and download are on My Purchases.';
            } elseif ($order->product->isService()) {
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
            'subject' => mb_substr(($this->product->isPlan() ? 'Care plan: ' : 'Order: ') . $this->product->name, 0, 255),
            'message' => trim((string) $this->brief) . ($this->website_url ? "\n\nWebsite: " . $this->website_url : '')
                . ($this->product->isPlan()
                    ? "\n\n(Care plan " . $this->product->name . ' started. Please reply with access to the website (WordPress admin or hosting) so we can begin.)'
                    : "\n\n(Paid order for " . $this->product->name . ', delivery in ' . (int) $this->product->delivery_days . ' working days.)'),
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

    /**
     * Maintenance from today for $months, renewing every $months through the usual renewals. In India at the
     * order's rupee price on its invoice line. Abroad, renewals are rupee invoices paid by PayPal with its fee added:
     * priced so that charge is about the dollar price.
     */
    private function startMaintenance(int $months): Maintenance
    {
        // The order's own line: plans and software both bill their first period as maintenance (StoreOrderService::buy).
        $line = InvoiceItem::findOne(['invoice_id' => $this->invoice_id, 'item_type' => InvoiceItem::TYPE_MAINTENANCE]);
        $invoice = $this->invoice;
        $periodPrice = $invoice->isUsdPriced() && $line !== null && $line->usd_unit_price !== null
            ? \common\services\payment\PayPalService::inrCharging((float) $line->usd_unit_price, (float) $invoice->usd_rate_locked)
            : ($line !== null ? (float) $line->unit_price : (float) $this->product->price((string) $this->licence, false));
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $period = (new \common\services\billing\RenewalPeriodCalculator())->calculate($yesterday, $months, \common\services\billing\RenewalPeriodCalculator::anchorDayFor($yesterday));
        $maintenance = new Maintenance();
        $maintenance->setAttributes([
            'user_id' => (int) $this->user_id,
            'website_url' => (string) $this->website_url,
            'start_date' => $period['start'],
            'current_expiry_date' => $period['end'],
            'renewal_anchor_day' => \common\services\billing\RenewalPeriodCalculator::anchorDayFor($yesterday),
            'renewal_cycle_months' => $months,
            // A subscription renews at its price: the maintenance price rise every few years is not in its terms.
            'renewal_increase_percent' => $this->product->isSoftware() ? 0 : null,
            'renewal_base_amount' => $periodPrice,
            'status' => Maintenance::STATUS_ACTIVE,
            'created_at' => time(),
            'updated_at' => time(),
        ], false);
        $maintenance->save(false);

        return $maintenance;
    }

    /** Days after a software subscription's paid period ends before its key stops working: time to pay the renewal. */
    public const LICENCE_GRACE_DAYS = 7;

    /**
     * Whether the licence key works now. A theme or plugin: while paid. Software: while paid and its subscription
     * (the maintenance it started) is active and paid up, with LICENCE_GRACE_DAYS to pay a renewal.
     */
    public function licenceActive(): bool
    {
        if (!$this->isPaid()) {
            return false;
        }
        if (!$this->product->isSoftware()) {
            return true;
        }
        $subscription = $this->maintenance;

        return $subscription !== null && (string) $subscription->current_expiry_date >= self::licenceCutoff((int) $subscription->status === Maintenance::STATUS_CANCELLED);
    }

    /**
     * The earliest paid-up date a subscription's key still works with: a cancelled one is not renewed but runs to the
     * end of what is paid; an active one gets LICENCE_GRACE_DAYS to pay its renewal. A refund of the first invoice
     * ends the subscription (licence terms: a refund ends the licence), so isPaid() is checked first.
     */
    public static function licenceCutoff(bool $cancelled): string
    {
        return $cancelled ? date('Y-m-d') : date('Y-m-d', strtotime('-' . self::LICENCE_GRACE_DAYS . ' days'));
    }

    /** licenceActive() as SQL over a joined maintenance $m, for the paid orders of a software product. */
    public static function licenceActiveCondition(string $m): array
    {
        return ['or',
            ['and', ['<>', $m . '.status', Maintenance::STATUS_CANCELLED], ['>=', $m . '.current_expiry_date', self::licenceCutoff(false)]],
            ['and', [$m . '.status' => Maintenance::STATUS_CANCELLED], ['>=', $m . '.current_expiry_date', self::licenceCutoff(true)]],
        ];
    }

    /** "billing.example.com" from "https://billing.example.com/path"; lower case, no "www.". */
    public static function hostOf(string $url): ?string
    {
        $host = parse_url(preg_match('#^https?://#i', trim($url)) ? trim($url) : 'https://' . trim($url), PHP_URL_HOST);
        $host = is_string($host) ? preg_replace('/^www\./', '', rtrim(mb_strtolower($host), '.')) : '';
        // An internationalised name (bücher.de) as its ASCII form (xn--bcher-kva.de), as browsers send it.
        if (preg_match('/[^\x20-\x7e]/', $host) === 1) {
            $host = function_exists('idn_to_ascii') ? (string) idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) : '';
        }

        // A dotted host name (billing.example.com), nothing else: it must match the copy's own address exactly.
        return str_contains($host, '.') && mb_strlen($host) <= 255 && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false ? $host : null;
    }

    /** e.g. 3F9A2-C41B7-0D6E8-B52F1 */
    private static function newKey(): string
    {
        return implode('-', str_split(strtoupper(bin2hex(random_bytes(10))), 5));
    }
}
