<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\components\RateLimit;
use common\services\LicenceService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * POST /licence/check (key, site): an installed copy of our software asks once a day whether its licence is paid up.
 * POST keeps the key out of web server logs; there is no session or form, so no CSRF token.
 */
final class LicenceController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return ['verbs' => ['class' => VerbFilter::class, 'actions' => ['check' => ['POST']]]];
    }

    public function actionCheck(): Response
    {
        Yii::$app->response->headers->set('X-Robots-Tag', 'noindex');
        $key = Yii::$app->request->post('key', '');
        $site = Yii::$app->request->post('site', '');

        // An address that has sent 30 unknown keys this hour is refused for the rest of it, before any lookup. Real
        // copies send a known key once a day, so even many on one shared server never get there; a refused copy
        // keeps its last answer (LicenceService::check) and asks again tomorrow.
        $guesses = 'licence-guesses:' . Yii::$app->request->userIP;
        if (RateLimit::over($guesses, 30)) {
            Yii::$app->response->statusCode = 429;

            return $this->asJson(LicenceService::unknown());
        }
        $answer = !is_string($key) || !is_string($site) || mb_strlen($key) > 64 || mb_strlen($site) > 255 ? LicenceService::unknown() : LicenceService::status($key, $site);
        if ($answer['status'] === LicenceService::UNKNOWN) {
            RateLimit::hit($guesses, 30);
        }

        return $this->asJson($answer);
    }
}
