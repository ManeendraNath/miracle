<?php

declare(strict_types=1);

use common\components\CustomYii;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $title */
/** @var string $text */

$this->title = $title . ' | ' . (CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies');
$this->params['robots'] = 'noindex, nofollow';
?>
<section class="mw-section">
    <div class="container" style="max-width: 40rem">
        <h1 class="mw-h1 mb-3"><?= Html::encode($title) ?></h1>
        <p class="mw-lead"><?= Html::encode($text) ?></p>
        <p class="mt-4"><?= Html::a('Go to the blog', ['/blog/index'], ['class' => 'btn btn-primary']) ?></p>
    </div>
</section>
