<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "blog_newsletter_delivery".
 *
 * @property int $post_id
 * @property int $subscriber_id
 * @property int $sent_at
 * @property int $failed
 *
 * @property BlogPost $post
 * @property BlogSubscriber $subscriber
 */
class BlogNewsletterDelivery extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blog_newsletter_delivery';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['failed'], 'default', 'value' => 0],
            [['post_id', 'subscriber_id', 'sent_at'], 'required'],
            [['post_id', 'subscriber_id', 'sent_at', 'failed'], 'integer'],
            [['post_id', 'subscriber_id'], 'unique', 'targetAttribute' => ['post_id', 'subscriber_id']],
            [['post_id'], 'exist', 'skipOnError' => true, 'targetClass' => BlogPost::class, 'targetAttribute' => ['post_id' => 'id']],
            [['subscriber_id'], 'exist', 'skipOnError' => true, 'targetClass' => BlogSubscriber::class, 'targetAttribute' => ['subscriber_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'post_id' => 'Post ID',
            'subscriber_id' => 'Subscriber ID',
            'sent_at' => 'Sent At',
            'failed' => 'Failed',
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

    /**
     * Gets query for [[Subscriber]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubscriber()
    {
        return $this->hasOne(BlogSubscriber::class, ['id' => 'subscriber_id']);
    }
}
