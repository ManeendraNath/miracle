<?php

declare(strict_types=1);

namespace backend\controllers;

use common\services\payment\RazorpayService;
use Yii;

/**
 * Admin page to try Razorpay Checkout with test keys, without an invoice: creates a bare order,
 * opens the real checkout, then verifies the signature and shows what Razorpay recorded.
 * Writes nothing to the database. Refused with live keys.
 */
final class RazorpayTestController extends BaseController
{
    public function actionIndex(): string
    {
        $razorpay = new RazorpayService();
        $order = null;
        $amount = 1.00;
        if ($this->request->isPost) {
            $amount = round((float) $this->request->post('amount', 1), 2);
            try {
                $order = ['id' => $razorpay->createTestOrder((int) round($amount * 100)), 'amount' => (int) round($amount * 100)];
            } catch (\DomainException $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            } catch (\Throwable $e) {
                Yii::error($e, __METHOD__);
                Yii::$app->session->setFlash('error', 'Razorpay did not create the order: ' . $e->getMessage());
            }
        }

        return $this->render('index', [
            'ready' => $razorpay->isEnabled() && $razorpay->isTestMode(),
            'keyId' => $razorpay->getKeyId(),
            'amount' => $amount,
            'order' => $order,
        ]);
    }

    /** Checkout's handler posts here: check the signature, then ask Razorpay what it recorded. */
    public function actionVerify(): string
    {
        $razorpay = new RazorpayService();
        $orderId = (string) $this->request->post('razorpay_order_id', '');
        $paymentId = (string) $this->request->post('razorpay_payment_id', '');
        $signatureValid = $razorpay->isTestMode() && $orderId !== '' && $paymentId !== ''
            && $razorpay->verifyCheckoutSignature($orderId, $paymentId, (string) $this->request->post('razorpay_signature', ''));
        $payment = null;
        $error = null;
        if ($signatureValid) {
            try {
                $payment = $razorpay->fetchPayment($paymentId);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('verify', [
            'orderId' => $orderId,
            'paymentId' => $paymentId,
            'signatureValid' => $signatureValid,
            'payment' => $payment,
            'error' => $error,
        ]);
    }
}
