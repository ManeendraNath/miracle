<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\SupportTicket $ticket */
// Sent to the support team: link to the admin panel (the customer portal if BACKEND_BASE_URL isn't set).
$link = !empty($_ENV['BACKEND_BASE_URL'])
    ? rtrim($_ENV['BACKEND_BASE_URL'], '/') . '/support-ticket/view?number=' . rawurlencode($ticket->ticket_number)
    : $_ENV['FRONTEND_BASE_URL'] . '/support/view?number=' . rawurlencode($ticket->ticket_number);

echo $this->render('_card', [
    'tone' => 'info',
    'eyebrow' => 'Support ticket',
    'title' => 'New support ticket',
    'intro' => 'A customer has opened a new support ticket.',
    'rows' => [
        ['Ticket', $ticket->ticket_number],
        ['Subject', $ticket->subject],
        ['Customer', $ticket->user->username ?? ''],
        ['Priority', $ticket->priority],
    ],
    'button' => ['View Ticket', $link],
]);
