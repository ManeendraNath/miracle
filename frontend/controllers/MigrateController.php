<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

/**
 * Site controller
 */
class MigrateController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['create', 'up', 'down'],
                'rules' => [
                    [
                        'actions' => ['create', 'up', 'down'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $fileName = Yii::$app->getRequest()->getQueryParam('name');
        ob_start();
        try {
            $output = Array();
            exec(__DIR__ . '/../../yii migrate/create ' . $fileName . ' --interactive=0', $output);
            echo implode("\n", $output);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
        return htmlentities(ob_get_clean(), ENT_QUOTES, Yii::$app->charset);
    }

    

    public function actionUp() {
        ob_start();
        try {
            $output = Array();
            exec(__DIR__ . '/../../yii migrate/up --interactive=0', $output);
            echo implode("\n", $output);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
        return htmlentities(ob_get_clean(), ENT_QUOTES, Yii::$app->charset);
    }

    public function actionDown() {
        ob_start();
        try {
            $output = Array();
            exec(__DIR__ . '/../../yii migrate/down --interactive=0', $output);
            echo implode("\n", $output);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
        return htmlentities(ob_get_clean(), ENT_QUOTES, Yii::$app->charset);
    }

}
