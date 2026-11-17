<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "blog_subscriber".
 *
 * @property int $id
 * @property string $email
 * @property int $status
 * @property string $token
 * @property string|null $ip
 * @property int|null $confirm_sent_at
 * @property int|null $confirmed_at
 * @property int|null $unsubscribed_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property BlogNewsletterDelivery[] $blogNewsletterDeliveries
 * @property BlogPost[] $posts
 */
class BlogSubscriber extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blog_subscriber';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip', 'confirm_sent_at', 'confirmed_at', 'unsubscribed_at'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 0],
            [['email', 'token', 'created_at', 'updated_at'], 'required'],
            [['status', 'confirm_sent_at', 'confirmed_at', 'unsubscribed_at', 'created_at', 'updated_at'], 'integer'],
            [['email'], 'string', 'max' => 255],
            [['token'], 'string', 'max' => 64],
            [['ip'], 'string', 'max' => 45],
            [['email'], 'unique'],
            [['token'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'status' => 'Status',
            'token' => 'Token',
            'ip' => 'Ip',
            'confirm_sent_at' => 'Confirm Sent At',
            'confirmed_at' => 'Confirmed At',
            'unsubscribed_at' => 'Unsubscribed At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[BlogNewsletterDeliveries]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBlogNewsletterDeliveries()
    {
        return $this->hasMany(BlogNewsletterDelivery::class, ['subscriber_id' => 'id']);
    }

    /**
     * Gets query for [[Posts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPosts()
    {
        return $this->hasMany(BlogPost::class, ['id' => 'post_id'])->viaTable('blog_newsletter_delivery', ['subscriber_id' => 'id']);
    }
}
