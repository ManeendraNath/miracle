<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Admin $model */

$this->title = 'Update Admin: ' . $model->username;
$this->params['subtitle'] = 'Sign-in details, role and two-factor status.';
$this->params['breadcrumbs'][] = ['label' => 'Admins', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->username, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="admin-profile-form card card-outline card-danger p-4 shadow-sm">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

    <div class="border-top pt-3 d-flex align-items-center">
        <span class="mr-3">Two-factor sign-in:
            <?= $model->hasTwoFactor() ? '<span class="badge badge-success">On</span>' : '<span class="badge badge-secondary">Off</span>' ?>
        </span>
        <?php if ($model->hasTwoFactor()): ?>
            <?= Html::a('Turn off two-factor', ['reset-two-factor', 'id' => $model->id], [
                'class' => 'btn btn-sm btn-outline-danger',
                'data' => [
                    'method' => 'post',
                    'confirm' => 'Turn off two-factor sign-in for ' . $model->username . '? Use this only when they lost their authenticator app; they can log in with just a password until they set it up again.',
                ],
            ]) ?>
        <?php endif; ?>
        <?php if ((int) $model->id === (int) Yii::$app->user->id): ?>
            <?= Html::a('Backup codes and two-factor settings: My account', ['account'], ['class' => 'btn btn-sm btn-outline-primary ml-2']) ?>
        <?php endif; ?>
    </div>

</div>
