<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "blog_post_view".
 *
 * @property int $id
 * @property int $post_id
 * @property string $day
 * @property string $visitor
 *
 * @property BlogPost $post
 */
class BlogPostView extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blog_post_view';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['post_id', 'day', 'visitor'], 'required'],
            [['post_id'], 'integer'],
            [['day'], 'safe'],
            [['visitor'], 'string', 'max' => 32],
            [['post_id', 'day', 'visitor'], 'unique', 'targetAttribute' => ['post_id', 'day', 'visitor']],
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
            'day' => 'Day',
            'visitor' => 'Visitor',
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
