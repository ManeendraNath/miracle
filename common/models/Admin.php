<?php

declare(strict_types=1);

namespace common\models;

use common\components\Totp;
use common\models\traits\AutoTimestampTrait;
use common\models\traits\BaseIdentityTrait;
use common\models\traits\PasswordManagementTrait;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "admin".
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $auth_key
 * @property string $password_hash
 * @property string|null $password_reset_token
 * @property string|null $totp_secret
 * @property int|null $totp_last_step
 * @property string $role
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class Admin extends base\Admin implements IdentityInterface
{
    use BaseIdentityTrait;
    use AutoTimestampTrait;
    use PasswordManagementTrait;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        $rules = ArrayHelper::merge(
            $this->getBaseIdentityRules(),
            $this->getPasswordRules(),
            [
                    [['username', 'email'], 'required'],
                    [['username', 'email'], 'unique'],
                    ['email', 'email'],
                    [['username', 'email', 'role'], 'string', 'max' => 255],
                    ['role', 'default', 'value' => self::getRoleManager()],
                    ['role', 'in', 'range' => [
                            self::getRoleSuperAdmin(),
                            self::getRoleAdmin(),
                            self::getRoleManager(),
                        ],
                    ],
                ]
        );

        return ArrayHelper::merge(parent::rules(), $rules);
    }

    /**
     * The two-factor columns are changed only through the methods below, never by form input.
     */
    #[\Override]
    public function scenarios()
    {
        return array_map(
            static fn(array $attributes): array => array_values(array_diff($attributes, ['totp_secret', 'totp_last_step'])),
            parent::scenarios(),
        );
    }

    /**
     * Prepare internally-managed fields before validation.
     */
    #[\Override]
    public function beforeValidate(): bool
    {
        $this->prepareTimestampAttributes();
        $this->preparePasswordAttributes();

        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function attributeLabels(): array
    {
        $attribute = [
            'username' => 'Username',
            'email' => 'Email Address',
            'password' => $this->isNewRecord ? 'Password' : 'New Password',
            'role' => 'Role',
            'status' => 'Account Status',
        ];
        return ArrayHelper::merge(parent::attributeLabels(), $attribute);
    }

    // ======================================================================
    // ROLE ASSIGNMENT CHECKERS
    // ======================================================================

    /**
     * Whether this administrator has the Superadmin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::getRoleSuperAdmin();
    }

    /**
     * Whether this administrator has administrative access.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::getRoleSuperAdmin(), self::getRoleAdmin()], true);
    }

    // ======================================================================
    // TWO-FACTOR SIGN-IN (TOTP)
    // ======================================================================

    public function hasTwoFactor(): bool
    {
        return $this->totp_secret !== null;
    }

    /**
     * Accepts a current authenticator code once. The used time step is stored with a
     * conditional update, so two requests racing with the same code can't both pass.
     */
    public function consumeTotpCode(string $code): bool
    {
        if (!$this->hasTwoFactor()) {
            return false;
        }
        $step = Totp::verify($this->totp_secret, $code, $this->totp_last_step);
        if ($step === null) {
            return false;
        }
        $updated = static::updateAll(['totp_last_step' => $step], [
            'and',
            ['id' => $this->id],
            ['or', ['totp_last_step' => null], ['<', 'totp_last_step', $step]],
        ]);
        if ($updated !== 1) {
            return false;
        }
        $this->totp_last_step = $step;
        return true;
    }

    /**
     * Turns two-factor on once the user proves their app has $secret. A new auth key
     * invalidates "remember me" cookies issued before, so none of them skips the code.
     */
    public function enableTwoFactor(string $secret, string $code): bool
    {
        $step = Totp::verify($secret, $code);
        if ($step === null) {
            return false;
        }
        $this->generateAuthKey();
        $this->updateAttributes(['totp_secret' => $secret, 'totp_last_step' => $step, 'auth_key' => $this->auth_key]);
        return true;
    }

    public function disableTwoFactor(): void
    {
        $this->updateAttributes(['totp_secret' => null, 'totp_last_step' => null]);
    }
}
