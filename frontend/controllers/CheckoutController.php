<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Domains;
use common\models\Hosting;
use common\models\Maintenance;

class CheckoutController extends Controller
{
    /**
     * Creates a standard renewal invoice for an asset
     * @param string $type Type of service ('domain', 'hosting', 'maintenance')
     * @param int $id The ID of the asset record
     */
    public function actionRenew($type, $id)
    {
        if (Yii::$app->user->isGuest) {
            Yii::$app->session->setFlash('warning', 'Please login to proceed with renewal payments.');
            return $this->redirect(['site/login']);
        }

        $userId = Yii::$app->user->id;
        $asset = $this->findAssetModel($type, $id, $userId);

        // Begin DB Transaction database block for financial integrity safety
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            // 1. Establish Master Billing Invoice Frame
            $invoice = new Invoice();
            $invoice->user_id = $userId;
            $invoice->invoice_number = 'MWT-' . time() . '-' . rand(10, 99);
            $invoice->client_name = Yii::$app->user->identity->username;
            $invoice->status = 'Unpaid';
            $invoice->due_date = date('Y-m-d', strtotime('+3 days'));
            $invoice->created_at = time();
            $invoice->updated_at = time();

            // Establish pricing variables based on asset context definitions
            $description = '';
            $unitPrice = 0.00;

            if ($type === 'domain') {
                $description = "1 Year Extension Renewal Fee for Domain Asset URL: " . $asset->domain_url;
                $unitPrice = 600.00; // Base baseline default pricing tier rate
            } elseif ($type === 'hosting') {
                $description = "1 Year Business Infrastructure Web Hosting Renewal for Domain: " . $asset->primary_domain;
                $unitPrice = 2000.00;
            } elseif ($type === 'maintenance') {
                $description = "1 Year Annual Maintenance Contract Maintenance Cover for: " . $asset->website_url;
                $unitPrice = 5000.00;
            }

            // 2. Perform Financial Math Computations with Precision
            $invoice->subtotal_amount = $unitPrice;
            $invoice->discount_amount = 0.00; 
            
            // Standard Local Indian Intra-State Taxation Architecture Mapping (9% CGST + 9% SGST)
            $invoice->cgst_percent = 9.00;
            $invoice->sgst_percent = 9.00;
            $invoice->igst_percent = 0.00;

            $taxableAmount = $invoice->subtotal_amount - $invoice->discount_amount;
            $totalTax = $taxableAmount * (($invoice->cgst_percent + $invoice->sgst_percent) / 100);
            $invoice->total_payable = $taxableAmount + $totalTax;

            if (!$invoice->save()) {
                throw new \Exception('Failed to create billing master framework invoice.');
            }

            // 3. Append Dynamic Item Breakdowns Layer Linking
            $item = new InvoiceItem();
            $item->invoice_id = $invoice->id;
            $item->item_type = $type;
            $item->item_id = $id;
            $item->category = ucfirst($type);
            $item->description = $description;
            $item->quantity = 1;
            $item->unit_price = $unitPrice;
            $item->total_price = $unitPrice;

            if (!$item->save()) {
                throw new \Exception('Failed to append billing element entries item references.');
            }

            $dbTransaction->commit();
            
            // Redirect straight to processing channel form choice layout screen route
            return $this->redirect(['invoice/pay', 'id' => $invoice->id]);

        } catch (\Exception $e) {
            $dbTransaction->rollBack();
            Yii::$app->session->setFlash('error', 'Checkout processing failed: ' . $e->getMessage());
            return $this->redirect(['site/index']);
        }
    }

    /**
     * Resolves asset ownership securely
     */
    protected function findAssetModel($type, $id, $userId)
    {
        $model = null;
        if ($type === 'domain') {
            $model = Domains::findOne(['id' => $id, 'user_id' => $userId]);
        } elseif ($type === 'hosting') {
            $model = Hosting::findOne(['id' => $id, 'user_id' => $userId]);
        } elseif ($type === 'maintenance') {
            $model = Maintenance::findOne(['id' => $id, 'user_id' => $userId]);
        }

        if ($model !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Requested infrastructure asset entry doesn\'t exist or access restricted.');
    }
}
