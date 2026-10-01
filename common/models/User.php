<?php

declare(strict_types=1);

namespace common\models;

use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property int $id
 * @property string $username
 * @property string $password_hash
 * @property string|null $password_reset_token
 * @property string $verification_token
 * @property string $email
 * @property string $auth_key
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property string $password write-only password
 */
class User extends base\User implements IdentityInterface
{

    use \common\models\BaseIdentity;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        $rules = array_merge($this->getBaseIdentityRules(), [
            ['status', 'default', 'value' => self::getStatusInactive()],
            ['status', 'in', 'range' => [self::getStatusActive(), self::getStatusInactive(), self::getStatusDeleted()]],
        ]);

        return ArrayHelper::merge(parent::rules(), $rules);
    }
}
