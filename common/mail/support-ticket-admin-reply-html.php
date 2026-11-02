<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\SupportTicket $ticket */
$link = $_ENV['FRONTEND_BASE_URL'] . '/support/view?number=' . rawurlencode($ticket->ticket_number);

echo $this->render('_card', [
    'tone' => 'info',
    'eyebrow' => 'Support ticket',
    'title' => 'Our support team replied',
    'intro' => 'A member of our support team has responded to your ticket. Sign in to read the reply.',
    'rows' => [
        ['Ticket', $ticket->ticket_number],
        ['Subject', $ticket->subject],
    ],
    'button' => ['View Ticket', $link],
]);
