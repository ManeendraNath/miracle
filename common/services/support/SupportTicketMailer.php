<?php

declare(strict_types=1);

namespace common\services\support;

use common\models\SupportTicket;
use Yii;

final class SupportTicketMailer
{

    public static function ticketCreated(SupportTicket $ticket): void
    {
        Yii::$app->mailer
                ->compose(
                        ['html' => 'support-ticket-created-html', 'text' => 'support-ticket-created-text'], 
                        ['ticket' => $ticket]
                )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo(Yii::$app->params['supportEmail'])
                ->setSubject(sprintf('New Support Ticket %s', $ticket->ticket_number))
                ->send();
    }

    public static function customerReply(SupportTicket $ticket): void
    {
        Yii::$app->mailer
                ->compose(
                        ['html' => 'support-ticket-customer-reply-html', 'text' => 'support-ticket-customer-reply-text'], 
                        ['ticket' => $ticket]
                )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo(Yii::$app->params['supportEmail'])
                ->setSubject(sprintf('[Ticket #%s Customer Replied]', $ticket->ticket_number))
                ->send();
    }

    public static function adminReply(SupportTicket $ticket): void
    {
        Yii::$app->mailer
                ->compose(
                        ['html' => 'support-ticket-admin-reply-html', 'text' => 'support-ticket-admin-reply-text'], 
                        ['ticket' => $ticket]
                )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo($ticket->user->email)
                ->setSubject(sprintf('[Ticket #%s Support Team Replied]', $ticket->ticket_number))
                ->send();
    }

    public static function ticketResolved(SupportTicket $ticket): void
    {
        Yii::$app->mailer
                ->compose(
                        ['html' => 'support-ticket-resolved-html', 'text' => 'support-ticket-resolved-text'],
                        ['ticket' => $ticket]
                )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo($ticket->user->email)
                ->setSubject(sprintf('[Ticket #%s Support Request Resolved]', $ticket->ticket_number))
                ->send();
    }

    public static function ticketClosed(SupportTicket $ticket): void
    {
        Yii::$app->mailer
                ->compose(
                        ['html' => 'support-ticket-closed-html', 'text' => 'support-ticket-closed-text'],
                        ['ticket' => $ticket]
                )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo($ticket->user->email)
                ->setSubject(sprintf('[Ticket #%s Support Request Closed]', $ticket->ticket_number))
                ->send();
    }
}
