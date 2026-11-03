<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\SupportTicket $ticket */
// Sent to the support team: link to the admin panel.
$link = Yii::$app->params['backendBaseUrl'] . '/support-ticket/view?number=' . rawurlencode($ticket->ticket_number);

echo $this->render('_card', [
    'tone' => 'warning',
    'eyebrow' => 'Support ticket',
    'title' => 'Customer replied',
    'intro' => 'A customer has replied to a support ticket.',
    'rows' => [
        ['Ticket', $ticket->ticket_number],
        ['Subject', $ticket->subject],
        ['Customer', $ticket->user->username ?? ''],
        ['Priority', $ticket->priority],
    ],
    'button' => ['View Ticket', $link],
]);
