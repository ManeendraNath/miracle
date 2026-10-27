<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "support_ticket_attachment".
 *
 * @property int $id
 * @property int $ticket_id
 * @property int|null $ticket_message_id
 * @property int $uploaded_by
 * @property string $file_name
 * @property string $file_path
 * @property int $file_size
 * @property string|null $mime_type
 * @property int $created_at
 *
 * @property SupportTicket $ticket
 * @property SupportTicketMessage $ticketMessage
 * @property User $uploadedBy
 */
class SupportTicketAttachment extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'support_ticket_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ticket_message_id', 'mime_type'], 'default', 'value' => null],
            [['ticket_id', 'uploaded_by', 'file_name', 'file_path', 'file_size', 'created_at'], 'required'],
            [['ticket_id', 'ticket_message_id', 'uploaded_by', 'file_size', 'created_at'], 'integer'],
            [['file_name'], 'string', 'max' => 255],
            [['file_path'], 'string', 'max' => 500],
            [['mime_type'], 'string', 'max' => 100],
            [['ticket_message_id'], 'exist', 'skipOnError' => true, 'targetClass' => SupportTicketMessage::class, 'targetAttribute' => ['ticket_message_id' => 'id']],
            [['ticket_id'], 'exist', 'skipOnError' => true, 'targetClass' => SupportTicket::class, 'targetAttribute' => ['ticket_id' => 'id']],
            [['uploaded_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['uploaded_by' => 'id']],
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
            'ticket_message_id' => 'Ticket Message ID',
            'uploaded_by' => 'Uploaded By',
            'file_name' => 'File Name',
            'file_path' => 'File Path',
            'file_size' => 'File Size',
            'mime_type' => 'Mime Type',
            'created_at' => 'Created At',
        ];
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

    /**
     * Gets query for [[TicketMessage]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTicketMessage()
    {
        return $this->hasOne(SupportTicketMessage::class, ['id' => 'ticket_message_id']);
    }

    /**
     * Gets query for [[UploadedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUploadedBy()
    {
        return $this->hasOne(User::class, ['id' => 'uploaded_by']);
    }

}
