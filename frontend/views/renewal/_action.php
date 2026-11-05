<?php

declare(strict_types=1);

use yii\helpers\Html;

/**
 * What the customer can do about a service nearing expiry; see InvoicePaymentService::renewalState().
 *
 * @var yii\web\View $this
 * @var string $type
 * @var int $id
 * @var array{invoiceNumber: string|null, billable: bool, issuedFrom: string} $renewal
 */

if ($renewal['invoiceNumber'] !== null) {
    echo Html::a('Renew Now', ['/checkout/renew', 'type' => $type, 'id' => $id], ['class' => 'btn btn-sm btn-primary']);
} elseif (!$renewal['billable']) {
    echo Html::a('Contact Us', ['/support/create'], ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'This service has no automatic renewal invoice.']);
} elseif ($renewal['issuedFrom'] > date('Y-m-d')) {
    echo Html::tag('span', 'Invoice from ' . Yii::$app->formatter->asDate($renewal['issuedFrom'], 'php:d M Y'), ['class' => 'small text-body-secondary']);
} else {
    echo Html::tag('span', 'Invoice on its way', ['class' => 'small text-body-secondary']);
}
