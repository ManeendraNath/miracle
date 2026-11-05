<?php

declare(strict_types=1);

namespace backend\controllers\traits;

use Yii;

/**
 * Enable/disable instead of deleting rows: billing, reminders and history keep
 * their references. The using controller provides findModel() and statusToggle().
 */
trait ToggleStatusTrait
{
    /**
     * @return array{0: string, 1: mixed, 2: mixed} [attribute, enabled value, disabled value]
     */
    abstract protected function statusToggle(): array;

    public function actionToggleStatus(int $id)
    {
        $model = $this->findModel($id);
        [$attribute, $on, $off] = $this->statusToggle();
        $enable = (string) $model->$attribute !== (string) $on;
        $type = match (true) {
            $model instanceof \common\models\Domains => \common\models\InvoiceItem::TYPE_DOMAIN,
            $model instanceof \common\models\Hosting => \common\models\InvoiceItem::TYPE_HOSTING,
            $model instanceof \common\models\Maintenance => \common\models\InvoiceItem::TYPE_MAINTENANCE,
            default => null,
        };
        // Disabling releases renewals: not while a billing run may be invoicing them.
        $locked = !$enable && $type !== null;
        if ($locked && !$this->lockBillingRun()) {
            Yii::$app->session->setFlash('error', 'The billing run is in progress. Try again in a minute.');
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }
        $model->$attribute = $enable ? $on : $off;
        $model->updated_at = time();

        if ($model->save(false, [$attribute, 'updated_at'])) {
            Yii::$app->session->setFlash('success', $enable ? 'Record enabled.' : 'Record disabled.');
            // A disabled service is not renewed: release its renewal (see RenewalReleaseService).
            $warning = !$enable && $type !== null ? (new \common\services\billing\RenewalReleaseService())->releaseForDisabledService($type, (int) $model->id) : null;
            if ($warning !== null) {
                Yii::$app->session->setFlash('warning', $warning);
            }
        } else {
            Yii::$app->session->setFlash('error', 'Status could not be changed.');
        }

        if ($locked) {
            $this->unlockBillingRun();
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }
}
