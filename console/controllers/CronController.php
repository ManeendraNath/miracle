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
use common\services\payment\RazorpayService;
use common\models\WhatsappLog;
use common\models\Invoice;

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
        /** @var \yii\mutex\Mutex $mutex */
        $mutex = Yii::$app->get('mutex');
        $lockName = 'miracle:auto-billing';
        if (!$mutex->acquire($lockName, 0)) {
            $this->stdout("Billing cron already running.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }
        try {
            Yii::info('Automatic billing cron started.', __METHOD__);
            $invoices = (new AutoBillingService())->run();
            $count = count($invoices);
            $this->stdout("Billing completed. Generated {$count} invoice(s).\n", Console::FG_GREEN);
            foreach ($invoices as $invoice) {
                $this->stdout(sprintf("%s | Client #%d | %.2f\n", $invoice->invoice_number, $invoice->user_id, $invoice->total_payable));
            }

            // Email this run's invoices plus any recent ones whose earlier send failed.
            $failed = 0;
            $deliveryService = new InvoiceDeliveryService();
            foreach ($this->invoicesAwaitingEmail() as $invoice) {
                try {
                    $deliveryService->send($invoice);
                } catch (\Throwable $e) {
                    $failed++;
                    Yii::error(['message' => 'Automatic invoice email failed.', 'invoiceId' => (int) $invoice->id, 'invoiceNumber' => $invoice->invoice_number, 'error' => $e->getMessage()], __METHOD__);
                    $this->stderr("Invoice email failed: {$invoice->invoice_number}: {$e->getMessage()}\n", Console::FG_RED);
                }
            }
            Yii::info(['message' => 'Automatic billing completed.', 'invoiceCount' => $count, 'emailFailures' => $failed], __METHOD__);
            if ($failed > 0) {
                $this->stderr("Billing finished with {$failed} invoice email failure(s); they will be retried on the next run.\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
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

    /**
     * Unsent payable invoices from the last 7 days. The window keeps invoices
     * created before emailed_at existed from being mass-mailed.
     *
     * @return Invoice[]
     */
    private function invoicesAwaitingEmail(): array
    {
        return Invoice::find()
            ->where(['emailed_at' => null, 'status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]])
            ->andWhere(['>=', 'created_at', strtotime('-7 days')])
            ->orderBy(['id' => SORT_ASC])
            ->all();
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

    /**
     * Records Razorpay payments whose callback and webhook were both missed, and fills in Razorpay's fees.
     * Every 30 minutes: php yii cron/razorpay-sync
     */
    public function actionRazorpaySync(): int
    {
        return $this->runLocked('miracle:razorpay-sync', 'Razorpay sync', function (): string {
            $razorpay = new RazorpayService();
            if (!$razorpay->isEnabled()) {
                return 'Razorpay is not enabled.';
            }
            $result = $razorpay->syncPending();
            if ($result['errors'] > 0) {
                throw new \RuntimeException(sprintf('Recorded %d missed payment(s) and %d fee(s); %d lookup(s) failed, see the app log.', $result['captured'], $result['fees'], $result['errors']));
            }
            return sprintf('Recorded %d missed payment(s) and %d fee(s).', $result['captured'], $result['fees']);
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
    /**
     * Skip every job while deploy.sh has the site in maintenance mode; the next
     * scheduled run picks the work up.
     */
    public function beforeAction($action): bool
    {
        if (is_file(dirname(Yii::getAlias('@common')) . '/runtime/maintenance.flag')) {
            $this->stdout("Maintenance mode (deploy in progress): skipping {$action->id}.\n", Console::FG_YELLOW);
            return false;
        }
        return parent::beforeAction($action);
    }

    private function runLocked(string $lockName, string $label, callable $job): int
    {
        /** @var \yii\mutex\Mutex $mutex */
        $mutex = Yii::$app->get('mutex');
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
