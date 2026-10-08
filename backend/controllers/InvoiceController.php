<?php

namespace backend\controllers;

use common\models\Invoice;
use common\models\InvoiceSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * InvoiceController implements the CRUD actions for Invoice model.
 */
class InvoiceController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
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
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Invoice models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new InvoiceSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Renders an individual dynamic invoice profile screen with automated tax math tracking.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
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

    /**
     * Creates a new Invoice model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Invoice();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Invoice model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Invoice model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Invoice model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Invoice the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Invoice::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
