<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\helpers\Html;

$this->render('_head');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-bs-theme="light">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link rel="icon" href="<?= Yii::$app->request->baseUrl ?>/favicon.ico" sizes="any" />
    <link rel="icon" href="<?= Yii::$app->request->baseUrl ?>/images/logo.svg" type="image/svg+xml" />
</head>
<body class="d-flex flex-column min-vh-100">
<?php $this->beginBody() ?>

<a class="skip-link" href="#main">Skip to main content</a>
<?= $this->render('_header') ?>

<main id="main" class="flex-grow-1" role="main">
    <?php if (!empty($this->params['bare'])): ?>
        <div class="mw-alerts"><div class="container"><?= Alert::widget() ?></div></div>
        <?= $content ?>
    <?php else: ?>
        <div class="container">
            <?php if (!empty($this->params['breadcrumbs'])): ?>
                <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
            <?php endif ?>
            <?= Alert::widget() ?>
            <?= $content ?>
        </div>
    <?php endif ?>
</main>

<?= $this->render('_footer') ?>

<button type="button" class="mw-to-top" aria-label="Back to top" hidden><i class="fas fa-arrow-up" aria-hidden="true"></i></button>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
