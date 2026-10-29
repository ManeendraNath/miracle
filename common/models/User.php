<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use common\models\traits\BaseIdentityTrait;
use common\models\traits\PasswordManagementTrait;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property int $id
 * @property string $username
 * @property string $password_hash
 * @property string|null $password_reset_token
 * @property string|null $verification_token
 * @property string $email
 * @property string $auth_key
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class User extends base\User implements IdentityInterface
{

    use BaseIdentityTrait;
    use AutoTimestampTrait;
    use PasswordManagementTrait;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function rules(): array
    {
        $rules = [
            ['mobile', 'match', 'pattern' => '/^[0-9]{10,15}$/', 'message' => 'Enter a valid mobile number.'],
            ['whatsapp', 'match', 'pattern' => '/^[0-9]{10,15}$/', 'message' => 'Enter a valid whatsapp number.'],
        ];
        return ArrayHelper::merge(parent::rules(), $this->getBaseIdentityRules(), $this->getPasswordRules(), $rules);
    }

    /**
     * Prepare internally-managed attributes before inherited
     * generated-model validation runs.
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
        $attributes = [
            'username' => 'Username',
            'email' => 'Email Address',
            'password' => $this->isNewRecord ? 'Password' : 'New Password',
            'status' => 'Account Status',
        ];
        return ArrayHelper::merge(parent::attributeLabels(), $attributes);
    }

    public function getWhatsappNumber(): ?string
    {
        if (empty($this->mobile) && empty($this->whatsapp)) {
            return null;
        }
        if (!empty($this->whatsapp)) {
            return preg_replace('/[^0-9]/', '', $this->whatsapp);
        }
        return preg_replace('/[^0-9]/', '', $this->mobile);
    }
}
