<?php

declare(strict_types=1);

namespace common\services;

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
                ->setSubject(sprintf('Customer Reply %s', $ticket->ticket_number))
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
                ->setSubject(sprintf('Response Received %s', $ticket->ticket_number))
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
                ->setSubject(sprintf('Support Request Resolved - %s', $ticket->ticket_number))
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
                ->setSubject(sprintf('Support Request Closed - %s', $ticket->ticket_number))
                ->send();
    }
}
