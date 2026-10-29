<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "service_reminder_log".
 *
 * @property int $id
 * @property string $service_type
 * @property int $service_id
 * @property int $user_id
 * @property string $reminder_key
 * @property int $days_before
 * @property int $sent_at
 * @property int $created_at
 *
 * @property User $user
 */
class ServiceReminderLog extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'service_reminder_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['service_type', 'service_id', 'user_id', 'reminder_key', 'days_before', 'sent_at', 'created_at'], 'required'],
            [['service_id', 'user_id', 'days_before', 'sent_at', 'created_at'], 'integer'],
            [['service_type'], 'string', 'max' => 50],
            [['reminder_key'], 'string', 'max' => 100],
            [['service_type', 'service_id', 'reminder_key'], 'unique', 'targetAttribute' => ['service_type', 'service_id', 'reminder_key']],
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
            'service_type' => 'Service Type',
            'service_id' => 'Service ID',
            'user_id' => 'User ID',
            'reminder_key' => 'Reminder Key',
            'days_before' => 'Days Before',
            'sent_at' => 'Sent At',
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
