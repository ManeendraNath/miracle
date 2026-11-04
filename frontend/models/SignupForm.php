<?php

declare(strict_types=1);

namespace frontend\models;

use common\models\User;
use Yii;
use yii\base\Model;
use yii\mail\MailerInterface;

/**
 * Signup form
 */
class SignupForm extends Model
{
    use \common\models\traits\ScalarInputTrait;

    public string $username = '';
    public string $email = '';
    public string $password = '';
    /** Set by signup(): false when the account was created but the verification email failed. */
    public bool $verificationEmailSent = false;
    /** Bot check, as on the contact form: each of these forms sends an email. */
    public string $verifyCode = '';

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            ['verifyCode', 'captcha'],
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => User::class, 'message' => 'This username has already been taken.'],
            ['username', 'string', 'min' => 2, 'max' => 255],
            // Echoed into the verification email: no spaces or links.
            ['username', 'match', 'pattern' => '/^[A-Za-z0-9._@-]+$/', 'message' => 'Username may only contain letters, numbers and . _ @ -'],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => User::class, 'message' => 'This email address has already been taken.'],

            ['password', 'required'],
            ['password', 'string', 'min' => Yii::$app->params['user.passwordMinLength']],
            ['password', 'match', 'pattern' => \common\models\User::PASSWORD_PATTERN, 'message' => \common\models\User::PASSWORD_MESSAGE],
        ];
    }

    /**
     * Signs user up.
     *
     * @param MailerInterface $mailer the mailer component.
     * @param string $supportEmail the support email address.
     * @param string $appName the application name.
     *
     * @return bool|null whether the creating new account was successful and email was sent.
     */
    public function signup(MailerInterface $mailer, string $supportEmail, string $appName): bool|null
    {
        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->status = User::getStatusInactive();
        // User hashes `password` itself in beforeValidate and requires it on new records.
        $user->password = $this->password;
        $user->generateEmailVerificationToken();
        if (!$user->save()) {
            // Surface model errors (e.g. password rules) instead of a silent reload.
            $this->addErrors($user->getErrors());
            return null;
        }
        // The account exists now: a mail failure must not 500 (resubmitting would say "already taken").
        try {
            $this->verificationEmailSent = $this->sendEmail($mailer, $user, $supportEmail, $appName);
        } catch (\Throwable $e) {
            \Yii::error(['message' => 'Signup verification email failed.', 'userId' => (int) $user->id, 'error' => $e->getMessage()], __METHOD__);
        }
        return true;
    }

    /**
     * Sends confirmation email to user.
     *
     * @param MailerInterface $mailer the mailer component.
     * @param User $user user model to which the email should be sent.
     * @param string $supportEmail the support email address.
     * @param string $appName the application name.
     *
     * @return bool whether the email was sent.
     */
    protected function sendEmail(MailerInterface $mailer, User $user, string $supportEmail, string $appName): bool
    {
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
