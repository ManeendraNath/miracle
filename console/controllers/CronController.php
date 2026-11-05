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
    use \console\traits\LogLinePrefixTrait;

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
        $lockName = AutoBillingService::LOCK_NAME;
        $problems = null;
        $locked = false;
        try {
            // A few seconds' wait: a staff member may be saving a service change under the same lock.
            $locked = $mutex->acquire($lockName, 10);
            if (!$locked) {
                $this->stdout("Billing cron already running.\n", Console::FG_YELLOW);
                return ExitCode::OK;
            }
            Yii::info('Automatic billing cron started.', __METHOD__);
            $billing = new AutoBillingService();
            $invoices = $billing->run();
            $problems = $billing->problems;
            $count = count($invoices);
            $this->stdout("Billing completed. Generated {$count} invoice(s).\n", Console::FG_GREEN);
            foreach ($invoices as $invoice) {
                $this->stdout(sprintf("%s | Client #%d | %.2f\n", $invoice->invoice_number, $invoice->user_id, $invoice->total_payable));
            }
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            $this->stderr('Billing failed: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            try {
                \common\services\admin\AdminAlertService::billingRunFailed($e->getMessage());
            } catch (\Throwable $alertError) {
                Yii::error($alertError, __METHOD__);
            }
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            if ($locked) {
                $mutex->release($lockName);
            }
        }
        if ($problems !== null) {
            $this->stderr("Billing finished with failures: {$problems}. Admins were alerted; they are retried on the next run.\n", Console::FG_RED);
        }

        // Not under the billing lock: a slow mail server must not block staff saving service changes.
        // Its own lock stops an overlapping cron sending the same invoices twice.
        $emailLock = 'miracle:invoice-email';
        $locked = false;
        try {
            $locked = $mutex->acquire($emailLock);
            if (!$locked) {
                $this->stdout("Invoice emails are already being sent; the rest go on the next run.\n", Console::FG_YELLOW);
                return $problems === null ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
            }
            // Email this run's invoices plus any recent ones whose earlier send failed.
            $failed = 0;
            $deliveryService = new InvoiceDeliveryService();
            foreach ($this->invoicesAwaitingEmail() as $invoice) {
                try {
                    // Staff may have cancelled, paid or resent it while earlier emails went out.
                    if (!$invoice->refresh() || $invoice->emailed_at !== null || !in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true)) {
                        continue;
                    }
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
            }
            return $failed > 0 || $problems !== null ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            $this->stderr('Billing failed: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            if ($locked) {
                $mutex->release($emailLock);
            }
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
            ->andWhere(['not in', 'user_id', \common\models\User::deletedIds()])
            ->andWhere(['>=', 'created_at', strtotime('-7 days')])
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    public function actionPaymentReminders(): int
    {
        return $this->runLocked('miracle:payment-reminders', 'Payment reminders', function (): string {
            $service = new InvoiceReminderService();
            return $this->reminderResult($service->sendDueReminders(), $service->failures);
        });
    }

    public function actionSendReminders(): int
    {
        return $this->runLocked('miracle:renewal-reminders', 'Renewal reminders', function (): string {
            $service = new ServiceRenewalReminderService();
            return $this->reminderResult($service->sendReminders(), $service->failures);
        });
    }

    /** Failures must reach the cron exit code: each is only logged where it happens, so the rest still go out. */
    private function reminderResult(int $sent, int $failures): string
    {
        if ($failures > 0) {
            throw new \RuntimeException(sprintf('Sent %d reminder(s); %d failed, see the app log.', $sent, $failures));
        }
        return sprintf('Sent %d reminder(s).', $sent);
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
                throw new \RuntimeException(sprintf('Recorded %d missed payment(s) and %d fee(s), found %d unrecorded refund(s); %d lookup(s) failed, see the app log.', $result['captured'], $result['fees'], $result['unrecordedRefunds'], $result['errors']));
            }
            return sprintf('Recorded %d missed payment(s) and %d fee(s), found %d unrecorded refund(s).', $result['captured'], $result['fees'], $result['unrecordedRefunds']);
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
        $locked = false;
        try {
            $locked = $mutex->acquire($lockName, 0);
            if (!$locked) {
                $this->stdout("{$label} already running.\n", Console::FG_YELLOW);
                return ExitCode::OK;
            }
            $this->stdout("{$label} completed. " . $job() . PHP_EOL);
            return ExitCode::OK;
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            $this->stderr("{$label} failed: " . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            if ($locked) {
                $mutex->release($lockName);
            }
        }
    }
}
