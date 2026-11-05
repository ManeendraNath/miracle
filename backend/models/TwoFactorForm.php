<?php

declare(strict_types=1);

namespace backend\models;

use common\components\LoginThrottle;
use Yii;
use yii\base\Model;

/**
 * Second login step for admins with two-factor on: the password was right, the
 * authenticator code is still owed. Only the admin id waits in the session.
 */
final class TwoFactorForm extends Model
{
    use \common\models\traits\ScalarInputTrait;

    private const SESSION_KEY = 'admin2faPending';
    private const TTL = 300;

    public $code;

    public static function start(Admin $admin, int $duration): void
    {
        Yii::$app->session->set(self::SESSION_KEY, ['id' => $admin->id, 'duration' => $duration, 'expires' => time() + self::TTL]);
    }

    /** The admin waiting for the code step, or null when none or it expired. */
    public static function pendingAdmin(): ?Admin
    {
        $pending = Yii::$app->session->get(self::SESSION_KEY);
        if (!is_array($pending) || $pending['expires'] < time()) {
            Yii::$app->session->remove(self::SESSION_KEY);
            return null;
        }
        $admin = Admin::findIdentity($pending['id']);
        return $admin !== null && $admin->hasTwoFactor() ? $admin : null;
    }

    public function rules()
    {
        return [
            ['code', 'trim'],
            ['code', 'required'],
        ];
    }

    public function attributeLabels()
    {
        return ['code' => 'Authenticator code'];
    }

    /** Logs the pending admin in when the code is valid; wrong codes count toward the login throttle. */
    public function login(): bool
    {
        $admin = self::pendingAdmin();
        if ($admin === null || !$this->validate()) {
            return false;
        }
        if (LoginThrottle::isLocked('backend', $admin->username)) {
            $this->addError('code', 'Too many failed attempts. Please try again in 15 minutes.');
            return false;
        }
        if (!$admin->consumeTotpCode((string) $this->code)) {
            LoginThrottle::fail('backend', $admin->username);
            $this->addError('code', 'That code is not valid. Check the time on your phone and try again.');
            return false;
        }
        $duration = (int) Yii::$app->session->get(self::SESSION_KEY)['duration'];
        Yii::$app->session->remove(self::SESSION_KEY);
        LoginThrottle::clear('backend', $admin->username);
        return Yii::$app->user->login($admin, $duration);
    }
}
