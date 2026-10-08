<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use common\models\Domains;
use common\models\Hosting;
use common\models\Maintenance;
use common\models\InvoiceItem;

class CronController extends Controller
{

    /**
     * Scans system databases infrastructure thresholds to dispatch expiry email notice warnings.
     * Command execution: php yii cron/check-expiries
     */
    public function actionCheckExpiries()
    {
        $this->stdout("Commencing system asset expiration verification scanning layer...\n", Console::FG_CYAN);

        $targetIntervals = [1, 2, 3, 4, 5, 10, 15, 30]; // Reminder warning schedules window metrics

        foreach ($targetIntervals as $days) {
            $targetDate = date('Y-m-d', strtotime("+$days days"));

            // Scan asset domains datasets
            $domains = Domains::find()->where(['current_expiry_date' => $targetDate, 'status' => 1])->all();
            foreach ($domains as $domain) {
                $this->sendReminderEmail('domain', $domain, $domain->domain_url, $domain->user, $days);
            }

            // Scan hosting server array datasets
            $hostings = Hosting::find()->where(['current_expiry_date' => $targetDate, 'status' => 1])->all();
            foreach ($hostings as $hosting) {
                $this->sendReminderEmail('hosting', $hosting, $hosting->primary_domain, $hosting->user, $days);
            }

            // Scan AMC contract datasets
            $maintenances = Maintenance::find()->where(['current_expiry_date' => $targetDate, 'status' => 1])->all();
            foreach ($maintenances as $amc) {
                $this->sendReminderEmail('maintenance', $amc, $amc->website_url, $amc->user, $days);
            }
        }

        $this->stdout("Scan operation layer complete.\n", Console::FG_GREEN);
    }

    private function sendReminderEmail($type, $asset, $name, $user, $daysRemaining)
    {
        if ($user === null || empty($user->email)) {
            return;
        }

        // Defensive check block: Prevent redundant invoice spam alerts if they are already processing an existing payment renewal order
        $alreadyInvoiced = InvoiceItem::find()
                        ->joinWith('invoice')
                        ->where([
                            'item_type' => $type,
                            'item_id' => $asset->id,
                            'invoice.status' => 'Unpaid'
                        ])->exists();

        if ($alreadyInvoiced) {
            $this->stdout("Skipping alert email notification for $type ($name). Unpaid invoice active.\n", Console::FG_YELLOW);
            return;
        }

        // Initialize Yii2 Mailer component processing
        $mail = Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['supportEmail'] => 'Miracle Web Technologies Operations'])
                ->setTo($user->email)
                ->setSubject("CRITICAL RENEWAL NOTICE: $name expires in $daysRemaining days")
                ->setHtmlBody("
                <h3>Hello {$user->username},</h3>
                <p>This is an automated lifecycle systems alert tracking system message regarding your active contract account.</p>
                <p>Your service asset type <strong>" . strtoupper($type) . "</strong> for identity entry <strong>$name</strong> is scheduled to expire on <strong>{$asset->current_expiry_date}</strong>.</p>
                <p>Please log into your client hub area dashboard panel immediately to clear balance processing fees to avoid downtime disruptions.</p>
                <br>
                <p>Regards,<br>Billing Team<br>Miracle Web Technologies</p>
            ");

        if ($mail->send()) {
            $this->stdout("Successfully dispatched expiry reminder warning notice to {$user->email} for asset: $name\n", Console::FG_GREEN);
        }
    }
}
