<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\HtmlPurifier;
use yii\helpers\Inflector;

/**
 * A blog post. Shown on the website at /blog/{slug} once Published and its publish time has passed.
 *
 * @property int $id
 * @property int|null $category_id
 * @property string $title
 * @property string $slug
 * @property string $summary Search result description and list teaser
 * @property string $body HTML, cleaned on save
 * @property string|null $cover_image Path under the frontend web root, e.g. uploads/blog/2026-11/ab12.jpg
 * @property string|null $seo_title Title for search results when the post title is too long
 * @property int $status
 * @property int|null $published_at
 * @property int|null $author_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property BlogCategory|null $category
 * @property Admin|null $author
 */
class BlogPost extends base\BlogPost
{
    use AutoTimestampTrait;

    public const STATUS_DRAFT = 0;
    public const STATUS_PUBLISHED = 1;

    /** Where post images live, under the frontend web root (served directly by the web server). */
    public const UPLOAD_DIR = 'uploads/blog';

    /** The form's date and time field (datetime-local); published_at is set from it. */
    public $publishAt;

    #[\Override]
    public function behaviors(): array
    {
        return ArrayHelper::merge(parent::behaviors(), [TimestampBehavior::class]);
    }

    #[\Override]
    public function rules(): array
    {
        // Only what the base class lacks, and first: its "slug required" must see the slug made from the title.
        $rules = [
            [['title', 'slug', 'summary', 'seo_title'], 'trim'],
            ['slug', 'filter', 'filter' => fn($slug): string => Inflector::slug((string) ($slug ?: $this->title))],
            // Server only: the browser can't run the filter above, so an empty field would wrongly fail there.
            ['slug', 'required', 'message' => 'Use at least one letter or digit in the title.', 'enableClientValidation' => false],
            ['slug', 'compare', 'compareValue' => 'feed', 'operator' => '!==', 'type' => 'string', 'message' => 'The web address "feed" is taken by the blog\'s RSS feed. Choose another.'],
            // Pasted or typed HTML could carry scripts: only plain article markup is kept.
            ['body', 'filter', 'filter' => [self::class, 'cleanBody']],
            ['body', 'required', 'message' => 'Write the post before saving.'],
            ['status', 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PUBLISHED]],
            ['publishAt', 'datetime', 'format' => 'php:Y-m-d\TH:i', 'skipOnEmpty' => true, 'message' => 'Pick a date and time.'],
        ];
        return ArrayHelper::merge($rules, parent::rules());
    }

    /** Set by the app only, never from a form: still validated, but not mass-assigned ("!"). */
    #[\Override]
    public function scenarios(): array
    {
        $internal = ['cover_image', 'author_id', 'published_at', 'created_at', 'updated_at'];

        return array_map(
            static fn(array $attributes): array => array_map(static fn(string $name): string => in_array($name, $internal, true) ? '!' . $name : $name, $attributes),
            parent::scenarios(),
        );
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
                    'category_id' => 'Category',
                    'slug' => 'Web address',
                    'summary' => 'Summary',
                    'body' => 'Post',
                    'cover_image' => 'Cover image',
                    'seo_title' => 'Search result title',
                    'publishAt' => 'Publish on',
                    'published_at' => 'Published',
        ]);
    }

    #[\Override]
    public function afterFind(): void
    {
        parent::afterFind();
        $this->publishAt = $this->published_at ? date('Y-m-d\TH:i', (int) $this->published_at) : null;
    }

    #[\Override]
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($this->publishAt) {
            $this->published_at = (int) strtotime((string) $this->publishAt);
        } elseif ((int) $this->status === self::STATUS_PUBLISHED && !$this->published_at) {
            $this->published_at = time();
        }

        return true;
    }

    /** Article markup only: headings, paragraphs, lists, quotes, code, links and images. */
    public static function cleanBody(?string $html): string
    {
        $clean = HtmlPurifier::process((string) $html, static function ($config): void {
            $config->set('HTML.Allowed', 'h2,h3,h4,p,br,strong,b,em,i,u,s,blockquote,pre,code,ul,ol,li,a[href|title|target|rel],img[src|alt|width|height]');
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('HTML.TargetNoopener', true);
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('AutoFormat.RemoveEmpty', true);
            // No made-up alt text from the file name; an image without a description gets an empty one.
            $config->set('Attr.DefaultImageAlt', '');
        });

        // An editor that was left empty still sends <p><br></p>.
        return trim(strip_tags($clean, '<img>')) === '' ? '' : $clean;
    }

    /** Posts visitors can see: Published, and their publish time has passed. */
    public static function findPublic(): ActiveQuery
    {
        return self::find()->where(['status' => self::STATUS_PUBLISHED])->andWhere(['<=', 'published_at', time()]);
    }

    public function isPublic(): bool
    {
        return (int) $this->status === self::STATUS_PUBLISHED && $this->published_at !== null && (int) $this->published_at <= time();
    }

    /** Draft, Scheduled or Published, for the admin list. */
    public function stateLabel(): string
    {
        return match (true) {
            (int) $this->status !== self::STATUS_PUBLISHED => 'Draft',
            !$this->isPublic() => 'Scheduled',
            default => 'Published',
        };
    }

    /** Whole minutes at about 220 words a minute, at least 1. */
    public function readingMinutes(): int
    {
        return max(1, (int) round(str_word_count(strip_tags((string) $this->body)) / 220));
    }

    /** @return array<int, string> */
    public static function statuses(): array
    {
        return [self::STATUS_DRAFT => 'Draft', self::STATUS_PUBLISHED => 'Published'];
    }
}
