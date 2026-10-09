<?php

namespace backend\controllers;

use Yii;
use common\models\Invoice;
use common\models\InvoiceSearch;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;
use common\models\User;

/**
 * InvoiceController implements the CRUD actions for Invoice model.
 */
class InvoiceController extends BaseController
{

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

        // 🚀 RUN SECURITY ENFORCEMENT CHECK: Blocks views instantly if client status !== 10
        $model->checkClientAccess();
    
        // Run calculations or load related item matrices
        $subtotal = (float) $model->subtotal_amount;
        $discountedSubtotal = $subtotal - (float) $model->discount_amount;

        $cgstAmount = $discountedSubtotal * ((float) $model->cgst_percent / 100);
        $sgstAmount = $discountedSubtotal * ((float) $model->sgst_percent / 100);
        $igstAmount = $discountedSubtotal * ((float) $model->igst_percent / 100);

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
                if (($client = User::findOne($model->user_id)) !== null) {
                    $this->sendEmail($client, $model);
                }
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
        $usersList = ArrayHelper::map(User::find()->asArray()->all(), 'id', function ($user) {
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

        $usersList = ArrayHelper::map(User::find()->asArray()->all(), 'id', function ($user) {
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

    private function sendEmail($client, $model)
    {
        // Compile the financial variables matrix parameters
        $subtotal = (float) $model->subtotal_amount;
        $discountedSubtotal = $subtotal - (float) $model->discount_amount;
        $cgstAmount = $discountedSubtotal * ((float) $model->cgst_percent / 100);
        $sgstAmount = $discountedSubtotal * ((float) $model->sgst_percent / 100);
        $igstAmount = $discountedSubtotal * ((float) $model->igst_percent / 100);
        $grandTotal = $discountedSubtotal + $cgstAmount + $sgstAmount + $igstAmount;

        // 📁 Generate HTML Stream context using your polished printable view canvas
        $htmlContent = $this->renderPartial('view', [
            'model' => $model,
            'subtotal' => $subtotal,
            'cgstAmount' => $cgstAmount,
            'sgstAmount' => $sgstAmount,
            'igstAmount' => $igstAmount,
            'grandTotal' => $grandTotal,
        ]);
        // Construct and bundle the message package safely
        $message = Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['adminEmail'] ?? 'admin@miraclewebtechnologies.com' => 'Miracle Billing'])
                ->setTo($client->email)
                ->setSubject('Official Billing Statement Issued: #' . $model->invoice_number)
                ->setHtmlBody("
                        <h3>Hello " . \yii\helpers\Html::encode($model->client_name) . ",</h3>
                        <p>Please find attached your official billing statement invoice reference record #<strong>{$model->invoice_number}</strong> for your corporate service portfolio files.</p>
                        <p>Thank you for choosing <strong>Miracle Web Technologies</strong>.</p>
                    ");

        // 📎 ATTACH PDF GENERATION DATA STREAM NATIVELY
        // Attach the printable HTML content layout directly as a responsive invoice asset file
        $message->attachContent($htmlContent, [
            'fileName' => 'Invoice_' . $model->invoice_number . '.html',
            'contentType' => 'text/html',
        ]);

        $message->send();
    }
}
