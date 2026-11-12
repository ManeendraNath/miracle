<?php

declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Audit Request';

$this->params['breadcrumbs'][] = [
    'label' => 'Audit Requests',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Record a client website audit request and its technical details.';
$this->params['help'] = [
    'When to use' => 'For a request that came in by phone, email or in person. Requests from the website form are added automatically.',
    'Website' => 'The client\'s site address. If you leave out https:// it is added for you.',
    'Framework and Hosting' => 'Free text, as the client told you: what the site is built with (e.g. WordPress) and where it is hosted (e.g. VPS).',
    'Status' => 'Pending, In Review, Contacted or Closed. Left empty, it saves as Pending. Nothing is sent to the client.',
    'Save Audit Request' => 'Saves it and opens the request page. Cancel goes back to the list without saving.',
];
?>

<div class="audit-request-create">


    <?= $this->render('_form', [
                'model' => $model,
    ]) ?>

</div>