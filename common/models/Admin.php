<?php

namespace common\models;

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
 * @property string $role
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class Admin extends base\Admin implements IdentityInterface
{

    use \common\models\BaseIdentity;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        $rules = ArrayHelper::merge($this->getBaseIdentityRules(), [
            [['username', 'email', 'password_hash'], 'required'],
            [['username', 'email'], 'unique'],
            ['email', 'email'],
            [['username', 'email', 'password_hash', 'role'], 'string', 'max' => 255],

            // Validate that assigned role falls within defined constraints
            ['role', 'default', 'value' => self::getRoleManager()],
            ['role', 'in', 'range' => [self::getRoleSuperAdmin(), self::getRoleAdmin(), self::getRoleManager()]],
            ['status', 'default', 'value' => self::getStatusInactive()],
            ['status', 'in', 'range' => [self::getStatusActive(), self::getStatusInactive(), self::getStatusDeleted()]],
        ]);
        return ArrayHelper::merge(parent::rules(), $rules);
    }

    // ======================================================================
    // ROLE ASSIGNMENT ENGINE CHECKERS
    // ======================================================================

    /**
     * Checks if current logged admin user possesses full root system permissions.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::getRoleSuperAdmin();
    }

    /**
     * Checks if current admin user possesses standard operational administration access.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::getRoleSuperAdmin(), self::getRoleAdmin()], true);
    }
}
