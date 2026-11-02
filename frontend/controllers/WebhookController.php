<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\services\payment\RazorpayService;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

final class WebhookController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionRazorpay(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $razorpay = new RazorpayService();
        $body = Yii::$app->request->getRawBody();
        $signature = (string) Yii::$app->request->headers->get('X-Razorpay-Signature', '');
        try {
            $valid = $razorpay->verifyWebhookSignature($body, $signature);
        } catch (\RuntimeException $e) {
            Yii::error($e->getMessage(), __METHOD__);
            throw new ServerErrorHttpException('Webhook is not configured.');
        }
        if (!$valid) {
            Yii::warning('Rejected Razorpay webhook with invalid signature.', __METHOD__);
            throw new BadRequestHttpException('Invalid webhook signature.');
        }

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BadRequestHttpException('Invalid webhook payload.', 0, $e);
        }

        if (($payload['event'] ?? null) !== 'payment.captured') {
            return ['status' => 'ignored'];
        }

        $payment = $payload['payload']['payment']['entity'] ?? null;
        if (!is_array($payment)) {
            throw new BadRequestHttpException('Payment information is missing.');
        }
        $orderId = $payment['order_id'] ?? null;
        $paymentId = $payment['id'] ?? null;
        $amountMinor = $payment['amount'] ?? null;
        $currency = $payment['currency'] ?? null;
        if (
            !is_string($orderId) || $orderId === '' || !is_string($paymentId) || $paymentId === '' || !is_int($amountMinor) || $amountMinor <= 0 || !is_string($currency)
        ) {
            throw new BadRequestHttpException('Incomplete payment information.');
        }
        if (strtoupper($currency) !== 'INR') {
            throw new BadRequestHttpException('Unexpected payment currency.');
        }

        try {
            $razorpay->capture($orderId, $paymentId, $amountMinor);
            return ['status' => 'success'];
        } catch (\DomainException $e) {
            Yii::warning(['message' => 'Razorpay webhook rejected.', 'orderId' => $orderId, 'paymentId' => $paymentId, 'error' => $e->getMessage()], __METHOD__);
            throw new BadRequestHttpException($e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Razorpay webhook processing failed.', 'orderId' => $orderId, 'paymentId' => $paymentId, 'error' => $e->getMessage()], __METHOD__);
            throw new ServerErrorHttpException('Payment processing failed.', 0, $e);
        }
    }
}
