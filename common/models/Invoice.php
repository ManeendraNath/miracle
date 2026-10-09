<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "invoice".
 *
 * @property int $id
 * @property string $invoice_number
 * @property string $client_name
 * @property string|null $client_address_line_1
 * @property string|null $client_address_line_2
 * @property float|null $discount_amount
 * @property float|null $cgst_percent
 * @property float|null $sgst_percent
 * @property float|null $igst_percent
 * @property string|null $status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property InvoiceItem[] $invoiceItems
 */
class Invoice extends base\Invoice
{

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
            [
                'class' => \common\components\behaviors\InvoiceAutomatedRenewalBehavior::class,
            ],
        ];
    }

    /**
     * SERVER-SIDE AUTOMATED REVENUE MATHEMATICS PROTOCOL
     * Intercepts incoming form data and recalculates balances securely before writing to database.
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $subtotal = (float) $this->subtotal_amount;
            $discount = (float) $this->discount_amount;

            $netTaxable = $subtotal - $discount;
            if ($netTaxable < 0) {
                $netTaxable = 0.00;
            }

            // Extract percentages out of validation request inputs
            $cgst = $netTaxable * ((float) $this->cgst_percent / 100);
            $sgst = $netTaxable * ((float) $this->sgst_percent / 100);
            $igst = $netTaxable * ((float) $this->igst_percent / 100);

            // Dynamically lock down total payable field securely
            $this->total_payable = number_format(($netTaxable + $cgst + $sgst + $igst), 2, '.', '');

            return true;
        }
        return false;
    }

    /**
     * 🛡️ AUTOMATED COMPLIANCE VERIFIER RELATIONAL LINK
     * Safely maps the billing record directly to the main system user account model
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * 🔒 PRODUCTION INVOICE PROTECTION GATEKEEPER
     * Throws an instant denial if a customer account has been suspended by an administrator
     */
    public function checkClientAccess()
    {
        // Traverses the relational user object data matrix fields context
        if ($this->user !== null && (int) $this->user->status !== User::STATUS_ACTIVE) {
            throw new ForbiddenHttpException('Access Denied. The associated client profile account has been suspended or locked.');
        }
        return true;
    }
}
