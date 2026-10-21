<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "invoice_reminder_log".
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $reminder_key
 * @property int $days_from_due
 * @property string $recipient_email
 * @property int $sent_at
 * @property int $created_at
 *
 * @property Invoice $invoice
 */
class InvoiceReminderLog extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice_reminder_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_id', 'reminder_key', 'days_from_due', 'recipient_email', 'sent_at', 'created_at'], 'required'],
            [['invoice_id', 'days_from_due', 'sent_at', 'created_at'], 'integer'],
            [['reminder_key'], 'string', 'max' => 50],
            [['recipient_email'], 'string', 'max' => 255],
            [['invoice_id', 'reminder_key'], 'unique', 'targetAttribute' => ['invoice_id', 'reminder_key']],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'invoice_id' => 'Invoice ID',
            'reminder_key' => 'Reminder Key',
            'days_from_due' => 'Days From Due',
            'recipient_email' => 'Recipient Email',
            'sent_at' => 'Sent At',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Invoice]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::class, ['id' => 'invoice_id']);
    }

}
