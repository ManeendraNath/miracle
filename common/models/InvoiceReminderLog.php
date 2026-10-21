<?php

namespace common\models;

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
class InvoiceReminderLog extends base\InvoiceReminderLog
{
    
}
