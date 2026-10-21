<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Transaction;
use common\services\billing\PaymentProcessingService;
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
        $secret = $_ENV['RAZORPAY_WEBHOOK_SECRET'] ?? '';
        if ($secret === '') {
            Yii::error('Razorpay webhook secret is not configured.', __METHOD__);
            throw new ServerErrorHttpException('Webhook is not configured.');
        }
        $body = Yii::$app->request->getRawBody();
        $signature = Yii::$app->request->headers->get('X-Razorpay-Signature');
        if (!is_string($signature) || $signature === '' || !$this->verifySignature($body, $signature, $secret)
        ) {
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
        if (!is_string($orderId) || $orderId === '' || !is_string($paymentId) || $paymentId === '' || !is_int($amountMinor) || $amountMinor <= 0 || !is_string($currency)
        ) {
            throw new BadRequestHttpException('Incomplete payment information.');
        }

        if (strtoupper($currency) !== 'INR') {
            throw new BadRequestHttpException('Unexpected payment currency.');
        }

        $dbTransaction = Yii::$app->db->beginTransaction();

        try {
            $transactionId = Yii::$app->db->createCommand('SELECT id FROM {{%transaction}} WHERE gateway_order_id = :orderId FOR UPDATE')
                    ->bindValue(':orderId', $orderId)
                    ->queryScalar();
            $transaction = $transactionId !== false ? Transaction::findOne((int) $transactionId) : null;
            if ($transaction === null) {
                throw new BadRequestHttpException('Payment transaction does not exist.');
            }
            if ($transaction->status === Transaction::STATUS_CAPTURED) {
                if ($transaction->gateway_payment_id !== $paymentId) {
                    throw new BadRequestHttpException('Captured payment reference does not match.');
                }
                $dbTransaction->commit();
                return ['status' => 'success'];
            }

            if ($transaction->status !== Transaction::STATUS_PENDING) {
                throw new BadRequestHttpException('Payment transaction is not pending.');
            }
            $expectedMinorAmount = (int) round(((float) $transaction->amount) * 100);

            if ($expectedMinorAmount !== $amountMinor) {
                throw new BadRequestHttpException('Payment amount does not match.');
            }

            if (!empty($transaction->gateway_payment_id) && $transaction->gateway_payment_id !== $paymentId) {
                throw new BadRequestHttpException('Payment reference does not match.');
            }
            $now = time();
            $transaction->gateway_payment_id = $paymentId;
            $transaction->status = Transaction::STATUS_CAPTURED;
            $transaction->paid_at = $now;
            $transaction->updated_at = $now;
            if (!$transaction->save()) {
                throw new \RuntimeException('Captured payment could not be saved.');
            }
            (new PaymentProcessingService())->transactionChanged($transaction, false);
            $dbTransaction->commit();
            try {
                (new PaymentConfirmationService())->send($transaction);
            } catch (\Throwable $e) {
                Yii::error(['message' => 'Razorpay payment was captured but confirmation email failed.', 'transactionId' => (int) $transaction->id, 'error' => $e->getMessage()], __METHOD__);
            }
            return ['status' => 'success'];
        } catch (BadRequestHttpException $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            Yii::warning(['message' => 'Razorpay webhook rejected.', 'orderId' => $orderId, 'paymentId' => $paymentId, 'error' => $e->getMessage()], __METHOD__);
            throw $e;
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            Yii::error(['message' => 'Razorpay webhook processing failed.', 'orderId' => $orderId, 'paymentId' => $paymentId, 'error' => $e->getMessage()], __METHOD__);

            throw new ServerErrorHttpException('Payment processing failed.', 0, $e);
        }
    }

    private function verifySignature(string $body, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $signature);
    }
}
