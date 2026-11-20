<?php

declare(strict_types=1);

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

/**
 * Setting up a copy sold to another business (install.php runs it after the migrations).
 *
 * php yii install/brand: the business's name, website and email in place of ours (SENDER_NAME, INSTALL_WEBSITE,
 * SUPPORT_EMAIL and ADMIN_EMAIL in .env; without a website, the portal's own address FRONTEND_BASE_URL). It runs
 * once: after that the business changes them in Site Settings.
 */
class InstallController extends Controller
{
    /** What the first migrations put in Site Settings and the first admin account: only that is ever replaced. */
    private const SEEDED_NAME = 'Miracle Web Technologies';
    private const SEEDED_ADMIN_EMAIL = 'miraclewebtechnologies@gmail.com';

    public function actionBrand(): int
    {
        // Never on our own site: it would empty our Site Settings.
        if (!\common\components\PortalOnly::enabled()) {
            $this->stderr("This is for a copy sold to another business (PORTAL_ONLY=true in .env): nothing done.\n");

            return ExitCode::CONFIG;
        }
        $name = trim((string) ($_ENV['SENDER_NAME'] ?? ''));
        // Site Settings needs one; without their own website the portal stands in (the portal footer then links none).
        $url = rtrim(trim((string) ($_ENV['INSTALL_WEBSITE'] ?? '')) ?: trim((string) ($_ENV['FRONTEND_BASE_URL'] ?? '')), '/');
        $admin = trim((string) ($_ENV['ADMIN_EMAIL'] ?? ''));
        // Customers see and write to the support address; the owner's own (ADMIN_EMAIL) is for staff alerts and sign-in.
        $support = trim((string) ($_ENV['SUPPORT_EMAIL'] ?? '')) ?: $admin;
        $db = Yii::$app->db;
        // The first admin account was made with our address: password resets and alerts would come to us. Checked on
        // its own, so it is put right even when Site Settings were already changed.
        if (filter_var($admin, FILTER_VALIDATE_EMAIL) !== false) {
            $db->createCommand()->update('{{%admin}}', ['email' => $admin, 'updated_at' => time()], ['username' => 'superadmin', 'email' => self::SEEDED_ADMIN_EMAIL])->execute();
        }
        // An update reruns this: done once, nothing in .env is needed again.
        if ((new Query())->select('site_name')->from('{{%site_setting}}')->scalar() !== self::SEEDED_NAME) {
            $this->stdout("Site Settings already have your details: change them there.\n");

            return ExitCode::OK;
        }
        if ($name === '' || $url === '' || filter_var($admin, FILTER_VALIDATE_EMAIL) === false || filter_var($support, FILTER_VALIDATE_EMAIL) === false) {
            $missing = array_keys(array_filter([
                'SENDER_NAME (your business name)' => $name === '',
                'FRONTEND_BASE_URL (the portal address)' => $url === '',
                'ADMIN_EMAIL (a valid address)' => filter_var($admin, FILTER_VALIDATE_EMAIL) === false,
                'SUPPORT_EMAIL (a valid address, or leave it out)' => trim((string) ($_ENV['SUPPORT_EMAIL'] ?? '')) !== '' && filter_var($support, FILTER_VALIDATE_EMAIL) === false,
            ]));
            $this->stderr('Set ' . implode(', ', $missing) . " in .env first.\n");

            return ExitCode::CONFIG;
        }
        // Ours out, theirs in; what is not asked for is left empty for Site Settings.
        $db->createCommand()->update('{{%site_setting}}', [
            'site_name' => $name, 'site_url' => $url, 'email_1' => $support, 'email_2' => null, 'email_3' => null,
            'mobile_1' => 0, 'mobile_2' => null, 'whatsapp' => null, 'tawk_chat' => null,
            'address_line_1' => null, 'address_line_2' => null, 'address_line_3' => null,
            'tagline' => null, 'services_line' => null, 'grievance_name' => null, 'grievance_email' => $support,
            'founded_year' => (int) date('Y'), 'terms_url' => null, 'refund_url' => null, 'privacy_url' => null,
            'facebook' => null, 'x' => null, 'youtube' => null, 'linkedin' => null, 'instagram' => null,
            'google_site_verification' => null, 'bing_site_verification' => null, 'indexnow_key' => null,
            'updated_at' => time(),
        ])->execute();
        $this->stdout("Site Settings now show {$name}. Add your phone, address and policy pages there.\n");

        return ExitCode::OK;
    }
}
