<?php

use yii\helpers\Html;

/** @var \common\models\SupportTicket $ticket */
?>
<div style="font-family:Arial,sans-serif;max-width:700px;margin:0 auto;">
    <div style="background:#fd7e14;color:#fff;padding:20px;">
        <h2 style="margin:0;">Customer Reply Received</h2>
    </div>
    <div style="padding:20px;border:1px solid #ddd;">
        <p>A customer has replied to a support request.</p>
        <table cellpadding="8">
            <tr>
                <td><strong>Ticket</strong></td>
                <td><?= Html::encode($ticket->ticket_number) ?></td>
            </tr>
            <tr>
                <td><strong>Subject</strong></td>
                <td><?= Html::encode($ticket->subject) ?></td>
            </tr>
            <tr>
                <td><strong>Customer</strong></td>
                <td><?= Html::encode($ticket->user->username) ?></td>
            </tr>
        </table>
    </div>
</div>