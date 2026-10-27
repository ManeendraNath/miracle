<?php

use yii\helpers\Html;

/** @var \common\models\SupportTicket $ticket */
?>

<div style="font-family:Arial,sans-serif;max-width:700px;margin:0 auto;">

    <div style="background:#0d6efd;color:#fff;padding:20px;">
        <h2 style="margin:0;">Ticket Resolved</h2>
    </div>

    <div style="padding:20px;border:1px solid #ddd;">

        <p>Hello,</p>

        <p>
            Your support request has been marked as resolved.
        </p>

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

        <p>
            If you need additional assistance, simply reply to the ticket and it will reopen automatically.
        </p>

    </div>

</div>