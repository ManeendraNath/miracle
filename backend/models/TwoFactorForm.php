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
    public $trustDevice = false;

    private const TRUST_COOKIE = '_admin_trusted_device';
    private const TRUST_DAYS = 30;

    public static function start(Admin $admin): void
    {
        Yii::$app->session->set(self::SESSION_KEY, ['id' => $admin->id, 'expires' => time() + self::TTL]);
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
            ['trustDevice', 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return ['code' => 'Authenticator code or backup code', 'trustDevice' => 'Trust this device for ' . self::TRUST_DAYS . ' days'];
    }

    /** Logs the pending admin in when the code is valid; wrong codes count toward the login throttle. */
    public function login(): bool
    {
        $admin = self::pendingAdmin();
        if ($admin === null || !$this->validate()) {
            return false;
        }
        if (LoginThrottle::isLocked(LoginThrottle::SCOPE_ADMIN_CODE, $admin->username)) {
            $this->addError('code', 'Too many failed attempts. Please try again in 15 minutes.');
            return false;
        }
        $usedBackupCode = false;
        if (!$admin->consumeTotpCode((string) $this->code)) {
            if (!$admin->consumeBackupCode((string) $this->code)) {
                LoginThrottle::fail(LoginThrottle::SCOPE_ADMIN_CODE, $admin->username);
                $this->addError('code', 'That code is not valid. Check the time on your phone and try again.');
                return false;
            }
            $usedBackupCode = true;
        }
        Yii::$app->session->remove(self::SESSION_KEY);
        LoginThrottle::clear('backend', $admin->username);
        LoginThrottle::clear(LoginThrottle::SCOPE_ADMIN_CODE, $admin->username);
        if ($this->trustDevice) {
            self::trustThisDevice($admin);
        }
        if ($usedBackupCode) {
            $left = $admin->backupCodesLeft();
            Yii::$app->session->setFlash('warning', "You signed in with a backup code. {$left} left: create new ones under My account when you can.");
            \common\services\admin\AdminAlertService::twoFactorChanged($admin->username, "signed in with a backup code ({$left} left)");
        }
        return Yii::$app->user->login($admin);
    }

    /** After a correct code, this browser may skip the code step for a while; the password is still asked. */
    private static function trustThisDevice(Admin $admin): void
    {
        $expires = time() + self::TRUST_DAYS * 86400;
        Yii::$app->response->cookies->add(new \yii\web\Cookie([
            'name' => self::TRUST_COOKIE,
            'value' => $admin->id . ':' . $expires . ':' . self::deviceSignature($admin, $expires),
            'expire' => $expires,
            'httpOnly' => true,
            'secure' => (bool) (Yii::$app->request->csrfCookie['secure'] ?? false) || Yii::$app->request->isSecureConnection, // same setting as the session and CSRF cookies
            'sameSite' => \yii\web\Cookie::SAME_SITE_LAX,
        ]));
    }

    public static function isTrustedDevice(Admin $admin): bool
    {
        $parts = explode(':', (string) Yii::$app->request->cookies->getValue(self::TRUST_COOKIE, ''));
        if (count($parts) !== 3) {
            return false;
        }
        [$id, $expires, $signature] = $parts;

        return (int) $id === (int) $admin->id && (int) $expires > time() && hash_equals(self::deviceSignature($admin, (int) $expires), $signature);
    }

    /** Stops matching when the password changes (new auth key) or two-factor is reset or set up again (new secret). */
    private static function deviceSignature(Admin $admin, int $expires): string
    {
        return hash_hmac('sha256', $admin->id . ':' . $expires, $admin->auth_key . '|' . $admin->totp_secret);
    }
}
