<?php

use yii\helpers\Html;

/** @var \common\models\SupportTicket $ticket */
$ticketLink = $_ENV['FRONTEND_BASE_URL'] . 'support/view?number' . $ticket->ticket_number;
?>
<div style="font-family:Arial,sans-serif;max-width:700px;margin:0 auto;">
    <div style="background:#1565c0;color:#fff;padding:20px;">
        <h2 style="margin:0;">Support Ticket Created</h2>
    </div>
    <div style="padding:20px;border:1px solid #ddd;">
        <p>Hello,</p>
        <p>Your support request has been submitted successfully.</p>
        <table cellpadding="8" cellspacing="0" width="100%">
            <tr>
                <td><strong>Ticket Number</strong></td>
                <td><?= Html::encode($ticket->ticket_number) ?></td>
            </tr>
            <tr>
                <td><strong>Subject</strong></td>
                <td><?= Html::encode($ticket->subject) ?></td>
            </tr>
            <tr>
                <td><strong>Priority</strong></td>
                <td><?= Html::encode($ticket->priority) ?></td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td><?= Html::encode($ticket->status) ?></td>
            </tr>
        </table>
        <p>Our support team will review your request shortly.</p>
        <a href="<?= $ticketLink ?>" style="background:#1565c0; color:#fff; padding:12px 25px; border-radius:6px; text-decoration:none; display:inline-block;">View Ticket</a>
    </div>
</div>