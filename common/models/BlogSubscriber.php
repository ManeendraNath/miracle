<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * A newsletter subscriber. Gets new blog posts by email once they confirm their address (double opt-in); every email
 * carries their token, which confirms or unsubscribes without signing in.
 */
class BlogSubscriber extends base\BlogSubscriber
{
    use AutoTimestampTrait;

    public const STATUS_PENDING = 0;
    public const STATUS_SUBSCRIBED = 1;
    public const STATUS_UNSUBSCRIBED = 2;

    /** Sign-up attempts one IP address can make in an hour. */
    public const RATE_LIMIT = 5;

    /** A waiting address gets at most one confirmation email in this many hours, however often it is entered. */
    public const RESEND_HOURS = 24;

    /** Sign-ups never confirmed are deleted after this many days by the weekly cleanup. */
    public const UNCONFIRMED_DAYS = 30;

    #[\Override]
    public function behaviors(): array
    {
        return ArrayHelper::merge(parent::behaviors(), [TimestampBehavior::class]);
    }

    #[\Override]
    public function rules(): array
    {
        return ArrayHelper::merge([
            ['email', 'trim'],
            ['email', 'filter', 'filter' => 'mb_strtolower'],
            ['email', 'required', 'message' => 'Enter your email address.'],
            ['email', 'email'],
            ['status', 'in', 'range' => array_keys(self::statuses())],
        ], parent::rules());
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return ArrayHelper::merge(parent::attributeLabels(), ['email' => 'Email address']);
    }

    /**
     * Records a sign-up. Returns the subscriber when a confirmation email should go out now (then call
     * confirmationSent()), null when not: already subscribed, or one went out in the last RESEND_HOURS. The visitor
     * sees the same message either way.
     */
    public static function signUp(string $email, ?string $ip): ?self
    {
        $email = mb_strtolower(trim($email));
        $subscriber = self::findOne(['email' => $email]);
        if ($subscriber === null) {
            $subscriber = new self(['email' => $email, 'token' => self::newToken()]);
        } elseif ($subscriber->status === self::STATUS_SUBSCRIBED) {
            return null;
        } elseif ($subscriber->status === self::STATUS_UNSUBSCRIBED) {
            // Links in old emails stop working: coming back takes a new confirmation.
            $subscriber->token = self::newToken();
        } elseif ((int) $subscriber->confirm_sent_at > time() - self::RESEND_HOURS * 3600) {
            return null;
        }
        $subscriber->status = self::STATUS_PENDING;
        $subscriber->ip = $ip;
        if (!$subscriber->save()) {
            throw new \RuntimeException('The subscriber could not be saved: ' . implode(' ', $subscriber->getFirstErrors()));
        }

        return $subscriber;
    }

    public function confirmationSent(): void
    {
        $this->updateAttributes(['confirm_sent_at' => time()]);
    }

    /**
     * Counts a sign-up attempt from this IP address and says whether it is over RATE_LIMIT in the hour.
     * ponytail: counted in the file cache, not atomically; a few extra attempts in a burst don't matter here.
     */
    public static function overLimit(?string $ip): bool
    {
        if ($ip === null) {
            return false;
        }
        return \common\components\RateLimit::hit('newsletter-signups:' . $ip, self::RATE_LIMIT);
    }

    /** Only a waiting sign-up: an unsubscribed reader is never put back by an old link. Returns whether it is subscribed now. */
    public function confirm(): bool
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->updateAttributes(['status' => self::STATUS_SUBSCRIBED, 'confirmed_at' => time(), 'unsubscribed_at' => null, 'updated_at' => time()]);
        }

        return $this->status === self::STATUS_SUBSCRIBED;
    }

    /** Sign-ups nobody confirmed within UNCONFIRMED_DAYS: no consent, so nothing kept. Returns how many. */
    public static function deleteUnconfirmed(): int
    {
        return self::deleteAll(['and', ['status' => self::STATUS_PENDING], ['<', 'updated_at', time() - self::UNCONFIRMED_DAYS * 86400]]);
    }

    private static function newToken(): string
    {
        return Yii::$app->security->generateRandomString(40);
    }

    public function unsubscribe(): void
    {
        if ($this->status !== self::STATUS_UNSUBSCRIBED) {
            $this->updateAttributes(['status' => self::STATUS_UNSUBSCRIBED, 'unsubscribed_at' => time(), 'updated_at' => time()]);
        }
    }

    /** Links for emails: built from the public site address, so they work from the cron job too. */
    public function emailLink(string $action): string
    {
        return rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/') . '/blog/' . $action . '?token=' . urlencode($this->token);
    }

    /** @return array<int, string> */
    public static function statuses(): array
    {
        return [self::STATUS_PENDING => 'Not confirmed', self::STATUS_SUBSCRIBED => 'Subscribed', self::STATUS_UNSUBSCRIBED => 'Unsubscribed'];
    }
}
