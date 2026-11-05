<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "lead".
 *
 * @property int $id
 * @property string $lead_name
 * @property string|null $phone_number
 * @property string|null $email
 * @property string|null $source
 * @property string|null $subject
 * @property string|null $message
 * @property string $status
 * @property int $created_at
 * @property int $updated_at
 */
class Lead extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lead';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone_number', 'email', 'source', 'subject', 'message'], 'default', 'value' => null],
            [['lead_name', 'status', 'created_at', 'updated_at'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['lead_name'], 'string', 'max' => 150],
            [['phone_number'], 'string', 'max' => 30],
            [['source'], 'string', 'max' => 100],
            [['email', 'subject'], 'string', 'max' => 255],
            [['message'], 'string'],
            [['status'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'lead_name' => 'Lead Name',
            'phone_number' => 'Phone Number',
            'email' => 'Email',
            'source' => 'Source',
            'subject' => 'Subject',
            'message' => 'Message',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
