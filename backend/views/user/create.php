<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $model */

$this->title = 'Create System User Account';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Username and Password' => 'What the customer signs in to the website with. Give them to the customer; the password needs at least 8 characters.',
    'Account Status' => 'Leave it Active so the customer can sign in. Only an Active account gets the welcome email.',
    'Create User' => 'Saves the account and emails the customer that it exists, then opens the billing details form. Fill it in, or click Customer Will Complete This to leave it to them.',
    'Billing details' => 'Without them no renewal invoice can be made. The customer is reminded daily, and an account still incomplete after a month with nothing in use is suspended.',
];
?>
<div class="user-create">

    <hr>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
