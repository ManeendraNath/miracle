<?php

declare(strict_types=1);

namespace common\models;

use common\components\LoginThrottle;
use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    use \common\models\traits\ScalarInputTrait;

    public string $username = '';
    public string $password = '';
    public bool $rememberMe = true;
    private User|null $_user = null;
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // Arrays (LoginForm[username][]=x) would hit the (string) casts below.
            [['username', 'password'], 'string', 'max' => 255],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword(string $attribute, array|null $params): void
    {
        if (!$this->hasErrors()) {
            if (LoginThrottle::isLocked('frontend', $this->throttleName())) {
                $this->addError($attribute, 'Too many failed attempts. Please try again in 15 minutes.');
                return;
            }
            // Suspended or not verified yet: only someone who knows the password is told why they can't get in.
            $user = $this->getUser() ?? User::findOne(['username' => $this->username, 'status' => [User::getStatusSuspended(), User::getStatusInactive()]]);
            if (!($user ? $user->validatePassword($this->password) : LoginThrottle::checkAgainstNobody((string) $this->password))) {
                LoginThrottle::fail('frontend', $this->throttleName());
                $this->addError($attribute, 'Incorrect username or password.');
                return;
            }
            LoginThrottle::clear('frontend', $this->throttleName());
            if ((int) $user->status !== User::getStatusActive()) {
                $this->addError($attribute, (string) $user->verification_token !== ''
                    ? 'Please confirm your email address first, using the link we sent you. You can ask for a new link below.'
                    : 'Your account has been disabled. Please contact support to have it set active again.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login(): bool
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
        }

        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser(): User|null
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }

    /** The stored name when the account exists: the collation matches other spellings (é, soft hyphens), each of which would otherwise get its own failure count. */
    private function throttleName(): string
    {
        $user = $this->getUser();

        return $user ? (string) $user->username : (string) $this->username;
    }
}
