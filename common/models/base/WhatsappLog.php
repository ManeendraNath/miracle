<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "whatsapp_log".
 *
 * @property int $id
 * @property string $mobile
 * @property string|null $template_name
 * @property string|null $message
 * @property string|null $request_payload
 * @property string|null $response_payload
 * @property string $status
 * @property string|null $error_message
 * @property string|null $event_key
 * @property int $created_at
 */
class WhatsappLog extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'whatsapp_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['template_name', 'message', 'request_payload', 'response_payload', 'error_message', 'event_key'], 'default', 'value' => null],
            [['mobile', 'status', 'created_at'], 'required'],
            [['message', 'request_payload', 'response_payload', 'error_message'], 'string'],
            [['created_at'], 'integer'],
            [['mobile', 'status'], 'string', 'max' => 30],
            [['template_name'], 'string', 'max' => 100],
            [['event_key'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mobile' => 'Mobile',
            'template_name' => 'Template Name',
            'message' => 'Message',
            'request_payload' => 'Request Payload',
            'response_payload' => 'Response Payload',
            'status' => 'Status',
            'error_message' => 'Error Message',
            'event_key' => 'Event Key',
            'created_at' => 'Created At',
        ];
    }

}
