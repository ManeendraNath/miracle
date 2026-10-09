<?php

namespace backend\controllers;

use common\models\Admin;
use common\models\AdminSearch;
use yii\web\NotFoundHttpException;

/**
 * AdminController implements the CRUD actions for Admin model.
 */
class AdminController extends BaseController
{

    /**
     * Lists all Admin models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new AdminSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Admin model.
     * @param int $id System ID Reference
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
     * Creates a new Admin model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Admin();

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
     * Updates an existing Admin model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id System ID Reference
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post())) {
            $newPassword = $this->request->post('new_password_string');
            
            // Cryptographic Hashing Protocol: Only update if a new password string is supplied
            if (!empty($newPassword)) {
                $model->setPassword($newPassword);
                $model->generateAuthKey();
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Administrative security profile parameters updated perfectly.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Reads background execution ledger logging streams and tracks automated metrics.
     */
    public function actionCronLog()
    {
        $logPath = '/home/rpocncwk/miracle/console/runtime/logs/cron.log';
        $logLines = [];
        $metrics = ['success' => 0, 'warnings' => 0, 'errors' => 0];

        if (file_exists($logPath)) {
            // Read last 150 lines from your server's log file safely
            $fileData = file($logPath);
            $slicedData = array_slice($fileData, -150);
            
            foreach ($slicedData as $line) {
                $lineText = trim($line);
                if (empty($lineText)) continue;

                // Dynamically compile metrics parameters using word signature sweeps
                if (stripos($lineText, 'Successfully dispatched') !== false || stripos($lineText, 'complete') !== false) {
                    $metrics['success']++;
                } elseif (stripos($lineText, 'Skipping') !== false || stripos($lineText, 'warning') !== false) {
                    $metrics['warnings']++;
                } elseif (stripos($lineText, 'error') !== false || stripos($lineText, 'failed') !== false) {
                    $metrics['errors']++;
                }

                $logLines[] = $lineText;
            }
        } else {
            $logLines[] = "System tracking ledger log file is not initialized yet. Run your cPanel cron tasks to populate data paths.";
        }

        return $this->render('cron-log', [
            'logLines' => array_reverse($logLines), // Newest log activities appear at the top
            'metrics' => $metrics,
            'logPath' => $logPath
        ]);
    }
    
    /**
     * Deletes an existing Admin model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id System ID Reference
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Admin model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id System ID Reference
     * @return Admin the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Admin::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
