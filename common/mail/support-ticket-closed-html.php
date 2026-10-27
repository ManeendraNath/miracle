<?php

use yii\helpers\Html;

/** @var \common\models\SupportTicket $ticket */
$ticketLink = $_ENV['FRONTEND_BASE_URL'] . 'support/view?number' . $ticket->ticket_number;
?>
<div style="font-family:Arial,sans-serif;max-width:700px;margin:0 auto;">
    <div style="background:#6c757d;color:#fff;padding:20px;">
        <h2 style="margin:0;">Ticket Closed</h2>
    </div>
    <div style="padding:20px;border:1px solid #ddd;">
        <p>Hello,</p>
        <p>Your support request has been closed.</p>
        <table cellpadding="8">
            <tr>
                <td><strong>Ticket</strong></td>
                <td><?= Html::encode($ticket->ticket_number) ?></td>
            </tr>
            <tr>
                <td><strong>Subject</strong></td>
                <td><?= Html::encode($ticket->subject) ?></td>
            </tr>
        </table>
        <p>Thank you for choosing Miracle Web Technologies.</p>
        <a href="<?= $ticketLink ?>" style="background:#1565c0; color:#fff; padding:12px 25px; border-radius:6px; text-decoration:none; display:inline-block;">View Ticket</a>
    </div>
</div>
