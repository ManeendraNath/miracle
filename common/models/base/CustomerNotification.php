<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "customer_notification".
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string|null $route
 * @property int $is_read
 * @property int|null $read_at
 * @property int $created_at
 *
 * @property User $user
 */
class CustomerNotification extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'customer_notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['route', 'read_at'], 'default', 'value' => null],
            [['is_read'], 'default', 'value' => 0],
            [['user_id', 'type', 'title', 'message', 'created_at'], 'required'],
            [['user_id', 'is_read', 'read_at', 'created_at'], 'integer'],
            [['message'], 'string'],
            [['type'], 'string', 'max' => 50],
            [['title'], 'string', 'max' => 255],
            [['route'], 'string', 'max' => 500],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'type' => 'Type',
            'title' => 'Title',
            'message' => 'Message',
            'route' => 'Route',
            'is_read' => 'Is Read',
            'read_at' => 'Read At',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

}
