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
 * @property BlogTag[] $tags
 * @property BlogPostRevision[] $revisions
 * @property int $comments_open New comments are accepted (old ones stay shown either way)
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

    /** The form's tags, comma-separated ("SEO, Web apps"); null when not on the form, so tags stay as they are. */
    public $tagNames;

    /** Title, summary and text before this save, kept as a revision when they change. */
    private ?array $_before = null;

    /** The post, the version before it and its tags are saved together or not at all. */
    #[\Override]
    public function transactions(): array
    {
        return [self::SCENARIO_DEFAULT => self::OP_ALL];
    }

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
            [['title', 'slug', 'summary', 'seo_title', 'cover_alt'], 'trim'],
            // Not on the form (null): left null, so the post's tags stay as they are.
            ['tagNames', 'trim', 'skipOnEmpty' => true],
            [['status', 'is_featured'], 'default', 'value' => 0],
            [['is_featured', 'noindex', 'comments_open'], 'boolean'],
            ['tagNames', 'string', 'max' => 500],
            ['tagNames', function (string $attribute): void {
                foreach (array_filter(array_map('trim', explode(',', (string) $this->$attribute))) as $name) {
                    if (mb_strlen($name) > 60) {
                        $this->addError($attribute, 'Keep each tag to 60 characters at most: "' . mb_substr($name, 0, 20) . '…".');
                    } elseif (BlogTag::slugFor($name) === '') {
                        $this->addError($attribute, 'Use letters or digits in the tag "' . $name . '".');
                    }
                }
            }],
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
                    'tagNames' => 'Tags',
                    'cover_alt' => 'Cover description (alt text)',
                    'is_featured' => 'Feature at the top of the blog',
                    'noindex' => 'Keep out of search engines',
                    'comments_open' => 'Allow comments',
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
    public function getAuthor(): ActiveQuery
    {
        return $this->hasOne(Admin::class, ['id' => 'author_id']);
    }

    #[\Override]
    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(BlogCategory::class, ['id' => 'category_id']);
    }

    #[\Override]
    public function getTags(): ActiveQuery
    {
        return $this->hasMany(BlogTag::class, ['id' => 'tag_id'])->viaTable('{{%blog_post_tag}}', ['post_id' => 'id'])->orderBy(['name' => SORT_ASC]);
    }

    /** Earlier versions, newest first. */
    public function getRevisions(): ActiveQuery
    {
        return $this->hasMany(BlogPostRevision::class, ['post_id' => 'id'])->orderBy(['id' => SORT_DESC]);
    }

    /** The tags as typed on the form. */
    public function tagNamesText(): string
    {
        return implode(', ', array_map(static fn(BlogTag $tag): string => $tag->name, $this->tags));
    }

    #[\Override]
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if (!$insert) {
            $old = ['title' => (string) $this->getOldAttribute('title'), 'summary' => (string) $this->getOldAttribute('summary'), 'body' => (string) $this->getOldAttribute('body')];
            $this->_before = $old !== ['title' => (string) $this->title, 'summary' => (string) $this->summary, 'body' => (string) $this->body] ? $old : null;
        }
        if ($this->publishAt) {
            $this->published_at = (int) strtotime((string) $this->publishAt);
        } elseif ((int) $this->status === self::STATUS_PUBLISHED && (!$this->published_at || ($this->publishAt === '' && (int) $this->published_at > time()))) {
            // Publish on left empty means now; a scheduled post whose date was cleared on the form ('') too.
            $this->published_at = time();
        }

        return true;
    }

    #[\Override]
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        if ($this->_before !== null) {
            // The version before this edit; editor is whoever is signed in to the admin (none in console runs).
            $editor = \Yii::$app->id === 'app-backend' ? \Yii::$app->user->id : null;
            (new BlogPostRevision($this->_before + ['post_id' => $this->id, 'author_id' => $editor, 'created_at' => time()]))->save(false);
            $keep = BlogPostRevision::find()->select('id')->where(['post_id' => $this->id])->orderBy(['id' => SORT_DESC])->offset(BlogPostRevision::KEEP)->column();
            if ($keep !== []) {
                BlogPostRevision::deleteAll(['id' => $keep]);
            }
            $this->_before = null;
        }
        if ($this->tagNames !== null) {
            $ids = array_map(static fn(BlogTag $tag): int => (int) $tag->id, BlogTag::findOrCreate((string) $this->tagNames));
            $db = static::getDb();
            $db->createCommand()->delete('{{%blog_post_tag}}', ['post_id' => $this->id])->execute();
            if ($ids !== []) {
                $db->createCommand()->batchInsert('{{%blog_post_tag}}', ['post_id', 'tag_id'], array_map(fn(int $id): array => [$this->id, $id], $ids))->execute();
            }
            unset($this->tags);
        }
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

    /**
     * The body as shown on the website: Heading 2 and 3 get anchors for the table of contents, and a section headed
     * "Frequently asked questions" (or "FAQ") is read as questions (its Heading 3s) and their answers.
     *
     * @return array{html: string, toc: list<array{level: int, id: string, text: string}>, faq: list<array{question: string, answer: string}>}
     */
    public function rendered(): array
    {
        $dom = new \DOMDocument();
        // Saved bodies are cleaned article markup; the XML prefix keeps UTF-8 text as it is.
        @$dom->loadHTML('<?xml encoding="UTF-8"><div id="post">' . (string) $this->body . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $dom->getElementById('post');
        if ($root === null) {
            return ['html' => (string) $this->body, 'toc' => [], 'faq' => []];
        }
        $toc = [];
        $faq = [];
        $ids = [];
        $inFaq = false;
        $asking = false; // inside a question: text goes to the last $faq entry's answer
        foreach (iterator_to_array($root->childNodes) as $node) {
            if ($node instanceof \DOMElement && in_array($node->nodeName, ['h2', 'h3'], true)) {
                $text = trim($node->textContent);
                // Prefixed: never the same as an id the page layout uses ("main", "content").
                $id = 'h-' . (Inflector::slug($text) ?: 'section');
                for ($n = 2, $base = $id; isset($ids[$id]); $n++) {
                    $id = $base . '-' . $n;
                }
                $ids[$id] = true;
                $node->setAttribute('id', $id);
                $toc[] = ['level' => (int) $node->nodeName[1], 'id' => $id, 'text' => $text];
                if ($node->nodeName === 'h2') {
                    $inFaq = preg_match('/\b(frequently asked questions|faqs?)\b/i', $text) === 1;
                    $asking = false;
                } elseif ($inFaq) {
                    $faq[] = ['question' => $text, 'answer' => ''];
                    $asking = true;
                }
                continue;
            }
            if ($asking) {
                // List items and lines stay separate words ("One Two", not "OneTwo").
                $faq[array_key_last($faq)]['answer'] .= ' ' . html_entity_decode(strip_tags((string) preg_replace('#<br\s*/?>|</(li|p)>#i', ' $0', (string) $dom->saveHTML($node))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        $faq = array_values(array_filter(
            array_map(static fn(array $item): array => ['question' => $item['question'], 'answer' => trim((string) preg_replace('/\s+/', ' ', $item['answer']))], $faq),
            static fn(array $item): bool => $item['answer'] !== '',
        ));
        $html = '';
        foreach ($root->childNodes as $node) {
            $html .= $dom->saveHTML($node);
        }

        return ['html' => $html, 'toc' => $toc, 'faq' => $faq];
    }

    /** When the text last changed after it went live (a day's grace for first fixes); null when it never did. */
    public function contentUpdatedAt(): ?int
    {
        if (!$this->published_at) {
            return null;
        }
        $last = (int) BlogPostRevision::find()->where(['post_id' => $this->id])->max('created_at');

        return $last > (int) $this->published_at + 86400 ? $last : null;
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
