<?php

declare(strict_types=1);

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use common\services\billing\AutoBillingService;
use common\services\billing\InvoiceDeliveryService;
use common\services\billing\InvoiceReminderService;
use common\services\billing\ServiceRenewalReminderService;
use common\models\WhatsappLog;

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
        return $this->runLocked('miracle:payment-reminders', 'Payment reminders', function (): string {
            $count = (new InvoiceReminderService())->sendDueReminders();
            return sprintf('Sent %d reminder(s).', $count);
        });
    }

    public function actionSendReminders(): int
    {
        return $this->runLocked('miracle:renewal-reminders', 'Renewal reminders', function (): string {
            $count = (new ServiceRenewalReminderService())->sendReminders();
            return sprintf('Sent %d reminder(s).', $count);
        });
    }

    public function actionWhatsappCleanup(): int
    {
        return $this->runLocked('miracle:whatsapp-cleanup', 'WhatsApp log cleanup', function (): string {
            $deleted = WhatsappLog::deleteAll(['<', 'created_at', strtotime('-90 days')]);
            return sprintf('Deleted %d log row(s).', $deleted);
        });
    }

    /**
     * Runs a job under a mutex so overlapping cron runs can't send duplicates,
     * and turns failures into a non-zero exit code cron can see.
     */
    private function runLocked(string $lockName, string $label, callable $job): int
    {
        $mutex = Yii::$app->mutex;
        if (!$mutex->acquire($lockName, 0)) {
            $this->stdout("{$label} already running.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }
        try {
            $this->stdout("{$label} completed. " . $job() . PHP_EOL);
            return ExitCode::OK;
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            $this->stderr("{$label} failed: " . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            $mutex->release($lockName);
        }
    }
}
