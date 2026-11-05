<?php

declare(strict_types=1);

namespace backend\models;

use common\components\LoginThrottle;
use common\models\Admin;
use common\models\User;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;

/**
 * Sets a new admin password from an emailed link. Other sessions are signed out; two-factor stays on.
 */
final class AdminResetPasswordForm extends Model
{
    use \common\models\traits\ScalarInputTrait;

    public string $password = '';
    private Admin $_admin;

    public function __construct(string $token, array $config = [])
    {
        $admin = $token === '' ? null : Admin::findByPasswordResetToken($token);
        if ($admin === null) {
            throw new InvalidArgumentException('Wrong password reset token.');
        }
        $this->_admin = $admin;
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            ['password', 'required'],
            ['password', 'string', 'min' => Yii::$app->params['user.passwordMinLength'], 'max' => 255],
            ['password', 'match', 'pattern' => User::PASSWORD_PATTERN, 'message' => User::PASSWORD_MESSAGE],
        ];
    }

    public function resetPassword(): void
    {
        $admin = $this->_admin;
        $admin->setPassword($this->password);
        $admin->removePasswordResetToken();
        $admin->generateAuthKey();
        $admin->updateAttributes(['password_hash' => $admin->password_hash, 'password_reset_token' => null, 'auth_key' => $admin->auth_key, 'updated_at' => time()]);
        LoginThrottle::clear('backend', (string) $admin->username);
        Yii::warning("Password reset by email link for admin #{$admin->id}", 'admin-auth');
        try {
            \common\services\admin\AdminAlertService::adminPasswordReset((string) $admin->username);
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
        }
    }
}
