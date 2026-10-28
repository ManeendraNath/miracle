<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "admin_notification".
 *
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string|null $route
 * @property int $is_read
 * @property int|null $read_at
 * @property int $created_at
 */
class AdminNotification extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'admin_notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['route', 'read_at'], 'default', 'value' => null],
            [['is_read'], 'default', 'value' => 0],
            [['type', 'title', 'message', 'created_at'], 'required'],
            [['message'], 'string'],
            [['is_read', 'read_at', 'created_at'], 'integer'],
            [['type'], 'string', 'max' => 50],
            [['title'], 'string', 'max' => 255],
            [['route'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'title' => 'Title',
            'message' => 'Message',
            'route' => 'Route',
            'is_read' => 'Is Read',
            'read_at' => 'Read At',
            'created_at' => 'Created At',
        ];
    }

}
