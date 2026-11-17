<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "blog_post".
 *
 * @property int $id
 * @property int|null $category_id
 * @property string $title
 * @property string $slug
 * @property string $summary
 * @property string $body
 * @property string|null $cover_image
 * @property string|null $cover_alt
 * @property string|null $seo_title
 * @property int $status
 * @property int $is_featured
 * @property int $noindex
 * @property int $comments_open
 * @property int $send_newsletter
 * @property int|null $newsletter_started_at
 * @property int|null $newsletter_done_at
 * @property int|null $deleted_at
 * @property int|null $published_at
 * @property int|null $author_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Admin $author
 * @property BlogComment[] $blogComments
 * @property BlogNewsletterDelivery[] $blogNewsletterDeliveries
 * @property BlogPostRedirect[] $blogPostRedirects
 * @property BlogPostRevision[] $blogPostRevisions
 * @property BlogPostTag[] $blogPostTags
 * @property BlogPostView[] $blogPostViews
 * @property BlogCategory $category
 * @property BlogSubscriber[] $subscribers
 * @property BlogTag[] $tags
 */
class BlogPost extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blog_post';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'cover_image', 'cover_alt', 'seo_title', 'newsletter_started_at', 'newsletter_done_at', 'deleted_at', 'published_at', 'author_id'], 'default', 'value' => null],
            [['noindex'], 'default', 'value' => 0],
            [['send_newsletter'], 'default', 'value' => 1],
            [['category_id', 'status', 'is_featured', 'noindex', 'comments_open', 'send_newsletter', 'newsletter_started_at', 'newsletter_done_at', 'deleted_at', 'published_at', 'author_id', 'created_at', 'updated_at'], 'integer'],
            [['title', 'slug', 'summary', 'body', 'created_at', 'updated_at'], 'required'],
            [['body'], 'string'],
            [['title', 'cover_alt'], 'string', 'max' => 200],
            [['slug'], 'string', 'max' => 220],
            [['summary'], 'string', 'max' => 300],
            [['cover_image'], 'string', 'max' => 255],
            [['seo_title'], 'string', 'max' => 70],
            [['slug'], 'unique'],
            [['author_id'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::class, 'targetAttribute' => ['author_id' => 'id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => BlogCategory::class, 'targetAttribute' => ['category_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'title' => 'Title',
            'slug' => 'Slug',
            'summary' => 'Summary',
            'body' => 'Body',
            'cover_image' => 'Cover Image',
            'cover_alt' => 'Cover Alt',
            'seo_title' => 'Seo Title',
            'status' => 'Status',
            'is_featured' => 'Is Featured',
            'noindex' => 'Noindex',
            'comments_open' => 'Comments Open',
            'send_newsletter' => 'Send Newsletter',
            'newsletter_started_at' => 'Newsletter Started At',
            'newsletter_done_at' => 'Newsletter Done At',
            'deleted_at' => 'Deleted At',
            'published_at' => 'Published At',
            'author_id' => 'Author ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Author]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(Admin::class, ['id' => 'author_id']);
    }

    /**
     * Gets query for [[BlogComments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogComments()
    {
        return $this->hasMany(BlogComment::class, ['post_id' => 'id']);
    }

    /**
     * Gets query for [[BlogNewsletterDeliveries]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogNewsletterDeliveries()
    {
        return $this->hasMany(BlogNewsletterDelivery::class, ['post_id' => 'id']);
    }

    /**
     * Gets query for [[BlogPostRedirects]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogPostRedirects()
    {
        return $this->hasMany(BlogPostRedirect::class, ['post_id' => 'id']);
    }

    /**
     * Gets query for [[BlogPostRevisions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogPostRevisions()
    {
        return $this->hasMany(BlogPostRevision::class, ['post_id' => 'id']);
    }

    /**
     * Gets query for [[BlogPostTags]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogPostTags()
    {
        return $this->hasMany(BlogPostTag::class, ['post_id' => 'id']);
    }

    /**
     * Gets query for [[BlogPostViews]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogPostViews()
    {
        return $this->hasMany(BlogPostView::class, ['post_id' => 'id']);
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(BlogCategory::class, ['id' => 'category_id']);
    }

    /**
     * Gets query for [[Subscribers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubscribers()
    {
        return $this->hasMany(BlogSubscriber::class, ['id' => 'subscriber_id'])->viaTable('blog_newsletter_delivery', ['post_id' => 'id']);
    }

    /**
     * Gets query for [[Tags]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(BlogTag::class, ['id' => 'tag_id'])->viaTable('blog_post_tag', ['post_id' => 'id']);
    }
}
