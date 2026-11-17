<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "blog_post_redirect".
 *
 * @property int $id
 * @property string $old_slug
 * @property int $post_id
 * @property int $created_at
 *
 * @property BlogPost $post
 */
class BlogPostRedirect extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blog_post_redirect';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['old_slug', 'post_id', 'created_at'], 'required'],
            [['post_id', 'created_at'], 'integer'],
            [['old_slug'], 'string', 'max' => 255],
            [['old_slug'], 'unique'],
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
            'old_slug' => 'Old Slug',
            'post_id' => 'Post ID',
            'created_at' => 'Created At',
        ];
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
