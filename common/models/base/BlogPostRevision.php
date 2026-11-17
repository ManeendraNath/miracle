<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "blog_post_revision".
 *
 * @property int $id
 * @property int $post_id
 * @property string $title
 * @property string $summary
 * @property string $body
 * @property int|null $author_id
 * @property int $created_at
 *
 * @property Admin $author
 * @property BlogPost $post
 */
class BlogPostRevision extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blog_post_revision';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['author_id'], 'default', 'value' => null],
            [['post_id', 'title', 'summary', 'body', 'created_at'], 'required'],
            [['post_id', 'author_id', 'created_at'], 'integer'],
            [['body'], 'string'],
            [['title'], 'string', 'max' => 200],
            [['summary'], 'string', 'max' => 300],
            [['author_id'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::class, 'targetAttribute' => ['author_id' => 'id']],
            [['post_id'], 'exist', 'skipOnError' => true, 'targetClass' => BlogPost::class, 'targetAttribute' => ['post_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'post_id' => 'Post ID',
            'title' => 'Title',
            'summary' => 'Summary',
            'body' => 'Body',
            'author_id' => 'Author ID',
            'created_at' => 'Created At',
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
     * Gets query for [[Post]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPost()
    {
        return $this->hasOne(BlogPost::class, ['id' => 'post_id']);
    }
}
