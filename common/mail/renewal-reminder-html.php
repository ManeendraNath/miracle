<?php

use yii\helpers\Html;

/** @var string $serviceName */
/** @var string $expiryDate */
/** @var int $daysBefore */
/** @var common\models\User $client */

?>

<div
    style="
        font-family: Arial, sans-serif;
        max-width: 700px;
        margin: 0 auto;
        background: #ffffff;
        border: 1px solid #e5e5e5;
    "
>

    <div
        style="
            background: #0b1f4d;
            color: #ffffff;
            padding: 24px;
        "
    >

        <h2 style="margin:0;">
            Service Renewal Reminder
        </h2>

    </div>

    <div style="padding: 30px;">

        <p>

            Hello
            <strong>
                <?= Html::encode(
                    $client->username
                ) ?>
            </strong>,

        </p>

        <p>

            This is a reminder that the following service
            is approaching its expiry date.

        </p>

        <table
            style="
                width:100%;
                border-collapse:collapse;
                margin:20px 0;
            "
        >
            <tr>

                <td
                    style="
                        padding:10px;
                        border:1px solid #ddd;
                        background:#f8f9fa;
                        width:180px;
                    "
                >
                    Service
                </td>

                <td
                    style="
                        padding:10px;
                        border:1px solid #ddd;
                    "
                >
                    <strong>
                        <?= Html::encode(
                            $serviceName
                        ) ?>
                    </strong>
                </td>

            </tr>

            <tr>

                <td
                    style="
                        padding:10px;
                        border:1px solid #ddd;
                        background:#f8f9fa;
                    "
                >
                    Expiry Date
                </td>

                <td
                    style="
                        padding:10px;
                        border:1px solid #ddd;
                    "
                >
                    <?= Yii::$app
                        ->formatter
                        ->asDate(
                            $expiryDate,
                            'php:d M Y'
                        ) ?>
                </td>

            </tr>

            <tr>

                <td
                    style="
                        padding:10px;
                        border:1px solid #ddd;
                        background:#f8f9fa;
                    "
                >
                    Remaining
                </td>

                <td
                    style="
                        padding:10px;
                        border:1px solid #ddd;
                    "
                >
                    <strong>
                        <?= $daysBefore ?>
                        day<?= $daysBefore !== 1 ? 's' : '' ?>
                    </strong>
                </td>

            </tr>

        </table>

        <div
            style="
                background:#fff3cd;
                border:1px solid #ffeeba;
                padding:15px;
                margin:25px 0;
            "
        >

            Please renew this service before the expiry
            date to avoid service interruption or downtime.

        </div>

        <p>

            Login to your customer portal to review and
            renew this service.

        </p>

    </div>

    <div
        style="
            background:#f8f9fa;
            color:#666;
            padding:15px;
            font-size:12px;
        "
    >

        Miracle Web Technologies

    </div>

</div>
