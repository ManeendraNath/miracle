<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\CustomerBillingProfile;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Lists billing profiles saved before the current validation rules (state list, GSTIN/PAN
 * format and check digit), which would fail the next time an admin edits them. Read-only.
 *
 *   php yii billing-profile/check
 */
class BillingProfileController extends Controller
{
    public function actionCheck(): int
    {
        $invalid = 0;
        foreach (CustomerBillingProfile::find()->with('user')->orderBy('id')->each() as $profile) {
            /** @var CustomerBillingProfile $profile */
            // validate() only normalises attributes in memory; nothing is saved.
            if ($profile->validate()) {
                continue;
            }
            $invalid++;
            $this->stdout("#{$profile->id} {$profile->legal_name} (customer: " . ($profile->user->username ?? $profile->user_id) . ")\n");
            foreach ($profile->getFirstErrors() as $attribute => $error) {
                $this->stdout("    {$attribute}: {$error}\n");
            }
        }
        $this->stdout($invalid === 0 ? "All billing profiles pass validation.\n" : "{$invalid} profile(s) need fixing: edit them in the admin panel (Billing Profiles).\n");
        return $invalid === 0 ? ExitCode::OK : ExitCode::DATAERR;
    }
}
