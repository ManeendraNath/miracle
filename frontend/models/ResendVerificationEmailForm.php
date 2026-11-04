<?php

declare(strict_types=1);

namespace frontend\models;

use common\models\User;
use yii\base\Model;
use yii\mail\MailerInterface;

class ResendVerificationEmailForm extends Model
{
    use \common\models\traits\ScalarInputTrait;

    public string $email = '';
    /** Bot check, as on the contact form: each of these forms sends an email. */
    public string $verifyCode = '';

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            ['verifyCode', 'captcha'],
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
        ];
    }

    /**
     * Sends confirmation email to user.
     *
     * @param MailerInterface $mailer the mailer component.
     * @param string $supportEmail the support email address.
     * @param string $appName the application name.
     *
     * @return bool whether the email was sent.
     */
    public function sendEmail(MailerInterface $mailer, string $supportEmail, string $appName): bool
    {
        $user = User::findOne(['email' => $this->email, 'status' => User::getStatusInactive()]);

        if ($user === null) {
            return false;
        }
        // A fresh link: the old one may have expired.
        $user->generateEmailVerificationToken();
        $user->updated_at = time();
        if (!$user->save(false, ['verification_token', 'updated_at'])) {
            return false;
        }

        return $mailer
            ->compose(
                ['html' => 'emailVerify-html', 'text' => 'emailVerify-text'],
                ['user' => $user],
            )
            ->setFrom([$supportEmail => $appName . ' robot'])
            ->setTo($this->email)
            ->setSubject('Account registration at ' . $appName)
            ->send();
    }
}
