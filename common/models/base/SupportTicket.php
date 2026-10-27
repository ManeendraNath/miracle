<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "support_ticket".
 *
 * @property int $id
 * @property string $ticket_number
 * @property int $user_id
 * @property string|null $service_type
 * @property int|null $service_id
 * @property string $subject
 * @property string $message
 * @property string $priority
 * @property string $status
 * @property string|null $admin_response
 * @property int|null $closed_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property SupportTicketAttachment[] $supportTicketAttachments
 * @property SupportTicketMessage[] $supportTicketMessages
 * @property User $user
 */
class SupportTicket extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'support_ticket';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['service_type', 'service_id', 'admin_response', 'closed_at'], 'default', 'value' => null],
            [['priority'], 'default', 'value' => 'Normal'],
            [['status'], 'default', 'value' => 'Open'],
            [['ticket_number', 'user_id', 'subject', 'message', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'service_id', 'closed_at', 'created_at', 'updated_at'], 'integer'],
            [['message', 'admin_response'], 'string'],
            [['ticket_number', 'service_type', 'status'], 'string', 'max' => 30],
            [['subject'], 'string', 'max' => 255],
            [['priority'], 'string', 'max' => 20],
            [['ticket_number'], 'unique'],
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
            'ticket_number' => 'Ticket Number',
            'user_id' => 'User ID',
            'service_type' => 'Service Type',
            'service_id' => 'Service ID',
            'subject' => 'Subject',
            'message' => 'Message',
            'priority' => 'Priority',
            'status' => 'Status',
            'admin_response' => 'Admin Response',
            'closed_at' => 'Closed At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[SupportTicketAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSupportTicketAttachments()
    {
        return $this->hasMany(SupportTicketAttachment::class, ['ticket_id' => 'id']);
    }

    /**
     * Gets query for [[SupportTicketMessages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSupportTicketMessages()
    {
        return $this->hasMany(SupportTicketMessage::class, ['ticket_id' => 'id']);
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
