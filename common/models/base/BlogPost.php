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
 * @property string|null $seo_title
 * @property int $status
 * @property int|null $published_at
 * @property int|null $author_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Admin $author
 * @property BlogCategory $category
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
            [['category_id', 'cover_image', 'seo_title', 'published_at', 'author_id'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 0],
            [['category_id', 'status', 'published_at', 'author_id', 'created_at', 'updated_at'], 'integer'],
            [['title', 'slug', 'summary', 'body', 'created_at', 'updated_at'], 'required'],
            [['body'], 'string'],
            [['title'], 'string', 'max' => 200],
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
            'seo_title' => 'Seo Title',
            'status' => 'Status',
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
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(BlogCategory::class, ['id' => 'category_id']);
    }
}
