<?php

namespace common\components\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use common\models\Invoice;
use common\models\ServiceRenewalHistory;
use common\models\Domains;
use common\models\Hosting;
use common\models\Maintenance;

class InvoiceAutomatedRenewalBehavior extends Behavior
{
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterInvoiceUpdate',
        ];
    }

    public function afterInvoiceUpdate($event)
    {
        /** @var Invoice $invoice */
        $invoice = $this->owner;

        // Check if status changed to 'Paid' during this update execution loop
        $changedAttributes = $event->changedAttributes;
        if (isset($changedAttributes['status']) && $invoice->status === 'Paid') {
            
            // Query all child items attached to this invoice billing statement
            foreach ($invoice->invoiceItems as $item) {
                if (in_array($item->item_type, ['domain', 'hosting', 'maintenance'])) {
                    $this->processServiceExtension($item, $invoice);
                }
            }
        }
    }

    private function processServiceExtension($item, $invoice)
    {
        $asset = null;
        $baseDate = date('Y-m-d');

        // 1. Locate the live corresponding service asset reference record model mapping
        if ($item->item_type === 'domain') {
            $asset = Domains::findOne($item->item_id);
        } elseif ($item->item_type === 'hosting') {
            $asset = Hosting::findOne($item->item_id);
        } elseif ($item->item_type === 'maintenance') {
            $asset = Maintenance::findOne($item->item_id);
        }

        if ($asset === null) {
            return; // Skip if asset reference is missing
        }

        // 2. Continuous lifecycle handling strategy:
        // If the service is active, extend from its current expiration date.
        // If the service has already expired, extend from today's date.
        if (!empty($asset->current_expiry_date) && $asset->current_expiry_date > date('Y-m-d')) {
            $baseDate = $asset->current_expiry_date;
        }

        // Calculate extension cycle milestones (Assumes standard 1 year / 12 month extension)
        $monthsToExtend = 12 * $item->quantity; 
        $newExpiryDate = date('Y-m-d', strtotime("+$monthsToExtend months", strtotime($baseDate)));

        // 3. Log into service_renewal_history
        $history = new ServiceRenewalHistory();
        $history->item_type = $item->item_type;
        $history->item_id = $item->item_id;
        $history->invoice_id = $invoice->id;
        $history->billed_amount = $item->total_price;
        $history->renewal_period_months = $monthsToExtend;
        $history->start_date = $baseDate;
        $history->end_date = $newExpiryDate;
        $history->logged_at = time();
        $history->save(false);

        // 4. Update the parent asset master table instance records data constraints directly
        $asset->current_expiry_date = $newExpiryDate;
        $asset->status = 1; // Mark state status code representation directly as active
        $asset->save(false);
    }
}
