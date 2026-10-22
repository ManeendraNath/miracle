<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "customer_preference".
 *
 * @property int $id
 * @property int $user_id
 * @property int $renewal_reminders
 * @property int $payment_reminders
 * @property int $service_expiry_notifications
 * @property int $support_notifications
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */
class CustomerPreference extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'customer_preference';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['support_notifications'], 'default', 'value' => 1],
            [['user_id', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'renewal_reminders', 'payment_reminders', 'service_expiry_notifications', 'support_notifications', 'created_at', 'updated_at'], 'integer'],
            [['user_id'], 'unique'],
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
            'renewal_reminders' => 'Renewal Reminders',
            'payment_reminders' => 'Payment Reminders',
            'service_expiry_notifications' => 'Service Expiry Notifications',
            'support_notifications' => 'Support Notifications',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
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
