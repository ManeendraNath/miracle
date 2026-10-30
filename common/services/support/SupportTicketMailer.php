<?php

declare(strict_types=1);

namespace common\services\support;

use common\models\SupportTicket;
use Yii;

final class SupportTicketMailer
{
    public static function ticketCreated(SupportTicket $ticket): void
    {
        self::send($ticket, 'support-ticket-created', Yii::$app->params['supportEmail'], 'New Support Ticket %s');
    }

    public static function customerReply(SupportTicket $ticket): void
    {
        self::send($ticket, 'support-ticket-customer-reply', Yii::$app->params['supportEmail'], '[Ticket #%s Customer Replied]');
    }

    public static function adminReply(SupportTicket $ticket): void
    {
        self::send($ticket, 'support-ticket-admin-reply', $ticket->user->email, '[Ticket #%s Support Team Replied]');
    }

    public static function ticketResolved(SupportTicket $ticket): void
    {
        self::send($ticket, 'support-ticket-resolved', $ticket->user->email, '[Ticket #%s Support Request Resolved]');
    }

    public static function ticketClosed(SupportTicket $ticket): void
    {
        self::send($ticket, 'support-ticket-closed', $ticket->user->email, '[Ticket #%s Support Request Closed]');
    }

    /**
     * Mail is a side effect of an already-saved ticket change: a mail outage
     * is logged, never allowed to fail (or roll back) the caller.
     */
    private static function send(SupportTicket $ticket, string $view, string $to, string $subject): void
    {
        try {
            $sent = Yii::$app->mailer
                    ->compose(
                        ['html' => $view . '-html', 'text' => $view . '-text'],
                        ['ticket' => $ticket]
                    )
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                    ->setTo($to)
                    ->setSubject(sprintf($subject, $ticket->ticket_number))
                    ->send();
            if (!$sent) {
                Yii::warning(['message' => 'Support ticket mail not sent.', 'view' => $view, 'ticket' => $ticket->ticket_number], __METHOD__);
            }
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Support ticket mail failed.', 'view' => $view, 'ticket' => $ticket->ticket_number, 'error' => $e->getMessage()], __METHOD__);
        }
    }
}
