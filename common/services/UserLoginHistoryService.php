<?php

declare(strict_types=1);

namespace common\services;

use common\models\User;
use common\models\UserLoginHistory;
use Yii;
use yii\web\Cookie;

final class UserLoginHistoryService
{
    /** Signed cookie listing the customer ids that have signed in on this browser before. */
    private const DEVICE_COOKIE = '_known-accounts';

    public function record(int $userId): void
    {
        try {
            $firstLogin = !UserLoginHistory::find()->where(['user_id' => $userId])->exists();

            $model = new UserLoginHistory();

            $model->user_id = $userId;

            $model->ip_address = Yii::$app->request->userIP;

            $model->user_agent = mb_substr((string) Yii::$app->request->userAgent, 0, 1000);

            $model->logged_in_at = time();

            if (!$model->save(false)) {
                throw new \RuntimeException('Login history ' . 'could not be saved.');
            }

            if ($this->rememberDevice($userId) && !$firstLogin) {
                $this->sendNewDeviceEmail($userId, $model);
            }
        } catch (\Throwable $e) {
            Yii::error(
                [
                    'message' => 'Unable to record ' . 'login history.',
                    'userId' => $userId,
                    'error' => $e->getMessage(),
                ],
                __METHOD__,
            );
        }
    }

    /**
     * Marks this browser as known for the user; true when it was not known before.
     * ponytail: "device" = this browser's cookie, so clearing cookies or a private window
     * counts as new. Fine for an alert; store device tokens server-side if it gets noisy.
     */
    private function rememberDevice(int $userId): bool
    {
        $known = array_filter(explode(',', (string) Yii::$app->request->cookies->getValue(self::DEVICE_COOKIE, '')));
        if (in_array((string) $userId, $known, true)) {
            return false;
        }
        $known[] = (string) $userId;
        Yii::$app->response->cookies->add(new Cookie([
            'name' => self::DEVICE_COOKIE,
            'value' => implode(',', array_slice($known, -10)),
            'expire' => time() + 400 * 86400,
            'httpOnly' => true,
            'secure' => Yii::$app->request->isSecureConnection,
            'sameSite' => Cookie::SAME_SITE_LAX,
        ]));
        return true;
    }

    private function sendNewDeviceEmail(int $userId, UserLoginHistory $login): void
    {
        $user = User::findOne($userId);
        if ($user === null || trim((string) $user->email) === '') {
            return;
        }
        $sent = Yii::$app->mailer
            ->compose(
                ['html' => 'new-device-login-html', 'text' => 'new-device-login-text'],
                ['user' => $user, 'login' => $login, 'device' => self::describeDevice((string) $login->user_agent)],
            )
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($user->email)
            ->setSubject('New sign-in to your ' . Yii::$app->params['senderName'] . ' account')
            ->send();
        if (!$sent) {
            Yii::warning(['message' => 'New device login email not sent.', 'userId' => $userId], __METHOD__);
        }
    }

    /** "Chrome on Windows" from a user agent; good enough for a customer to recognise. */
    public static function describeDevice(string $userAgent): string
    {
        $match = static function (array $patterns) use ($userAgent): ?string {
            foreach ($patterns as $needle => $name) {
                if (stripos($userAgent, $needle) !== false) {
                    return $name;
                }
            }
            return null;
        };
        // Order matters: Edge and Opera also say Chrome; Chrome also says Safari; Android says Linux.
        $browser = $match(['Edg/' => 'Edge', 'OPR/' => 'Opera', 'SamsungBrowser' => 'Samsung Internet', 'Firefox/' => 'Firefox', 'CriOS' => 'Chrome', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari']);
        $os = $match(['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iPhone', 'iPad' => 'iPad', 'Mac OS X' => 'Mac', 'CrOS' => 'Chromebook', 'Linux' => 'Linux']);
        if ($browser === null && $os === null) {
            return 'Unknown device';
        }
        return trim(($browser ?? 'Browser') . ($os !== null ? " on {$os}" : ''));
    }
}
