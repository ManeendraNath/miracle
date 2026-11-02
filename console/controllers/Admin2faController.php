<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\Admin;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Lost authenticator app and no other Superadmin to reset it in the panel:
 *
 *   php yii admin-2fa/reset <username>
 */
class Admin2faController extends Controller
{
    /** Turns off two-factor sign-in for the admin; they log in with the password only until they set it up again. */
    public function actionReset(string $username): int
    {
        $admin = Admin::findOne(['username' => $username]);
        if ($admin === null) {
            $this->stderr("No admin with username \"{$username}\".\n");
            return ExitCode::DATAERR;
        }
        if (!$admin->hasTwoFactor()) {
            $this->stdout("Two-factor sign-in is already off for {$admin->username}.\n");
            return ExitCode::OK;
        }
        $admin->disableTwoFactor();
        $this->stdout("Two-factor sign-in is now off for {$admin->username}.\n");
        return ExitCode::OK;
    }
}
