<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingRun;
use DateTimeImmutable;
use Yii;

final class AutoBillingService
{

    public function __construct(
            private readonly BillingCollectorService $collector = new BillingCollectorService(),
            private readonly AutoInvoiceService $invoiceService = new AutoInvoiceService()
    )
    {
        
    }

    public function run(?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        $run = $this->createRun();
        try {
            $events = $this->collector->collect((int) $run->id, $today);
            Yii::info(
                    [
                        'message' => 'Renewable assets collected.',
                        'billingRunId' => (int) $run->id,
                        'billingRunNumber' => $run->run_number,
                        'billingEventCount' => count($events),
                    ],
                    __METHOD__
            );
            $invoices = $this->invoiceService->generatePendingInvoices((int) $run->id);
            $this->markCompleted($run);
            Yii::info(
                    [
                        'message' => 'Automatic billing run completed.',
                        'billingRunId' => (int) $run->id,
                        'billingRunNumber' => $run->run_number,
                        'invoiceCount' => count($invoices),
                    ],
                    __METHOD__
            );
            return $invoices;
        } catch (\Throwable $exception) {
            $this->markFailed($run, $exception);
            Yii::error(
                    [
                        'message' => 'Automatic billing run failed.',
                        'billingRunId' => (int) $run->id,
                        'billingRunNumber' => $run->run_number,
                        'exception' => $exception->getMessage(),
                    ],
                    __METHOD__
            );
            throw $exception;
        }
    }

    private function createRun(): BillingRun
    {
        $now = time();
        $run = new BillingRun();
        $run->run_number = $this->generateRunNumber();
        $run->status = BillingRun::STATUS_RUNNING;
        $run->started_at = $now;
        $run->completed_at = null;
        $run->failure_message = null;
        $run->created_at = $now;
        $run->updated_at = $now;
        if (!$run->save()) {
            throw new \RuntimeException('Unable to create billing run: ' . json_encode($run->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        return $run;
    }

    private function markCompleted(BillingRun $run): void
    {
        $now = time();
        $run->status = BillingRun::STATUS_COMPLETED;
        $run->completed_at = $now;
        $run->failure_message = null;
        $run->updated_at = $now;
        if (!$run->save()) {
            throw new \RuntimeException('Unable to mark billing run as completed: ' . json_encode($run->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    private function markFailed(BillingRun $run, \Throwable $exception): void
    {
        $now = time();
        $run->status = BillingRun::STATUS_FAILED;
        $run->completed_at = $now;
        $run->failure_message = mb_substr($exception->getMessage(), 0, 1000);
        $run->updated_at = $now;
        if (!$run->save()) {
            Yii::error(
                    [
                        'message' => 'Unable to persist failed billing run.',
                        'billingRunId' => (int) $run->id,
                        'billingRunNumber' => $run->run_number,
                        'errors' => $run->errors,
                        'exception' => $exception->getMessage(),
                    ],
                    __METHOD__
            );
        }
    }

    private function generateRunNumber(): string
    {
        return sprintf('BR-%s-%s', date('Ymd-His'), strtoupper(bin2hex(random_bytes(4))));
    }
}
