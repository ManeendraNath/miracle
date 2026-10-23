<?php

use yii\helpers\Html;

$this->beginContent('@frontend/views/layouts/main.php');
?>

<div class="container py-4">

    <div class="row">

        <div class="col-lg-3 mb-4">

            <?= $this->render('_account_sidebar') ?>

        </div>

        <div class="col-lg-9">

            <?= $content ?>

        </div>

    </div>

</div>

<?php $this->endContent(); ?>