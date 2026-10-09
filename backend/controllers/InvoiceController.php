<?php

namespace backend\controllers;

use Yii;
use common\models\Invoice;
use common\models\InvoiceSearch;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;
use common\models\User;

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
                            'actions' => ['index', 'create', 'update', 'delete', 'download', 'view'],
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
     * Renders an individual dynamic invoice profile screen.
     */
    public function actionView(string $number)
    {
        // Check if the parameter passed is an ID instead of an absolute invoice string number
        if (is_numeric($number)) {
            $model = Invoice::find()->where(['id' => $number])->one();
        } else {
            $model = Invoice::find()->where(['invoice_number' => $number])->one();
        }
        
        if ($model === null) {
            throw new NotFoundHttpException('The specified billing invoice reference could not be located.');
        }

        // Run calculations or load related item matrices
        $subtotal = (float)$model->subtotal_amount;
        $discountedSubtotal = $subtotal - (float)$model->discount_amount;

        $cgstAmount = $discountedSubtotal * ((float)$model->cgst_percent / 100);
        $sgstAmount = $discountedSubtotal * ((float)$model->sgst_percent / 100);
        $igstAmount = $discountedSubtotal * ((float)$model->igst_percent / 100);
        
        $grandTotal = $discountedSubtotal + $cgstAmount + $sgstAmount + $igstAmount;

        return $this->render('view', [
            'model' => $model,
            'subtotal' => $subtotal,
            'cgstAmount' => $cgstAmount,
            'sgstAmount' => $sgstAmount,
            'igstAmount' => $igstAmount,
            'grandTotal' => $grandTotal,
        ]);
    }

    /**
     * Creates a new Invoice model.
     */
    public function actionCreate()
    {
        $model = new Invoice();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                // Redirect cleanly using the newly generated alphanumeric invoice field parameters
                return $this->redirect(['view', 'number' => $model->invoice_number]);
            }
        } else {
            $model->loadDefaultValues();
            // 🏷️ AUTO-GENERATE CODES: Set unique sequential default invoice tags
            $model->invoice_number = 'INV-' . date('YmdHis');
            $model->cgst_percent = 9.00; // Standard default Indian GST templates presets
            $model->sgst_percent = 9.00;
            $model->igst_percent = 0.00;
        }

        // 👥 DYNAMIC DROPDOWN MATRIX DATA: Map ID fields to user email/username profiles
        $usersList = ArrayHelper::map(User::find()->asArray()->all(), 'id', function($user) {
            return $user['username'] . ' (' . $user['email'] . ')';
        });

        return $this->render('create', [
            'model' => $model,
            'usersList' => $usersList,
        ]);
    }

    /**
     * Updates an existing Invoice model.
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'number' => $model->invoice_number]);
        }

        $usersList = ArrayHelper::map(User::find()->asArray()->all(), 'id', function($user) {
            return $user['username'] . ' (' . $user['email'] . ')';
        });

        return $this->render('update', [
            'model' => $model,
            'usersList' => $usersList,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Invoice::findOne(['id' => $id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
