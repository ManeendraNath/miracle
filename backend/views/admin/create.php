<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Admin $model */

$this->title = 'Create Admin';
$this->params['breadcrumbs'][] = ['label' => 'Admins', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Username' => 'What they type to sign in.',
    'Role' => 'Start with the lowest role that does the job: Manager for day-to-day work, Admin for billing and payments, Superadmin only for people who manage staff and site settings.',
    'Account Status' => 'Only Active can sign in. Suspended blocks sign-in but keeps the account.',
    'Password' => 'At least 8 characters with a letter and a number. No email is sent: give them the username and password yourself.',
    'First sign-in' => 'They must set up two-factor sign-in under My Account before they can use the panel.',
];
?>
<div class="admin-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
