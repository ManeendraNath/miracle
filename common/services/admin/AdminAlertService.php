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
        $message = "Invoice: {$invoiceNumber} ({$invoiceStatus})\n" . "Amount: ₹{$amount}\n" . "Razorpay payment: {$paymentId}\n" . 'The customer was charged but the invoice could not accept it. Refund in Razorpay or apply it manually.';
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
            . 'The invoice had already been partly paid another way. Refund the excess' . ($online ? ' in Razorpay.' : ' to the customer.');
        self::send($title, $message, 'payment', '/transaction/index?' . http_build_query(['TransactionSearch' => ['gateway_payment_id' => $paymentId]]), true);
    }

    public static function supersededOrderPaid(string $invoiceNumber, float $paid, float $invoiceTotal, string $paymentId): void
    {
        $title = 'Online Payment Needs Attention';
        $message = "Invoice: {$invoiceNumber}\n" . "Paid: ₹{$paid} (invoice total now ₹{$invoiceTotal})\n" . "Razorpay payment: {$paymentId}\n" . 'The customer paid an older online order after changing the coupon. Refund any excess in Razorpay; a shortfall stays on the invoice.';
        self::send($title, $message, 'payment', '/transaction/index?' . http_build_query(['TransactionSearch' => ['gateway_payment_id' => $paymentId]]), true);
    }

    public static function invoiceGenerationFailed(int $userId, string $reason): void
    {
        $title = 'Automatic Invoice Failed';
        $message = "Client ID: {$userId}\n" . "Reason: {$reason}\n" . 'It will be retried on the next billing run.';
        self::send($title, $message, 'billing', '/customer-billing-profile/index', true);
    }

    public static function renewalSkipped(string $service, string $reason): void
    {
        $title = 'Renewal Not Billed';
        $message = "Service: {$service}\n" . "Reason: {$reason}\n" . 'The rest of the billing run went ahead. Fix the service (expiry date, cycle, price) and it is billed on the next run.';
        self::send($title, $message, 'billing', '/renewal-monitoring/index', true);
    }

    public static function billingRunFailed(string $reason): void
    {
        $message = "Reason: {$reason}\n" . 'No renewal invoices were created by this run. It runs again at the next scheduled time; fix the cause before then.';
        self::send('Billing Run Failed', $message, 'billing', '/admin/cron-log', true);
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
        (new AdminNotificationService())->create($type, $title, $message, $route);
        self::sendEmail($title, $message);
        if ($whatsappNotification) {
            self::sendWhatsApp($title, $message);
        }
    }

    private static function sendEmail(string $title, string $message): void
    {
        try {
            Yii::$app->mailer
                    ->compose('admin-alert-html', ['title' => $title, 'message' => $message])
                    ->setFrom(Yii::$app->params['senderEmail'])
                    ->setTo(Yii::$app->params['adminEmail'])
                    ->setSubject("[ADMIN] {$title}")
                    ->send();
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
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
