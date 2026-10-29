<?php

declare(strict_types=1);

namespace frontend\models;

use common\models\User;
use yii\base\Model;

final class ProfileForm extends Model
{

    public string $username = '';
    public string $email = '';
    public string $mobile = '';
    public string $whatsapp = '';
    private User $user;

    public function __construct(User $user, array $config = [])
    {
        $this->user = $user;

        $this->username = (string) $user->username;
        $this->email = (string) $user->email;

        $this->mobile = (string) $user->mobile;
        $this->whatsapp = (string) $user->whatsapp;

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
            ['mobile', 'string', 'max' => 20],
            ['whatsapp', 'string', 'max' => 20],
            [
                ['mobile', 'whatsapp'],
                'match',
                'pattern' => '/^[0-9]{10,15}$/',
                'message' => 'Enter a valid mobile number.'
            ],
            ['mobile', 'validateMobileUnique'],
            ['whatsapp', 'validateWhatsappUnique'],
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

    public function validateMobileUnique(
            string $attribute
    ): void
    {

        if (
                empty($this->$attribute) || $this->hasErrors()
        ) {
            return;
        }

        $exists = User::find()
                ->where([
                    'mobile' => $this->$attribute,
                ])
                ->andWhere([
                    '<>',
                    'id',
                    $this->user->id,
                ])
                ->exists();

        if ($exists) {

            $this->addError(
                    $attribute,
                    'This mobile number is already in use.'
            );
        }
    }

    public function validateWhatsappUnique(
            string $attribute
    ): void
    {

        if (
                empty($this->$attribute) || $this->hasErrors()
        ) {
            return;
        }

        $exists = User::find()
                ->where([
                    'whatsapp' => $this->$attribute,
                ])
                ->andWhere([
                    '<>',
                    'id',
                    $this->user->id,
                ])
                ->exists();

        if ($exists) {

            $this->addError(
                    $attribute,
                    'This WhatsApp number is already in use.'
            );
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
        $this->user->mobile = $this->mobile;
        $this->user->whatsapp = $this->whatsapp;
        if (empty($this->user->whatsapp) && !empty($this->user->mobile)) {
            $this->user->whatsapp = $this->user->mobile;
        }
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
