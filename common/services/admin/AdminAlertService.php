<?php

declare(strict_types=1);

namespace common\services\admin;

use common\models\AdminNotification;
use common\services\notification\AdminNotificationService;
use common\services\notification\WhatsAppService;
use Yii;

/**
 * Description of AdminAlertService
 *
 * @author trivedi
 */
final class AdminAlertService extends AdminNotificationService
{
    public static function supportTicketCreated(string $ticketNumber, string $customer, string $subject): void
    {
        $title = 'New Support Ticket';
        $message = "Ticket: {$ticketNumber}\n" . "Customer: {$customer}\n" . "Subject: {$subject}";
        self::send($title, $message, 'support', '/support-ticket/view?number=' . rawurlencode($ticketNumber), true);
    }

    public static function supportCustomerReply(string $ticketNumber, string $customer): void
    {
        $title = 'Customer Replied';
        $message = "Ticket: {$ticketNumber}\n" . "Customer: {$customer}";
        self::send($title, $message, 'support', '/support-ticket/view?number=' . rawurlencode($ticketNumber), true);
    }

    public static function paymentSubmitted(string $invoiceNumber, string $customer, float $amount): void
    {
        $title = 'Payment Submitted';
        $message = "Invoice: {$invoiceNumber}\n" . "Customer: {$customer}\n" . "Amount: ₹{$amount}";
        self::send($title, $message, 'payment', '/transaction/index?' . http_build_query(['TransactionSearch' => ['status' => 'Pending']]));
    }

    public static function leadCreated(string $name, string $email): void
    {
        $title = 'New Lead';
        $message = "Name: {$name}\n" . "Email: {$email}";
        self::send($title, $message, 'lead', '/lead/index', true);
    }

    public static function auditRequestCreated(string $website): void
    {
        $title = 'New Audit Request';
        $message = "Website: {$website}";
        self::send($title, $message, 'lead', '/audit-request/index', true);
    }

    public static function testimonialSubmitted(string $clientName): void
    {
        self::send('New Testimonial', "From: {$clientName}\nReview it before publishing.", 'lead', '/testimonial/index');
    }

    public static function serviceExpiring(string $service, string $expiryDate, bool $hasPrice = true): void
    {
        $title = 'Service Expiring';
        $message = "Service: {$service}\n" . "Expiry Date: {$expiryDate}" . ($hasPrice ? '' : "\nNo renewal price is set: no invoice will be generated. Set the Renewal Base Amount.");
        self::send($title, $message, 'renewal', '/renewal-monitoring/index');
    }

    public static function invoiceGenerated(string $invoiceNumber, float $amount): void
    {
        $title = 'Invoice Generated';
        $message = "Invoice: {$invoiceNumber}\n" . "Amount: ₹{$amount}";
        self::send($title, $message, 'invoice', '/invoice/view?number=' . rawurlencode($invoiceNumber));
    }

    public static function paymentUnapplied(string $invoiceNumber, string $invoiceStatus, float $amount, string $paymentId): void
    {
        $title = 'Online Payment Needs Attention';
        $message = "Invoice: {$invoiceNumber} ({$invoiceStatus})\n" . "Amount: ₹{$amount}\n" . "Razorpay payment: {$paymentId}\n" . 'The customer was charged but the invoice could not accept it. Open the payment here and use Refund or Apply.';
        self::send($title, $message, 'payment', '/transaction/index?' . http_build_query(['TransactionSearch' => ['gateway_payment_id' => $paymentId]]), true);
    }

    public static function paymentNotRecorded(string $orderId, string $paymentId, float $amount, string $reason): void
    {
        $title = 'Online Payment Not Recorded';
        // Razorpay may deliver the same event again (timeouts, manual resend): alert once per payment.
        if (AdminNotification::find()->where(['title' => $title])->andWhere(['like', 'message', $paymentId])->exists()) {
            return;
        }
        $message = "Razorpay order: {$orderId}\n" . "Razorpay payment: {$paymentId}\n" . "Amount: ₹{$amount}\n" . "Reason: {$reason}\n" . 'The customer was charged but no invoice was updated. Check the payment in Razorpay and refund it or record it manually.';
        self::send($title, $message, 'payment', '/transaction/index', true);
    }

    public static function paymentOverpaid(string $invoiceNumber, float $paid, float $excess, string $paymentId, bool $online = true): void
    {
        $title = ($online ? 'Online' : 'Approved') . ' Payment Needs Attention';
        $message = "Invoice: {$invoiceNumber}\n" . ($online ? 'Paid online' : 'Approved proof') . ": ₹{$paid}\n" . "Overpaid by: ₹{$excess}\n" . ($online ? 'Razorpay payment' : 'Reference') . ": {$paymentId}\n"
            . 'The invoice had already been partly paid another way. Refund the excess' . ($online ? ' using Refund on the payment here.' : ' to the customer.');
        self::send($title, $message, 'payment', '/transaction/index?' . http_build_query(['TransactionSearch' => ['gateway_payment_id' => $paymentId]]), true);
    }

    public static function supersededOrderPaid(string $invoiceNumber, float $paid, float $invoiceTotal, string $paymentId): void
    {
        $title = 'Online Payment Needs Attention';
        $message = "Invoice: {$invoiceNumber}\n" . "Paid: ₹{$paid} (invoice total now ₹{$invoiceTotal})\n" . "Razorpay payment: {$paymentId}\n" . 'The customer paid an older online order after changing the coupon. Refund any excess using Refund on the payment here; a shortfall stays on the invoice.';
        self::send($title, $message, 'payment', '/transaction/index?' . http_build_query(['TransactionSearch' => ['gateway_payment_id' => $paymentId]]), true);
    }

    /** From the sync, every 30 minutes: alert once per payment and amount. */
    public static function refundNotRecorded(string $paymentId, float $razorpayRefunded, float $recorded, int $transactionId): void
    {
        $title = 'Razorpay Refund Not Recorded';
        // With the line end, so ₹100 doesn't count as already alerted when ₹1000 was.
        $shown = "Razorpay shows refunded: ₹{$razorpayRefunded}\n";
        if (AdminNotification::find()->where(['title' => $title])->andWhere(['like', 'message', $paymentId])->andWhere(['like', 'message', $shown])->exists()) {
            return;
        }
        $unrecorded = round($razorpayRefunded - $recorded, 2);
        $message = "Razorpay payment: {$paymentId}\n" . $shown . "Recorded here: ₹{$recorded}\n" . ($unrecorded < 0
            ? 'Razorpay shows less refunded than recorded here: a refund may have failed or been reversed, so the customer may not have this money. Check it in the Razorpay dashboard.'
            : "Revenue and the invoice are wrong until this is recorded. Open the payment first: if it already shows this refund, do nothing, because pressing Refund would send new money. Otherwise use Refund with exactly ₹{$unrecorded}: that only writes the record.");
        self::send($title, $message, 'payment', '/transaction/view?id=' . $transactionId, true);
    }

    public static function backupFailed(string $reason): void
    {
        self::send('Backup Failed', "Reason: {$reason}\n" . 'Check the server before the next scheduled run.', 'billing', '/admin/cron-log');
    }

    public static function invoiceGenerationFailed(int $userId, string $reason): void
    {
        $title = 'Automatic Invoice Failed';
        // Retried on every run, so at most one a day per customer.
        $client = "Client ID: {$userId}\n";
        self::sendOncePerDay($title, $client . "Reason: {$reason}\n" . 'It will be retried on the next billing run.', 'billing', '/customer-billing-profile/index', $client, true);
    }

    public static function renewalSkipped(string $service, string $reason): void
    {
        $title = 'Renewal Not Billed';
        $message = "Service: {$service}\n" . "Reason: {$reason}\n" . 'The rest of the billing run went ahead. Fix the service (expiry date, cycle, price) and it is billed on the next run.';
        self::send($title, $message, 'billing', '/renewal-monitoring/index', true);
    }

    public static function adminPasswordReset(string $username): void
    {
        self::send('Admin Password Reset', "Admin: {$username}\n" . 'The password was changed through a "Forgot password" email link. If this was not expected, check the account now.', 'system', null, true);
    }

    public static function billingRunFailed(string $reason): void
    {
        $message = "Reason: {$reason}\n" . 'No renewal invoices were created by this run. It runs again at the next scheduled time; fix the cause before then.';
        self::send('Billing Run Failed', $message, 'billing', '/admin/cron-log', true);
    }

    /** At most one a day for each job: a job running every 30 minutes must not flood the inbox. */
    public static function cronJobFailed(string $job, string $reason): void
    {
        self::sendOncePerDay('Cron Job Failed: ' . $job, "Reason: {$reason}\n" . 'Further failures of this job today are not emailed. It runs again at its next scheduled time.', 'billing', '/admin/cron-log');
    }

    public static function cronJobSwitched(string $job, bool $enabled, string $by): void
    {
        $state = $enabled ? 'on' : 'off';
        self::send("Cron Job Turned " . ucfirst($state) . ': ' . $job, "{$by} turned this job {$state} in the admin panel.", 'billing', '/admin/cron-log');
    }

    /** @param string[] $customers one line each */
    public static function profilesIncomplete(array $customers): void
    {
        self::sendOncePerDay('Incomplete Customer Profiles', 'Customers with incomplete billing details (reminded daily; a customer already reminded today is not emailed again):' . "\n" . implode("\n", $customers), 'billing', '/customer-billing-profile/index');
    }

    /** A service came back because the customer paid: staff suspended it, so they should know. */
    public static function serviceReactivatedByPayment(string $service, string $invoiceNumber): void
    {
        self::send('Service Reactivated By Payment', "Service: {$service}\n" . "Invoice: {$invoiceNumber}\n" . 'It was suspended or overdue and is Active again because this renewal was paid. Suspend it again if that is not what you want.', 'billing', '/renewal-monitoring/index');
    }

    public static function paymentApprovedOnClosedInvoice(string $invoiceNumber, string $invoiceStatus, float $amount, int $transactionId): void
    {
        $message = "Invoice: {$invoiceNumber} ({$invoiceStatus})\n" . "Amount: ₹{$amount}\n" . 'The proof was approved but the invoice could not accept it, so the money is held on the payment. Open it and refund or apply it.';
        self::send('Approved Payment Needs Attention', $message, 'payment', '/transaction/view?id=' . $transactionId, true);
    }

    public static function webhookFailed(string $event, string $reason): void
    {
        self::sendOncePerDay('Razorpay Webhook Failed', "Event: {$event}\n" . "Reason: {$reason}\n" . 'Razorpay retries it; further failures today are not emailed. The sync job also picks up missed payments, if it is on.', 'payment', '/transaction/index', null, true);
    }

    /** @param string[] $customers one line each, possibly capped */
    public static function accountsDisabled(array $customers, int $total): void
    {
        self::send('Inactive Accounts Disabled', $total . " account(s) were suspended: billing details incomplete after a month and at least 7 days' notice, nothing live.\n" . implode("\n", $customers) . "\nSet an account to Active on its customer page to give it another month.", 'billing', '/user/index');
    }

    /** One bell row a day; retryUnsent() sees to the email if it fails. */
    private static function sendOncePerDay(string $title, string $message, string $type, ?string $route, ?string $match = null, bool $whatsappNotification = false): void
    {
        try {
            if (AdminNotification::find()->where(['title' => $title])->andWhere(['>=', 'created_at', strtotime('today')])->andFilterWhere(['like', 'message', $match])->exists()) {
                return;
            }
        } catch (\Throwable $e) {
            // Better a repeated alert than none, e.g. when the database is why this one is being sent.
            Yii::error($e, __METHOD__);
        }
        self::send($title, $message, $type, $route, $whatsappNotification);
    }

    /**
     * At the start of every cron job: emails that failed in the last 3 days, oldest first. Stops at the first failure,
     * as the mail server is probably still down. Not the last 5 minutes: another process may be sending those.
     */
    public static function retryUnsent(): void
    {
        try {
            $unsent = AdminNotification::find()->where(['emailed_at' => null])->andWhere(['between', 'created_at', time() - 3 * 86400, time() - 300])->orderBy(['id' => SORT_ASC])->limit(20)->all();
            foreach ($unsent as $notification) {
                if (!self::sendEmail((string) $notification->title, (string) $notification->message)) {
                    return;
                }
                $notification->updateAttributes(['emailed_at' => time()]);
            }
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
        }
    }

    public static function renewalLeftOpen(string $service, string $staff, string $warning): void
    {
        $message = "Service: {$service}\n" . "Disabled by: {$staff}\n" . $warning;
        self::send('Disabled Service Still Billed', $message, 'billing', '/invoice/index');
    }

    public static function billingDetailsChanged(int $profileId, string $customer, string $legalName, bool $created): void
    {
        $title = $created ? 'Customer Added Billing Details' : 'Customer Updated Billing Details';
        $message = "Customer: {$customer}\n" . "Legal name: {$legalName}\n" . 'Review the profile and set GST applicability if needed.';
        self::send($title, $message, 'billing', '/customer-billing-profile/update?id=' . $profileId);
    }

    /**
     * @param bool $whatsappNotification also message ADMIN_WHATSAPP_MOBILE (urgent, low-volume alerts
     *                                   only; bulk ones like "invoice generated" stay email-only)
     */
    private static function send(string $title, string $message, string $type = 'system', ?string $route = null, bool $whatsappNotification = false): void
    {
        // One channel failing (e.g. the database is why this alert exists) must not silence the others.
        $notification = null;
        try {
            $notification = (new AdminNotificationService())->create($type, $title, $message, $route);
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Admin notification could not be saved.', 'title' => $title, 'error' => $e->getMessage()], __METHOD__);
        }
        if (self::sendEmail($title, $message) && $notification !== null) {
            try {
                $notification->updateAttributes(['emailed_at' => time()]);
            } catch (\Throwable $e) {
                Yii::error($e, __METHOD__);
            }
        }
        if ($whatsappNotification) {
            self::sendWhatsApp($title, $message);
        }
    }

    private static function sendEmail(string $title, string $message): bool
    {
        try {
            $sent = Yii::$app->mailer
                    ->compose('admin-alert-html', ['title' => $title, 'message' => $message])
                    ->setFrom(Yii::$app->params['senderEmail'])
                    ->setTo(Yii::$app->params['adminEmail'])
                    ->setSubject("[ADMIN] {$title}")
                    ->send();
            if (!$sent) {
                Yii::error(['message' => 'Admin alert email was not sent.', 'title' => $title], __METHOD__);
            }

            return (bool) $sent;
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);

            return false;
        }
    }

    private static function sendWhatsApp(string $title, string $message): void
    {
        try {
            WhatsAppService::sendTemplate(
                Yii::$app->params['whatsapp']['adminMobile'],
                'admin_alert',
                [
                        'body_1' => ['type' => 'text', 'value' => $title],
                        // Template parameters may not contain newlines, tabs or 4+ spaces.
                        'body_2' => ['type' => 'text', 'value' => preg_replace('/\s*\n\s*|\t| {4,}/', ' | ', trim($message))],
                    ]
            );
        } catch (\Throwable $exception) {
            Yii::error($exception, __METHOD__);
        }
    }
}
