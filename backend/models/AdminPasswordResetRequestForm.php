<?php

declare(strict_types=1);

namespace backend\models;

use common\models\Admin;
use Yii;
use yii\base\Model;

/**
 * "Forgot password" on the admin login. The link is built from BACKEND_BASE_URL, never the request's host.
 */
final class AdminPasswordResetRequestForm extends Model
{
    use \common\models\traits\ScalarInputTrait;

    public string $email = '';
    public string $verifyCode = '';

    public function rules(): array
    {
        return [
            ['verifyCode', 'captcha', 'captchaAction' => 'auth/captcha'],
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
        ];
    }

    /** False when no active admin has this address, or the email failed: callers answer the same either way. */
    public function sendEmail(): bool
    {
        $admin = Admin::findOne(['email' => $this->email, 'status' => Admin::getStatusActive()]);
        if ($admin === null) {
            return false;
        }
        if (!Admin::isPasswordResetTokenValid($admin->password_reset_token)) {
            $admin->generatePasswordResetToken();
            $admin->updateAttributes(['password_reset_token' => $admin->password_reset_token]);
        }
        $resetUrl = rtrim((string) Yii::$app->params['backendBaseUrl'], '/') . '/auth/reset-password?token=' . rawurlencode((string) $admin->password_reset_token);

        return Yii::$app->mailer
            ->compose(['html' => '@common/mail/admin-password-reset-html', 'text' => '@common/mail/admin-password-reset-text'], ['username' => (string) $admin->username, 'resetUrl' => $resetUrl])
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo((string) $admin->email)
            ->setSubject('Reset your admin password')
            ->send();
    }
}
