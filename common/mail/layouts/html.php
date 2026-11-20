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


            @media only screen and (max-width:600px) {

                .email-body {
                    padding:14px !important;
                }

                .email-card {
                    padding:26px 20px !important;
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
        <div style=" display:none; max-height:0; overflow:hidden; opacity:0;"><?= \yii\helpers\Html::encode(\common\components\CustomYii::businessName()) ?> Notification</div>
        <?php $this->beginBody() ?>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td align="center" style="padding:30px 15px;">
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:700px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 3px 12px rgba(0,0,0,.08);">
                        <!-- Header -->
                        <tr>
                            <td align="center" style="background:#0b1f4d;color:#ffffff;padding:30px;">
                                <?= \common\components\CustomYii::logoImg(\common\components\CustomYii::portalUrl() . '/images/logo.png', ['height' => 90, 'style' => 'max-height:90px; max-width:100%; width:auto; display:block; margin:0 auto 15px;'], true, null) ?>
                                <h1 style="margin:0; font-size:28px; color:#ffffff;"><?= \yii\helpers\Html::encode(\common\components\CustomYii::businessName()) ?></h1>
                                <?php if (\common\components\CustomYii::getSetting('tagline')): ?>
                                    <div style="margin-top:8px; font-size:14px; color:#d7e4ff;">
                                        <?= \yii\helpers\Html::encode((string) \common\components\CustomYii::getSetting('tagline')) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <!-- Email Content -->
                        <tr>
                            <td class="email-body" style="padding:28px; background:#f3f4f6;">
                                <?= $content ?>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td align="center" style=" background:#f8f9fa; padding:25px; color:#666; font-size:13px;">
                                <strong><?= \yii\helpers\Html::encode(\common\components\CustomYii::businessName()) ?></strong>
                                <br>
                                <?php if (\common\components\CustomYii::getSetting('services_line')): ?>
                                    <?= \yii\helpers\Html::encode((string) \common\components\CustomYii::getSetting('services_line')) ?>
                                    <br>
                                <?php endif; ?>
                                <br>
                                <?= Html::encode(Yii::$app->params['supportEmail']) ?>
                                <br>
                                <?= \yii\helpers\Html::encode(\common\components\CustomYii::businessUrl()) ?>
                                <br>
                                <?php if (!empty($this->params['staffEmail'])): ?>
                                    <a href="<?= Html::encode(Yii::$app->params['backendBaseUrl']) ?>">Open the admin panel</a>
                                <?php else: ?>
                                    <a href="<?= \yii\helpers\Html::encode(\common\components\CustomYii::portalUrl()) ?>/dashboard/">Access Customer Portal</a>
                                <?php endif; ?>
                                <br><br>
                                © <?= \common\components\CustomYii::getSetting('founded_year') && (int) \common\components\CustomYii::getSetting('founded_year') < (int) date('Y') ? (int) \common\components\CustomYii::getSetting('founded_year') . '-' : '' ?><?= date('Y') ?> <?= \yii\helpers\Html::encode(\common\components\CustomYii::businessName()) ?>
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
