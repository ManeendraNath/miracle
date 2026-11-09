<?php

declare(strict_types=1);

namespace common\services\customer;

use common\models\CustomerBillingProfile;
use common\models\User;
use common\services\admin\AdminAlertService;

/**
 * Customer-side create/update of the billing profile. Invoices copy the profile when they are
 * generated, so a change here only affects future invoices.
 */
final class CustomerBillingDetailsService
{
    public static function forUser(User $user): CustomerBillingProfile
    {
        // Same default as an admin-created profile: no GST until an admin turns it on.
        $profile = CustomerBillingProfile::findOne(['user_id' => $user->id])
                ?? new CustomerBillingProfile(['user_id' => $user->id, 'is_gst_applicable' => 0, 'default_discount_amount' => 0]);
        $profile->scenario = CustomerBillingProfile::SCENARIO_CUSTOMER;

        return $profile;
    }

    /** Loads only the customer-safe attributes from $post; alerts admins when something actually changed. */
    public static function save(CustomerBillingProfile $profile, array $post): bool
    {
        $profile->scenario = CustomerBillingProfile::SCENARIO_CUSTOMER; // never mass-assign GST/discount
        if (!$profile->load($post) || !$profile->validate()) {
            return false;
        }
        $created = $profile->isNewRecord;
        // An empty country is an older profile, which counts as India.
        $oldCountry = $created ? null : ((string) $profile->getOldAttribute('country') ?: \common\models\CustomerBillingProfile::COUNTRY_INDIA);
        $oldCountry = $oldCountry === $profile->country ? null : $oldCountry;
        $changed = $created || $profile->getDirtyAttributes() !== [];
        if (!$profile->save()) {
            return false;
        }
        if ($changed) {
            AdminAlertService::billingDetailsChanged($profile->id, (string) $profile->user->username, $profile->legal_name, $created, $oldCountry === null ? null : $oldCountry . ' to ' . $profile->country);
        }

        return true;
    }
}
