<?php

namespace backend\controllers;

use yii\filters\AccessControl;
class InvoiceController extends \yii\web\Controller
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
    
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    public function actionCreate()
    {
        return $this->render('create');
    }
    
    public function actionDownload()
    {
        $this->layout = 'blank';
        return $this->render('invoice');
    }
    
    public function actionview()
    {
        return $this->render('view');
    }

}
