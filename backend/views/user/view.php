<?php

use backend\controllers\BaseController;
use common\models\User;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\User $model */
$this->title = $model->username;

$this->params['breadcrumbs'][] = [
    'label' => 'Users',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);

$statuses = User::getStatuses();
$statusLabel = $statuses[(int) $model->status] ?? 'Unknown';

switch ((int) $model->status) {
    case User::getStatusActive():
        $statusClass = 'bg-success';
        break;

    case User::getStatusInactive():
        $statusClass = 'bg-secondary';
        break;

    case User::getStatusSuspended():
        $statusClass = 'bg-warning text-dark';
        break;

    case User::getStatusDeleted():
        $statusClass = 'bg-danger';
        break;

    default:
        $statusClass = 'bg-secondary';
        break;
}
$this->params['subtitle'] = 'User account details';
$this->params['actions'] = [
    Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], ['class' => 'btn btn-light border']),
    Html::a('<i class="fas fa-edit me-1"></i> Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']),
    Html::a('<i class="fas fa-envelope me-1"></i> Email customer', ['email', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']),
];
if (\backend\controllers\BaseController::canAccess(Yii::$app->user->identity, 'customer-preference', 'index')) {
    $this->params['actions'][] = Html::a('<i class="fas fa-bell me-1"></i> Preferences', ['/customer-preference/update', 'userId' => $model->id], ['class' => 'btn btn-outline-secondary']);
}
if (BaseController::canAccess(Yii::$app->user->identity, 'user-login-history')) {
    $this->params['actions'][] = Html::a('<i class="fas fa-shield-alt me-1"></i> Login History', ['/user-login-history/index', 'UserLoginHistorySearch' => ['user_id' => $model->id]], ['class' => 'btn btn-outline-secondary']);
}
$this->params['help'] = [
    'Update' => BaseController::canAccess(Yii::$app->user->identity, 'user', 'credentials')
        ? 'Change the username, email, password or account status.'
        : 'Change the account status. Only an Admin can change the username, email or password.',
    'Email customer' => 'Write one email to this customer from ready-made templates. A copy goes to the billing address.',
];
if (BaseController::canAccess(Yii::$app->user->identity, 'customer-preference', 'index')) {
    $this->params['help']['Preferences'] = 'Turn this customer\'s renewal, payment and support emails on or off.';
}
if (BaseController::canAccess(Yii::$app->user->identity, 'user-login-history')) {
    $this->params['help']['Login History'] = 'Every time this customer signed in to the website, with IP address and browser.';
}
$this->params['help']['Account Status'] = 'Only Active customers can sign in. Suspended or Inactive customers are still billed and reminded for their active services; Deleted ones are not.';
?>

<div class="user-view">

    <div class="card border-0 shadow-sm">


        <div class="card-body p-4">

            <?= DetailView::widget([
                'model' => $model,
                'options' => [
                    'class' => 'table table-bordered align-middle mb-0',
                ],
                'attributes' => [
                    [
                        'attribute' => 'id',
                        'label' => 'User ID',
                    ],
                    [
                        'attribute' => 'username',
                        'label' => 'Username',
                    ],
                    [
                        'attribute' => 'email',
                        'label' => 'Email Address',
                        'format' => 'email',
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Account Status',
                        'format' => 'raw',
                        'value' => Html::tag('span', Html::encode($statusLabel), [
                            'class' => 'badge ' . $statusClass,
                        ]),
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Created At',
                        'format' => ['datetime', 'php:d M Y, h:i A'],
                    ],
                    [
                        'attribute' => 'updated_at',
                        'label' => 'Last Updated',
                        'format' => ['datetime', 'php:d M Y, h:i A'],
                    ],
                ],
            ]) ?>

        </div>

    </div>

</div>