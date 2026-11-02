<?php

declare(strict_types=1);

namespace backend\controllers;

use common\services\report\AccountingReportService;
use Yii;
use yii\web\Response;

/** Accountant CSV exports (GST filing). */
final class ReportController extends BaseController
{
    public function actionIndex(?string $from = null, ?string $to = null): string
    {
        return $this->render('index', [
            'from' => $from ?? date('Y-m-d', strtotime('first day of last month')),
            'to' => $to ?? date('Y-m-d', strtotime('last day of last month')),
        ]);
    }

    public function actionDownload(string $type = '', string $from = '', string $to = ''): Response
    {
        if (!in_array($type, ['invoices', 'payments'], true)) {
            Yii::$app->session->setFlash('error', 'Unknown report.');
            return $this->redirect(['index', 'from' => $from, 'to' => $to]);
        }
        try {
            [$start, $end] = AccountingReportService::range($from, $to);
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(['index', 'from' => $from, 'to' => $to]);
        }

        $service = new AccountingReportService();
        $rows = $type === 'invoices' ? $service->invoiceRows($start, $end) : $service->paymentRows($start, $end);
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->setDownloadHeaders("{$type}_{$from}_{$to}.csv", 'text/csv; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-store');
        $response->stream = static fn(): \Generator => $service->csv($rows);
        return $response;
    }
}
