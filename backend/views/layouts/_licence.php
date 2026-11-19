<?php

declare(strict_types=1);

use common\components\PortalOnly;
use common\services\LicenceService;
use yii\helpers\Html;

/** @var yii\web\View $this */

// A copy sold to another business: say so when its subscription is not paid up. Billing carries on regardless.
if (!PortalOnly::enabled() || !\backend\controllers\BaseController::canAccess(Yii::$app->user->identity, 'site-setting')) {
    return;
}
$licence = LicenceService::current();
$week = time() - 7 * 86400;
// Every check for a week has failed to reach our site: a passing outage says nothing.
$unreachable = isset($licence['failing_since']) && (int) $licence['failing_since'] < $week;
// The job has not run for a week (no answer and no failure).
$stale = $licence['status'] === LicenceService::ACTIVE && (int) $licence['checked_at'] < $week;
$message = match (true) {
    $unreachable => 'The licence could not be confirmed: this server cannot reach the licence server (' . (string) Yii::$app->params['licence']['server'] . '). Ask your hosting provider to allow outgoing connections to it.',
    $licence['status'] === LicenceService::EXPIRED => 'Your subscription has ended' . ($licence['until'] ? ' (paid up to ' . Yii::$app->formatter->asDate($licence['until'], 'medium') . ')' : '') . '. Billing keeps working, but updates and support have stopped. Renew it from your account with your supplier.',
    $licence['status'] === LicenceService::WRONG_SITE => 'This licence key is for a different web address. Ask your supplier to move it to this one.',
    $licence['status'] === LicenceService::UNKNOWN => 'This licence key is not recognised. Check LICENCE_KEY in the .env file.',
    $licence['status'] === LicenceService::NOT_CHECKED => (string) ($licence['reason'] ?? 'The licence has not been checked yet: the daily "Licence check" job (Cron Jobs & Logs) does it.'),
    $stale => 'The licence has not been confirmed for a week: the server may not reach the licence server. Check the "Licence check" job in Cron Jobs & Logs.',
    default => null,
};
if ($message === null) {
    return;
}
?>
<div class="alert alert-warning" role="status"><i class="fas fa-key mr-2" aria-hidden="true"></i><?= Html::encode($message) ?></div>
