<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "support_ticket_message".
 *
 * @property int $id
 * @property int $ticket_id
 * @property string $sender_type
 * @property int|null $sender_id
 * @property string $message
 * @property int $created_at
 *
 * @property SupportTicketAttachment[] $supportTicketAttachments
 * @property SupportTicket $ticket
 */
class SupportTicketMessage extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'support_ticket_message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sender_id'], 'default', 'value' => null],
            [['ticket_id', 'sender_type', 'message', 'created_at'], 'required'],
            [['ticket_id', 'sender_id', 'created_at'], 'integer'],
            [['message'], 'string'],
            [['sender_type'], 'string', 'max' => 20],
            [['ticket_id'], 'exist', 'skipOnError' => true, 'targetClass' => SupportTicket::class, 'targetAttribute' => ['ticket_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ticket_id' => 'Ticket ID',
            'sender_type' => 'Sender Type',
            'sender_id' => 'Sender ID',
            'message' => 'Message',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[SupportTicketAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSupportTicketAttachments()
    {
        return $this->hasMany(SupportTicketAttachment::class, ['ticket_message_id' => 'id']);
    }

    /**
     * Gets query for [[Ticket]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicket()
    {
        return $this->hasOne(SupportTicket::class, ['id' => 'ticket_id']);
    }

}
