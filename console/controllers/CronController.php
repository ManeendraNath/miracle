<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use common\services\billing\AutoBillingService;
use common\services\billing\InvoiceDeliveryService;
use common\services\billing\InvoiceReminderService;
use common\services\billing\ServiceRenewalReminderService;

class CronController extends Controller
{

    /**
     * Generate automatic renewal invoices for:
     *
     * - Domains
     * - Hosting
     * - Maintenance
     *
     * Usage:
     *
     * php yii cron/run-billing
     */
    public function actionRunBilling(): int
    {
        $mutex = Yii::$app->mutex;
        $lockName = 'miracle:auto-billing';
        if (!$mutex->acquire($lockName, 0)) {
            $this->stdout("Billing cron already running.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }
        try {
            Yii::info('Automatic billing cron started.', __METHOD__);
            $invoices = (new AutoBillingService())->run();
            $deliveryService = new InvoiceDeliveryService();
            foreach ($invoices as $invoice) {
                try {
                    $deliveryService->send($invoice);
                } catch (\Throwable $e) {
                    Yii::error(
                            [
                                'message' => 'Automatic invoice email failed.',
                                'invoiceId' => (int) $invoice->id,
                                'invoiceNumber' => $invoice->invoice_number,
                                'error' => $e->getMessage(),
                            ],
                            __METHOD__
                    );
                }
            }
            $count = count($invoices);
            Yii::info(['message' => 'Automatic billing completed.', 'invoiceCount' => $count], __METHOD__);
            $this->stdout("Billing completed successfully. Generated {$count} invoice(s).\n", Console::FG_GREEN);
            foreach ($invoices as $invoice) {
                $this->stdout(sprintf("%s | Client #%d | %.2f\n", $invoice->invoice_number, $invoice->user_id, $invoice->total_payable));
            }
            return ExitCode::OK;
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            $this->stderr('Billing failed: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            $mutex->release($lockName);
        }
    }

    public function actionPaymentReminders(): int
    {
        try {
            $count = (new InvoiceReminderService())->sendDueReminders();
            $this->stdout(sprintf("Payment reminders completed. Sent %d reminder(s).\n", $count));
            return self::EXIT_CODE_NORMAL;
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            $this->stderr('Payment reminders failed: ' . $e->getMessage() . PHP_EOL);
            return self::EXIT_CODE_ERROR;
        }
    }

    public function actionSendReminders(): void
    {
        $count = (new ServiceRenewalReminderService())->sendReminders();
        $this->stdout("Sent: {$count}\n");
    }

    public function actionWhatsappCleanup(): void
    {
        WhatsAppLog::deleteAll(['<', 'created_at', strtotime('-90 days')]);
    }
}
