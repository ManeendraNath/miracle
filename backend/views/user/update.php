<?php

declare(strict_types=1);

use yii\helpers\Html;

$userLabel = $model->username ?: '#' . $model->id;

$this->title = 'Update User: ' . $userLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Users',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $userLabel,
    'url' => [
        'view',
        'id' => $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update customer account information and account status.';
$this->params['help'] = [
    'Username, Email and New Password' => \backend\controllers\BaseController::canAccess(Yii::$app->user->identity, 'user', 'credentials')
        ? 'Leave New Password blank to keep the current one. A new password or email cancels any password reset link the customer asked for.'
        : 'Only an Admin can change these, so they are locked here.',
    'Account Status' => 'Active can sign in. Suspended blocks sign-in. Inactive means waiting for the customer to confirm their email. Deleted stops renewals and reminders, but their open invoices stay in Outstanding until cancelled or written off.',
    'Setting a Suspended or Deleted account Active' => 'Gives the customer another month to complete their billing details before it can be suspended automatically.',
    'After saving' => 'If the customer is not Active but still has active services or open invoices, a warning tells you how many, so you can stop them if needed.',
];
?>

<div class="user-update">


    <?=
    $this->render('_form', [
        'model' => $model,
    ])
    ?>

</div>