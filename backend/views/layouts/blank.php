<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use backend\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100" data-bs-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column justify-content-center align-items-center vh-100">
<?php $this->beginBody() ?>

<?php foreach (Yii::$app->session->getAllFlashes() as $type => $messages): ?>
    <?php foreach ((array) $messages as $message): ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : \yii\helpers\Html::encode($type) ?>"><?= \yii\helpers\Html::encode($message) ?></div>
    <?php endforeach; ?>
<?php endforeach; ?>
<?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
