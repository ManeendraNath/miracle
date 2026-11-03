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
            if (LoginThrottle::isLocked('frontend', (string) $this->username)) {
                $this->addError($attribute, 'Too many failed attempts. Please try again in 15 minutes.');
                return;
            }
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                LoginThrottle::fail('frontend', (string) $this->username);
                $this->addError($attribute, 'Incorrect username or password.');
                return;
            }
            LoginThrottle::clear('frontend', (string) $this->username);
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
}
