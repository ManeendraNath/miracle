<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\SupportTicket $ticket */
$link = $_ENV['FRONTEND_BASE_URL'] . '/support/view?number=' . rawurlencode($ticket->ticket_number);

echo $this->render('_card', [
    'tone' => 'success',
    'icon' => '✓',
    'eyebrow' => 'Support ticket',
    'title' => 'Your ticket is resolved',
    'intro' => 'Your support request has been marked as resolved.',
    'rows' => [
        ['Ticket', $ticket->ticket_number],
        ['Subject', $ticket->subject],
    ],
    'note' => ['Need more help? Reply to the ticket and it will reopen automatically.'],
    'button' => ['View Ticket', $link],
]);
