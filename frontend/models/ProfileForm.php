<?php

declare(strict_types=1);

namespace frontend\models;

use common\models\User;
use yii\base\Model;

final class ProfileForm extends Model
{

    public string $username = '';
    public string $email = '';
    private User $user;

    public function __construct(User $user, array $config = [])
    {
        $this->user = $user;
        $this->username = (string) $user->username;
        $this->email = (string) $user->email;
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['username', 'email'], 'trim'],
            [['username', 'email'], 'required'],
            ['username', 'string', 'min' => 2, 'max' => 255],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['username', 'validateUsernameUnique'],
            ['email', 'validateEmailUnique'],
            ['email', 'validateEmailChange'],
        ];
    }

    public function validateUsernameUnique(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }
        $exists = User::find()->where(['username' => $this->$attribute])->andWhere(['<>', 'id', $this->user->id])->exists();
        if ($exists) {
            $this->addError($attribute, 'This username is already in use.');
        }
    }

    public function validateEmailUnique(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }
        $exists = User::find()->where(['email' => $this->$attribute])->andWhere(['<>', 'id', $this->user->id])->exists();
        if ($exists) {
            $this->addError($attribute, 'This email address is already in use.');
        }
    }
    
    public function validateEmailChange(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }
        $user = User::findOne(['id' => $this->user->id]);
        if ($user->email == $this->$attribute) {
            $this->addError($attribute, 'To change email address is not allowed. Kindly contact administrator');
        }
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $emailChanged = strcasecmp((string) $this->user->email, $this->email) !== 0;
        $this->user->username = $this->username;
        $this->user->email = $this->email;
        if ($emailChanged) {
            $this->user->generateEmailVerificationToken();
        }
        return $this->user->save();
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
