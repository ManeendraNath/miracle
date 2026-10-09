<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use yii\helpers\Html;
use common\models\Domains;
use common\models\Hosting;
use common\models\Maintenance;
use common\models\InvoiceItem;
use common\models\Invoice;
use common\models\User;

class CronController extends Controller
{
    /**
     * Scans system databases infrastructure thresholds to dispatch expiry email notice warnings.
     * Command execution: php yii cron/check-expiries
     */
    public function actionCheckExpiries()
    {
        $this->stdout("Commencing system asset expiration verification scanning layer...\n", Console::FG_CYAN);

        $targetIntervals = [1, 5, 10, 20, 30]; // Reminder warning schedules window metrics

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

    /**
     * SCANS FOR UNPAID INVOICES DUE TOMORROW
     * Command execution: php yii cron/check-invoices
     */
    public function actionCheckInvoices()
    {
        $this->stdout("Commencing outstanding client invoice verification scan...\n", Console::FG_CYAN);
        
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        // Fetch all invoices that are Unpaid/Partially Paid and due precisely tomorrow
        $invoices = Invoice::find()
            ->where(['status' => ['Unpaid', 'Partially_Paid']])
            ->andWhere(['due_date' => $tomorrow])
            ->all();

        if (empty($invoices)) {
            $this->stdout("No outstanding invoices due tomorrow ($tomorrow).\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        }

        $count = 0;
        foreach ($invoices as $invoice) {
            $client = User::findOne($invoice->user_id);
            if ($client && !empty($client->email)) {
                $sent = Yii::$app->mailer->compose()
                    ->setFrom([Yii::$app->params['supportEmail'] => 'Miracle Web Technologies Billing'])
                    ->setTo($client->email)
                    ->setSubject("URGENT REMINDER: Invoice #{$invoice->invoice_number} is due tomorrow")
                    ->setHtmlBody("
                        <h3>Hello " . Html::encode($invoice->client_name) . ",</h3>
                        <p>This is an automated operational alert system reminder that your billing statement invoice <strong>#{$invoice->invoice_number}</strong> is due tomorrow (<strong>" . date('d M Y', strtotime($invoice->due_date)) . "</strong>).</p>
                        <ul>
                            <li><strong>Invoice Number Reference:</strong> {$invoice->invoice_number}</li>
                            <li><strong>Outstanding Balance Due:</strong> ₹" . number_format($invoice->total_payable, 2) . "</li>
                        </ul>
                        <p>Please log into your client hub area dashboard panel immediately to settle your outstanding ledger statement balance to ensure seamless continuity of services.</p>
                        <br>
                        <p>Regards,<br>Billing Team<br>Miracle Web Technologies</p>
                    ")
                    ->send();

                if ($sent) {
                    $count++;
                    $this->stdout("Dispatched due-date reminder notice to {$client->email} for invoice: {$invoice->invoice_number}\n", Console::FG_GREEN);
                }
            }
        }

        $this->stdout("Invoice check layer complete. Dispatched $count payments alerts warnings.\n", Console::FG_GREEN);
        return Controller::EXIT_CODE_NORMAL;
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
