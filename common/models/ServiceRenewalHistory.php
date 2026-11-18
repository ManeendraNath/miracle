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
    /**
     * What each renewal was for, e.g. "Domain example.com", in one query per service type.
     * @param self[] $renewals
     * @return array<int, string> renewal id => label
     */
    public static function serviceLabels(array $renewals): array
    {
        $sources = ['domain' => [Domains::class, 'domain_url'], 'hosting' => [Hosting::class, 'primary_domain'], 'maintenance' => [Maintenance::class, 'website_url']];
        $names = [];
        foreach ($sources as $type => [$class, $column]) {
            $ids = array_map(static fn(self $r): int => (int) $r->item_id, array_filter($renewals, static fn(self $r): bool => $r->item_type === $type));
            $names[$type] = $ids === [] ? [] : $class::find()->select($column)->where(['id' => $ids])->indexBy('id')->column();
        }
        $labels = [];
        foreach ($renewals as $renewal) {
            $name = $names[$renewal->item_type][$renewal->item_id] ?? '';
            $labels[(int) $renewal->id] = trim(ucfirst((string) $renewal->item_type) . ' ' . $name);
        }

        return $labels;
    }
}
