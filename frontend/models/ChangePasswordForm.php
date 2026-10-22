<?php

declare(strict_types=1);

namespace frontend\models;

use common\models\User;
use yii\base\Model;

final class ChangePasswordForm extends Model
{

    public string $currentPassword = '';
    public string $newPassword = '';
    public string $confirmPassword = '';

    public function __construct(private readonly User $user, array $config = [])
    {
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['currentPassword', 'newPassword', 'confirmPassword'], 'required'],
            ['currentPassword', 'validateCurrentPassword'],
            ['newPassword', 'string', 'min' => 8, 'max' => 255],
            ['newPassword', 'match', 'pattern' => '/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', 'message' => 'Password must contain at least 8 characters including a letter and a number.'],
            ['confirmPassword', 'compare', 'compareAttribute' => 'newPassword', 'message' => 'The password confirmation does not match.'],
        ];
    }

    public function validateCurrentPassword(string $attribute): void
    {
        if (!$this->hasErrors() && !$this->user->validatePassword($this->$attribute)) {
            $this->addError($attribute, 'The current password is incorrect.');
        }
    }

    public function changePassword(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $this->user->setPassword($this->newPassword);
        $this->user->generateAuthKey();
        $this->user->removePasswordResetToken();
        return $this->user->save(false);
    }
}
