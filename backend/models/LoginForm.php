<?php

namespace backend\models;

use common\components\LoginThrottle;
use Yii;
use yii\base\Model;

/**
 * Login form processing configuration parameters.
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
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
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (LoginThrottle::isLocked('backend', (string) $this->username)) {
                $this->addError($attribute, 'Too many failed attempts. Please try again in 15 minutes.');
                return;
            }
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                LoginThrottle::fail('backend', (string) $this->username);
                $this->addError($attribute, 'Incorrect username or password.');
                return;
            }
        }
    }

    /**
     * Logs in a user using the provided username and password. For an account with
     * two-factor on, only starts the code step (TwoFactorForm); the user stays a guest.
     *
     * @return bool whether the password was correct
     */
    public function login()
    {
        if (!$this->validate()) {
            return false;
        }
        $duration = $this->rememberMe ? 3600 * 24 * 30 : 0;
        if ($this->getUser()->hasTwoFactor()) {
            // The throttle is cleared only after the code, so a known password
            // can't be used to reset the count while guessing codes.
            TwoFactorForm::start($this->getUser(), $duration);
            return true;
        }
        LoginThrottle::clear('backend', (string) $this->username);
        return Yii::$app->user->login($this->getUser(), $duration);
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = Admin::findByUsername($this->username);
        }
        return $this->_user;
    }
}
