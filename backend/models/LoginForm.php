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
    use \common\models\traits\ScalarInputTrait;

    public $username;
    public $password;

    private $_user;


    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['username', 'password'], 'required'],
            // Arrays (LoginForm[username][]=x) would hit the (string) casts below.
            [['username', 'password'], 'string', 'max' => 255],
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
            if (LoginThrottle::isLocked('backend', $this->throttleName())) {
                $this->addError($attribute, 'Too many failed attempts. Please try again in 15 minutes.');
                return;
            }
            $user = $this->getUser();
            if (!($user ? $user->validatePassword($this->password) : LoginThrottle::checkAgainstNobody((string) $this->password))) {
                LoginThrottle::fail('backend', $this->throttleName());
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
        if ($this->getUser()->hasTwoFactor() && !TwoFactorForm::isTrustedDevice($this->getUser()) && !\common\models\Admin::twoFactorPaused()) {
            // The throttle is cleared only after the code, so a known password
            // can't be used to reset the count while guessing codes.
            TwoFactorForm::start($this->getUser());
            return true;
        }
        LoginThrottle::clear('backend', $this->throttleName());
        return Yii::$app->user->login($this->getUser());
    }

    /**
     * Finds user by [[username]]
     *
     * @return Admin|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = Admin::findByUsername($this->username);
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
