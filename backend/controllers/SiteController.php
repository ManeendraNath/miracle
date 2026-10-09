<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\LoginForm;
use Yii;
use yii\web\ErrorAction;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends BaseController
{

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        return $this->render('index');
    }

    public function actionDemo()
{
    // Re-enable the layout wrapper so Yii's Asset bundle injects everything perfectly
    $this->layout = 'main';
    return $this->render('demo');
}

}
