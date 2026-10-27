<?php

use yii\helpers\Html;

/** @var \common\models\SupportTicket $ticket */
$ticketLink = $_ENV['FRONTEND_BASE_URL'] . '/support/view?number=' . $ticket->ticket_number;
?>
<div style="font-family:Arial,sans-serif;max-width:700px;margin:0 auto;">
    <div style="background:#198754;color:#fff;padding:20px;">
        <h2 style="margin:0;">Support Team Replied</h2>
    </div>
    <div style="padding:20px;border:1px solid #ddd;">
        <p>Hello,</p>
        <p>A member of our support team has responded to your ticket.</p>
        <table cellpadding="8" cellspacing="0">
            <tr>
                <td><strong>Ticket</strong></td>
                <td><?= Html::encode($ticket->ticket_number) ?></td>
            </tr>
            <tr>
                <td><strong>Subject</strong></td>
                <td><?= Html::encode($ticket->subject) ?></td>
            </tr>
        </table>
        <p>Please login to your client portal to review the response.</p>
        <a href="<?= $ticketLink ?>" style="background:#1565c0; color:#fff; padding:12px 25px; border-radius:6px; text-decoration:none; display:inline-block;">View Ticket</a>
    </div>
</div>