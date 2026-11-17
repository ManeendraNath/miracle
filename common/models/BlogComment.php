<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * A reader's comment on a blog post, shown once staff approve it, or a staff reply to one (parent_id, admin_id).
 *
 * @property BlogPost $post
 * @property BlogComment|null $parent
 * @property Admin|null $admin
 * @property BlogComment[] $replies
 */
class BlogComment extends base\BlogComment
{
    use AutoTimestampTrait;

    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_SPAM = 2;

    /** What a reader fills in on the post page; everything else is set by the app. */
    public const SCENARIO_READER = 'reader';

    /** Comments one address can send in RATE_MINUTES before it has to wait. */
    public const RATE_LIMIT = 3;
    public const RATE_MINUTES = 10;

    #[\Override]
    public function behaviors(): array
    {
        return ArrayHelper::merge(parent::behaviors(), [TimestampBehavior::class]);
    }

    #[\Override]
    public function rules(): array
    {
        return ArrayHelper::merge([
            [['author_name', 'author_email', 'body'], 'trim'],
            [['author_name', 'body'], 'required'],
            ['author_email', 'required', 'on' => self::SCENARIO_READER],
            ['author_email', 'email'],
            ['body', 'string', 'max' => 5000],
            ['status', 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_SPAM]],
        ], parent::rules());
    }

    #[\Override]
    public function scenarios(): array
    {
        return [self::SCENARIO_READER => ['author_name', 'author_email', 'body']] + parent::scenarios();
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'author_name' => 'Name',
            'author_email' => 'Email',
            'body' => 'Comment',
        ]);
    }

    #[\Override]
    public function getPost(): ActiveQuery
    {
        return $this->hasOne(BlogPost::class, ['id' => 'post_id']);
    }

    #[\Override]
    public function getParent(): ActiveQuery
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    #[\Override]
    public function getAdmin(): ActiveQuery
    {
        return $this->hasOne(Admin::class, ['id' => 'admin_id']);
    }

    /** Approved staff replies, oldest first. */
    public function getReplies(): ActiveQuery
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])->andOnCondition(['status' => self::STATUS_APPROVED])->orderBy(['id' => SORT_ASC]);
    }

    /** Approved reader comments of a post with their replies, oldest first. */
    public static function shownFor(int $postId): ActiveQuery
    {
        return self::find()->where(['post_id' => $postId, 'parent_id' => null, 'status' => self::STATUS_APPROVED])->with('replies')->orderBy(['id' => SORT_ASC]);
    }

    /** Whether this address has sent RATE_LIMIT comments in the last RATE_MINUTES (on any post). */
    public static function tooManyFrom(?string $ip): bool
    {
        return $ip !== null && self::find()->where(['ip' => $ip])->andWhere(['>=', 'created_at', time() - self::RATE_MINUTES * 60])->count() >= self::RATE_LIMIT;
    }

    /**
     * Kept as Spam, without an alert: more than two links, an opt-out line next to a link (mass outreach), or a name
     * made of links. Narrow on purpose: staff still read what lands in Pending.
     */
    public function looksLikeSpam(): bool
    {
        $links = preg_match_all('~https?://|www\.~i', $this->body);

        return $links > 2
            || preg_match('~https?://|www\.~i', $this->author_name) === 1
            || ($links > 0 && preg_match('/\b(unsubscribe|opt[ -]?out)\b/i', $this->body) === 1);
    }

    public function isStaffReply(): bool
    {
        return $this->parent_id !== null;
    }

    /** @return array<int, string> */
    public static function statuses(): array
    {
        return [self::STATUS_PENDING => 'Waiting', self::STATUS_APPROVED => 'Shown', self::STATUS_SPAM => 'Spam'];
    }
}
