<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use common\models\Invoice;
use common\models\Transaction;

class WebhookController extends Controller
{

    // Disable CSRF token mapping matching blocks for external gateway API webhook triggers
    public $enableCsrfValidation = false;

    /**
     * Automated API callback capture junction endpoint for Razorpay payment triggers
     * URL path route: https://yourdomain.com
     */
    public function actionRazorpay()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $secret = 'YOUR_RAZORPAY_WEBHOOK_SECRET_KEY_TOKEN'; // Set this matching your Razorpay settings profile dashboard
        $postData = file_get_contents('php://input');
        $signature = Yii::$app->request->headers->get('X-Razorpay-Signature');

        // 1. Authenticate Hook Request Validity Security Signature Checklist Verification
        if (empty($signature) || !$this->verifySignature($postData, $signature, $secret)) {
            Yii::warning('Rejected fraud webhook event payload injection attempt on signatures.', 'webhook');
            throw new BadRequestHttpException('Invalid execution signatures payload mapping keys.');
        }

        $payload = json_decode($postData, true);
        if (isset($payload['event']) && $payload['event'] === 'payment.captured') {

            $paymentEntity = $payload['payload']['payment']['entity'];
            $orderId = $paymentEntity['order_id']; // Matches Razorpay Order ID reference token
            $paymentId = $paymentEntity['id'];     // Capture unique transaction code
            // 2. Identify corresponding internal ledger transaction block row frame instance
            $transaction = Transaction::findOne(['gateway_order_id' => $orderId]);

            if ($transaction !== null && $transaction->status === 'Pending') {

                // Secure atomic execution data verification tracking metrics directly
                $transaction->gateway_payment_id = $paymentId;
                $transaction->status = 'Captured';
                $transaction->paid_at = time();
                $transaction->updated_at = time();
                $transaction->raw_payload = $postData;

                if ($transaction->save(false)) {
                    // Update parent master billing invoice target record
                    $invoice = $transaction->invoice;
                    if ($invoice !== null) {
                        $invoice->status = 'Paid';
                        $invoice->updated_at = time();
                        $invoice->save(false); // Triggers the custom automated lifecycle extension behavior system
                    }

                    return ['status' => 'success', 'message' => 'Local database state re-aligned to captured transaction state.'];
                }
            }
        }

        return ['status' => 'ignored', 'message' => 'Event type callback execution signature validated but template skipped.'];
    }

    /**
     * Helper verification function computing native HMAC SHA256 hashes directly
     */
    private function verifySignature($body, $signature, $secret)
    {
        $expectedSignature = hash_hmac('sha256', $body, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}
