<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

trait BaseIdentity
{

    public static function getRoleSuperAdmin(): string
    {
        return 'Superadmin';
    }

    public static function getRoleAdmin(): string
    {
        return 'Admin';
    }

    public static function getRoleManager(): string
    {
        return 'Manager';
    }

    public static function getStatusInactive(): int
    {
        return 0;
    }

    public static function getStatusActive(): int
    {
        return 1;
    }

    public static function getStatusSuspended(): int
    {
        return 2;
    }

    public static function getStatusDeleted(): int
    {
        return 3;
    }

    public static function getStatuses(): array
    {
        return [
            self::getStatusInactive() => 'Inactive',
            self::getStatusActive() => 'Active',
            self::getStatusSuspended() => 'Suspended',
            self::getStatusDeleted() => 'Deleted',
        ];
    }

    /**
     * Shared baseline status validation rules configuration array layer.
     *
     * @return array
     */
    public function getBaseIdentityRules(): array
    {
        return [
            ['status', 'default', 'value' => self::getStatusInactive()],
            ['status', 'in', 'range' => [self::getStatusActive(), self::getStatusInactive(), self::getStatusDeleted()]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * DYNAMIC PROPERTY LABEL LABELS MAP:
     * Overrides and maps shared default column description arrays across models.
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'System ID Reference',
            'username' => 'Unique Profile Handle',
            'password_hash' => 'Encrypted Password Hash',
            'auth_key' => 'Persistent Cookie Validation Token',
            'status' => 'Account Deployment State',
            'created_at' => 'Record Creation Date',
            'updated_at' => 'Last Account Update Timestamp',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id): static|null
    {
        return static::findOne(['id' => $id, 'status' => self::getStatusActive()]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null): never
    {
        throw new \yii\base\NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds an identity record by username.
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername(string $username): static|null
    {
        return static::findOne(['username' => $username, 'status' => self::getStatusActive()]);
    }

    /**
     * Finds an identity record by password reset token.
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken(string $token): static|null
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
                    'password_reset_token' => $token,
                    'status' => self::getStatusActive(),
        ]);
    }

    /**
     * Finds an identity record by verification email token.
     *
     * @param string $token verify email token
     * @return static|null
     */
    public static function findByVerificationToken(string $token): static|null
    {
        return static::findOne([
                    'verification_token' => $token,
                    'status' => self::getStatusInactive(),
        ]);
    }

    /**
     * Validates if the password reset token is still active and within time constraints.
     *
     * @param string|null $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid(string|null $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'] ?? 3600;

        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return (int) $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey(): string
    {
        return (string) $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates the plain text password string against the encrypted database hash.
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates a modern secure password hash and sets it to the record payload.
     *
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates persistent session identity keys.
     */
    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates a secure recovery hash mapping block.
     */
    public function generatePasswordResetToken(): void
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Generates a sign-up validation mail checker token.
     */
    public function generateEmailVerificationToken(): void
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes the active password reset validation string parameters.
     */
    public function removePasswordResetToken(): void
    {
        $this->password_reset_token = null;
    }
}
