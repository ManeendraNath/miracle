<?php

namespace backend\controllers;

use backend\models\searches\AdminSearch;
use common\models\Admin;
use yii\web\NotFoundHttpException;
use Yii;

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
            $model->role = Admin::getRoleSuperAdmin();
            $model->status = Admin::getStatusActive();
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
            // Admin hashes a non-empty `password` in beforeValidate; blank keeps the current one.
            if (!$this->hasOtherActiveSuperadmin($model) && !$this->isActiveSuperadmin($model)) {
                Yii::$app->session->setFlash('error', 'At least one active Superadmin must remain.');
            } elseif ($model->save()) {
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
        $logPath = Yii::$app->params['cronLogPath'] ?: dirname(Yii::getAlias('@common')) . '/runtime/logs/billing-cron.log';
        $logLines = [];
        $metrics = ['success' => 0, 'warnings' => 0, 'errors' => 0];

        if (is_readable($logPath)) {
            // Only the tail: the log grows forever and file() would load all of it.
            $handle = fopen($logPath, 'rb');
            fseek($handle, max(0, filesize($logPath) - 65536));
            $slicedData = array_slice(explode("\n", (string) stream_get_contents($handle)), -150);
            fclose($handle);

            foreach ($slicedData as $line) {
                $lineText = trim($line);
                if (empty($lineText)) {
                    continue;
                }

                // Errors first: "completed with 2 failure(s)" is not a success.
                if (stripos($lineText, 'error') !== false || stripos($lineText, 'failed') !== false || stripos($lineText, 'failure') !== false) {
                    $metrics['errors']++;
                } elseif (stripos($lineText, 'Skipping') !== false || stripos($lineText, 'warning') !== false) {
                    $metrics['warnings']++;
                } elseif (stripos($lineText, 'Successfully dispatched') !== false || stripos($lineText, 'complete') !== false) {
                    $metrics['success']++;
                }

                $logLines[] = $lineText;
            }
        } else {
            $logLines[] = 'Cron log not found. Point the crontab output (>> ... 2>&1) at this file, or set CRON_LOG_PATH in .env.';
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
        $model = $this->findModel($id);

        if ((int) $model->id === (int) Yii::$app->user->id) {
            Yii::$app->session->setFlash('error', 'You cannot delete your own account.');
        } elseif (!$this->hasOtherActiveSuperadmin($model)) {
            Yii::$app->session->setFlash('error', 'The last active Superadmin cannot be deleted.');
        } else {
            $model->delete();
        }

        return $this->redirect(['index']);
    }

    private function isActiveSuperadmin(Admin $model): bool
    {
        return strtolower((string) $model->role) === 'superadmin'
            && (int) $model->status === Admin::getStatusActive();
    }

    /**
     * Whether some admin other than $model can still log in to the panel.
     */
    private function hasOtherActiveSuperadmin(Admin $model): bool
    {
        return Admin::find()
            ->where(['role' => Admin::getRoleSuperAdmin(), 'status' => Admin::getStatusActive()])
            ->andWhere(['<>', 'id', $model->id])
            ->exists();
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
