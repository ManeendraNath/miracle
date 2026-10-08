<?php

namespace common\models;

/**
 * This is the model class for table "service_renewal_history".
 *
 * @property int $id
 * @property string $item_type
 * @property int $item_id The Primary Key ID of the corresponding domain, hosting, or AMC record
 * @property int|null $invoice_id Link to billing record if applicable
 * @property int|null $transaction_id Link to transaction log gateway reference
 * @property float $billed_amount The exact rate charged for this specific year extension
 * @property int $renewal_period_months Usually 12 months, or custom adjustments
 * @property string $start_date Cycle coverage commencement date
 * @property string $end_date Calculated new expiry timestamp threshold
 * @property int $logged_at Timestamp of payment verification execution
 *
 * @property Invoice $invoice
 * @property Transaction $transaction
 */
class ServiceRenewalHistory extends base\ServiceRenewalHistory
{

}
