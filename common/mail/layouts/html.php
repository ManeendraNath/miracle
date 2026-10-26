<?php
declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $content */
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= Html::encode($this->title) ?></title>
        <?php $this->head() ?>
        <style>

            body {
                margin:0;
                padding:0;
                background:#f4f7fb;
                font-family:Arial,Helvetica,sans-serif;
            }

            .email-container {
                width:100%;
                max-width:700px;
            }

            .email-content {
                padding:35px;
            }

            .button {
                background:#1565c0;
                color:#ffffff !important;
                text-decoration:none;
                padding:14px 28px;
                border-radius:6px;
                display:inline-block;
                font-weight:bold;
            }

            @media only screen and (max-width:600px) {

                .email-content {
                    padding:20px !important;
                }

                .responsive-table td {
                    display:block !important;
                    width:100% !important;
                    box-sizing:border-box;
                }

                .button {
                    display:block !important;
                    width:100% !important;
                    text-align:center !important;
                    box-sizing:border-box;
                }
            }

        </style>
    </head>
    <body style=" margin:0; padding:0; background:#f4f7fb; font-family:Arial,Helvetica,sans-serif;">
        <?php $this->beginBody() ?>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td align="center" style="padding:30px 15px;">
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:700px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 3px 12px rgba(0,0,0,.08);">
                        <!-- Header -->
                        <tr>
                            <td align="center" style="background:#0b1f4d; color:#ffffff; padding:30px;">
                                <h1 style="margin:0; font-size:28px;">Miracle Web Technologies</h1>
                                <div style="margin-top:8px; font-size:14px; opacity:0.9;">
                                    Upgrade Your Business With Us</div>
                            </td>
                        </tr>
                        <!-- Email Content -->
                        <tr>
                            <td style="padding:35px; color:#444; line-height:1.8;">
                                <?= $content ?>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td align="center" style="background:#f8f9fa; padding:25px; color:#666; font-size:13px;">
                                <strong>Miracle Web Technologies</strong>
                                <br>
                                Operations & Billing Department
                                <br><br>
                                <?= Html::encode(Yii::$app->params['supportEmail']) ?>
                                <br><br>
                                © 2015-<?= date('Y') ?> Miracle Web Technologies
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>
