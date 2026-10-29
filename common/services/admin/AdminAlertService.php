<?php

declare(strict_types=1);

namespace common\services\admin;

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
        self::send($title, $message);
    }

    public static function supportCustomerReply(string $ticketNumber, string $customer): void
    {
        $title = 'Customer Replied';
        $message = "Ticket: {$ticketNumber}\n" . "Customer: {$customer}";
        self::send($title, $message);
    }

    public static function paymentSubmitted(string $invoiceNumber, string $customer, float $amount): void
    {
        $title = 'Payment Submitted';
        $message = "Invoice: {$invoiceNumber}\n" . "Customer: {$customer}\n" . "Amount: ₹{$amount}";
        self::send($title, $message);
    }

    public static function leadCreated(string $name, string $email): void
    {
        $title = 'New Lead';
        $message = "Name: {$name}\n" . "Email: {$email}";
        self::send($title, $message);
    }

    public static function auditRequestCreated(string $website): void
    {
        $title = 'New Audit Request';
        $message = "Website: {$website}";
        self::send($title, $message);
    }

    public static function serviceExpiring(string $service, string $expiryDate): void
    {
        $title = 'Service Expiring';
        $message = "Service: {$service}\n" . "Expiry Date: {$expiryDate}";
        self::send($title, $message);
    }

    public static function invoiceGenerated(string $invoiceNumber, float $amount): void
    {
        $title = 'Invoice Generated';
        $message = "Invoice: {$invoiceNumber}\n" . "Amount: ₹{$amount}";
        self::send($title, $message);
    }

    private static function send(string $title, string $message, bool $whatsappNotification = false): void
    {
        (new AdminNotificationService())->create('support', $title, $message);
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
                        'body_2' => ['type' => 'text', 'value' => $message],
                    ]
            );
        } catch (\Throwable $exception) {
            Yii::error($exception, __METHOD__);
        }
    }
}
