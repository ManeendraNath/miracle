<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Testimonial;
use common\services\admin\AdminAlertService;
use Yii;
use yii\db\IntegrityException;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

/** A customer's own testimonial: one each, shown on the home page only after staff publish it. */
final class FeedbackController extends Controller
{
    public $layout = 'account';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
        ];
    }

    public function actionIndex(): Response|string
    {
        $userId = (int) Yii::$app->user->id;
        $existing = Testimonial::findOne(['user_id' => $userId]);
        if ($existing !== null) {
            return $this->render('index', ['submitted' => $existing]);
        }

        $model = new Testimonial(['scenario' => Testimonial::SCENARIO_CUSTOMER]);
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->user_id = $userId;
            $model->consent_at = time();
            try {
                $model->save(false);
            } catch (IntegrityException) {
                // A double submit: the first one is already saved.
                return $this->refresh();
            }
            try {
                AdminAlertService::testimonialSubmitted($model->client_name);
            } catch (\Throwable $e) {
                Yii::error(['message' => 'Testimonial admin alert failed.', 'testimonialId' => (int) $model->id, 'error' => $e->getMessage()], __METHOD__);
            }
            Yii::$app->session->setFlash('success', 'Thank you for your feedback.');
            return $this->refresh();
        }
        return $this->render('index', ['model' => $model]);
    }
}
