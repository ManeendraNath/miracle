<?php

namespace backend\controllers;

use backend\models\searches\AdminSearch;
use common\components\LoginThrottle;
use common\components\Totp;
use common\models\Admin;
use yii\web\NotFoundHttpException;
use Yii;

/**
 * AdminController implements the CRUD actions for Admin model.
 */
class AdminController extends BaseController
{
    /** Session key of a two-factor secret shown for setup but not yet confirmed. */
    private const TOTP_SETUP_KEY = 'adminTotpSetup';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs']['actions']['reset-two-factor'] = ['POST'];
        return $behaviors;
    }

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
     * The signed-in staff member edits their own email and password (any role).
     * Role and status are not editable here.
     */
    public function actionAccount()
    {
        $model = $this->findModel(Yii::$app->user->id);
        $twoFactorStep = $this->request->post('two_factor');
        if ($this->request->isPost && $twoFactorStep !== null) {
            $this->handleTwoFactor($model, (string) $twoFactorStep, (string) $this->request->post('code', ''));
            return $this->refresh();
        }
        if ($this->request->isPost) {
            $posted = $this->request->post($model->formName(), []);
            $model->setAttributes(array_intersect_key($posted, array_flip(['email', 'password'])));
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Your account has been updated.');
                return $this->refresh();
            }
        }
        $model->password = '';
        $setupSecret = $model->hasTwoFactor() ? null : Yii::$app->session->get(self::TOTP_SETUP_KEY);
        return $this->render('account', [
                    'model' => $model,
                    'setupSecret' => $setupSecret,
                    'setupUri' => $setupSecret === null ? null : Totp::uri($setupSecret, $model->username, Yii::$app->name),
        ]);
    }

    /**
     * "My account" two-factor steps: setup (new secret, kept in the session only),
     * enable (first valid code saves it), cancel, disable (needs a current code).
     */
    private function handleTwoFactor(Admin $model, string $step, string $code): void
    {
        $session = Yii::$app->session;
        if ($step === 'setup' && !$model->hasTwoFactor()) {
            $session->set(self::TOTP_SETUP_KEY, Totp::generateSecret());
        } elseif ($step === 'cancel') {
            $session->remove(self::TOTP_SETUP_KEY);
        } elseif ($step === 'enable') {
            $secret = $session->get(self::TOTP_SETUP_KEY);
            if (is_string($secret) && !$model->hasTwoFactor() && $model->enableTwoFactor($secret, $code)) {
                $session->remove(self::TOTP_SETUP_KEY);
                // New auth key: keep this session signed in, drop the old remember-me cookie.
                Yii::$app->user->switchIdentity($model, 0);
                $session->setFlash('success', 'Two-factor sign-in is on. You will need a code from your app at every login.');
            } else {
                $session->setFlash('error', 'That code is not valid. Check the time on your phone and try again.');
            }
        } elseif ($step === 'disable' && $model->hasTwoFactor()) {
            if (LoginThrottle::isLocked('backend', $model->username)) {
                $session->setFlash('error', 'Too many failed attempts. Please try again in 15 minutes.');
            } elseif ($model->consumeTotpCode($code)) {
                $model->disableTwoFactor();
                $session->setFlash('success', 'Two-factor sign-in is off.');
            } else {
                LoginThrottle::fail('backend', $model->username);
                $session->setFlash('error', 'That code is not valid. Two-factor sign-in is still on.');
            }
        }
    }

    /**
     * Superadmin only (MIN_ROLE 'admin'): turns off another admin's two-factor when
     * they lost their phone. They can set it up again under My account.
     */
    public function actionResetTwoFactor($id)
    {
        $model = $this->findModel($id);
        $model->disableTwoFactor();
        Yii::info("Two-factor sign-in reset for admin #{$model->id} by admin #" . Yii::$app->user->id, 'admin-auth');
        Yii::$app->session->setFlash('success', 'Two-factor sign-in is off for ' . $model->username . '.');
        return $this->redirect(['update', 'id' => $model->id]);
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
