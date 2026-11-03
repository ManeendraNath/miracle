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
        $model->$attribute = $enable ? $on : $off;
        $model->updated_at = time();

        if ($model->save(false, [$attribute, 'updated_at'])) {
            Yii::$app->session->setFlash('success', $enable ? 'Record enabled.' : 'Record disabled.');
        } else {
            Yii::$app->session->setFlash('error', 'Status could not be changed.');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }
}
