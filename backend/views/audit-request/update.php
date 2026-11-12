<?php

declare(strict_types=1);

use yii\helpers\Html;

$requestLabel = $model->name ?: '#' . $model->id;

$this->title = 'Update Audit Request: ' . $requestLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Audit Requests',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $requestLabel,
    'url' => [
        'view',
        'id' => $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update client, technical and audit request information.';
$this->params['help'] = [
    'Status' => 'Move it along as you work: Pending, In Review, Contacted, Closed. For your own tracking; nothing is sent to the client.',
    'Website' => 'The client\'s site address. If you leave out https:// it is added for you.',
    'Framework and Hosting' => 'Free text: what the site is built with and where it is hosted.',
    'Update Audit Request' => 'Saves the changes and opens the request page. Cancel goes back to it without saving.',
];
?>

<div class="audit-request-update">


    <?=
    $this->render('_form', [
        'model' => $model,
    ])
    ?>

</div>