<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use common\models\Invoice;

/**
 * InvoiceController handles administrative client transaction rendering gates.
 */
class InvoiceController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['index', 'create', 'download', 'view'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Renders an individual dynamic invoice profile screen with automated tax math tracking.
     */
    public function actionView(string $number)
    {
        $model = Invoice::find()->where(['invoice_number' => $number])->with('invoiceItems')->one();
        
        if ($model === null) {
            throw new NotFoundHttpException('The specified billing invoice reference could not be located.');
        }

        // 📊 RUN HIGH-PRECISION REVENUE MATHEMATICS FORMULAS
        $subtotal = 0.00;
        foreach ($model->invoiceItems as $item) {
            $subtotal += (float) $item->total_price;
        }

        $discountedSubtotal = $subtotal - (float) $model->discount_amount;

        // Dynamic multi-tier Indian GST matrices extra calculations blocks
        $cgstAmount = $discountedSubtotal * ((float) $model->cgst_percent / 100);
        $sgstAmount = $discountedSubtotal * ((float) $model->sgst_percent / 100);
        $igstAmount = $discountedSubtotal * ((float) $model->igst_percent / 100);
        
        $grandTotal = $discountedSubtotal + $cgstAmount + $sgstAmount + $igstAmount;

        return $this->renderPartial('view', [
            'model' => $model,
            'subtotal' => $subtotal,
            'cgstAmount' => $cgstAmount,
            'sgstAmount' => $sgstAmount,
            'igstAmount' => $igstAmount,
            'grandTotal' => $grandTotal,
        ]);
    }
    
    public function actionDownload()
    {
        $this->layout = 'blank';
        return $this->render('invoice');
    }

}
