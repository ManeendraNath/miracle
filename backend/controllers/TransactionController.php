<?php

namespace backend\controllers;

use Yii;
use common\models\Transaction;
use common\models\TransactionSearch;
use yii\web\NotFoundHttpException;

/**
 * TransactionController implements the CRUD actions for Transaction model.
 */
class TransactionController extends BaseController
{

    /**
     * Lists all Transaction models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new TransactionSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Transaction model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
                    'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Transaction model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Transaction();

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
     * Updates an existing Transaction model.
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
     * Action: Approve a user-submitted manual UPI payment reference
     */
    public function actionApprove($id)
    {
        $transaction = $this->findModel($id);

        if ($transaction->status === 'Pending') {
            $transaction->status = 'Captured';
            $transaction->paid_at = time();
            $transaction->updated_at = time();

            if ($transaction->save(false)) {
                // Find parent bill statement invoice layout block instance
                $invoice = $transaction->invoice;
                if ($invoice !== null) {
                    $invoice->status = 'Paid';
                    $invoice->updated_at = time();
                    $invoice->save(false); // Triggers the automated renewal behavior system loop safely
                }
                Yii::$app->session->setFlash('success', 'UPI Transaction reference confirmed successfully. Account extended.');
            } else {
                Yii::$app->session->setFlash('error', 'Unable to complete verification check operations.');
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Action: Reject an invalid or fake manual transaction reference check string
     */
    public function actionReject($id)
    {
        $transaction = $this->findModel($id);

        if ($transaction->status === 'Pending') {
            $transaction->status = 'Failed';
            $transaction->updated_at = time();

            if ($transaction->save(false)) {
                Yii::$app->session->setFlash('warning', 'Transaction verification proof has been marked as failed/rejected.');
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Deletes an existing Transaction model.
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
     * Finds the Transaction model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Transaction the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Transaction::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
