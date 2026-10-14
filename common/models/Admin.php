<?php

declare(strict_types=1);

namespace common\models;

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
}
