<?php

declare(strict_types=1);

namespace common\models;

use common\helpers\SecretBox;
use common\components\Totp;
use common\models\traits\AutoTimestampTrait;
use common\models\traits\BaseIdentityTrait;
use common\models\traits\PasswordManagementTrait;
use Yii;
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
    use traits\ScalarInputTrait;

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
                    ['password', 'match', 'pattern' => User::PASSWORD_PATTERN, 'message' => User::PASSWORD_MESSAGE, 'skipOnEmpty' => true],
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
    public function scenarios(): array
    {
        return array_map(
            static fn(array $attributes): array => array_values(array_diff($attributes, ['totp_backup_codes', 'totp_secret', 'totp_last_step'])),
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
    public function beforeSave($insert): bool
    {
        if (!$insert) {
            $this->dropStaleResetToken();
        }

        return parent::beforeSave($insert);
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
     * Role value => label, highest first.
     *
     * @return array<string, string>
     */
    public static function getRoles(): array
    {
        return [
            self::getRoleSuperAdmin() => self::getRoleSuperAdmin(),
            self::getRoleAdmin() => self::getRoleAdmin(),
            self::getRoleManager() => self::getRoleManager(),
        ];
    }

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

    /** Whether TWO_FACTOR_KEY is set, so new secrets can be stored encrypted. */
    public static function canStoreTwoFactor(): bool
    {
        return SecretBox::hasKey();
    }

    private static function encryptTotpSecret(string $secret): string
    {
        return SecretBox::encrypt($secret);
    }

    /** The plain base32 secret. Secrets saved before encryption existed are stored as-is. */
    private function totpSecret(): string
    {
        $secret = SecretBox::decrypt((string) $this->totp_secret);
        if ($secret === null) {
            throw new \RuntimeException("Two-factor secret of admin #{$this->id} can't be decrypted: TWO_FACTOR_KEY is missing or changed. "
                . 'Restore the old key, or run: php yii admin-2fa/reset ' . $this->username);
        }
        return $secret;
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
        $step = Totp::verify($this->totpSecret(), $code, $this->totp_last_step);
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
        // Encrypt a secret saved before encryption existed, now that the key is set.
        if (!SecretBox::isEncrypted($this->totp_secret) && self::canStoreTwoFactor()) {
            $this->updateAttributes(['totp_secret' => self::encryptTotpSecret($this->totp_secret)]);
        }
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
        $this->updateAttributes(['totp_secret' => self::encryptTotpSecret($secret), 'totp_last_step' => $step, 'auth_key' => $this->auth_key]);
        return true;
    }

    public function disableTwoFactor(): void
    {
        $this->updateAttributes(['totp_secret' => null, 'totp_last_step' => null, 'totp_backup_codes' => null]);
    }

    /** @return string[] new one-time codes to show once (only their hashes are kept); any earlier set stops working */
    public function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $raw = bin2hex(random_bytes(5));
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5);
        }
        $this->updateAttributes(['totp_backup_codes' => json_encode(array_map(static fn(string $code): string => hash('sha256', str_replace('-', '', $code)), $codes))]);

        return $codes;
    }

    public function backupCodesLeft(): int
    {
        return count(json_decode((string) $this->totp_backup_codes, true) ?: []);
    }

    /** Accepts a backup code once; the conditional update stops two requests using the same code. */
    public function consumeBackupCode(string $code): bool
    {
        $code = strtolower((string) preg_replace('/[\s-]+/', '', $code));
        if (!$this->hasTwoFactor() || preg_match('/^[0-9a-f]{10}$/', $code) !== 1) {
            return false;
        }
        $stored = (string) $this->totp_backup_codes;
        $hashes = json_decode($stored, true) ?: [];
        $used = hash('sha256', $code);
        $left = array_values(array_filter($hashes, static fn(string $hash): bool => !hash_equals($hash, $used)));
        if (count($left) === count($hashes)) {
            return false;
        }
        if (static::updateAll(['totp_backup_codes' => json_encode($left)], ['id' => $this->id, 'totp_backup_codes' => $stored]) !== 1) {
            return false;
        }
        $this->totp_backup_codes = json_encode($left);

        return true;
    }
}
