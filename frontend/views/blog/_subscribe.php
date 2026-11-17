<?php

declare(strict_types=1);

use frontend\components\FormGuard;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var yii\base\Model|null $model */
/** @var string|null $id unique per page */
/** @var bool|null $main the form is the page's content */

$id ??= 'subscribe';
// An aside next to posts; the page's own content on /blog/subscribe.
$tag = ($main ?? false) ? 'section' : 'aside';
$value = isset($model) ? (string) $model->email : ''; // @phpstan-ignore property.notFound
$error = isset($model) ? $model->getFirstError('email') : null;
?>
<<?= $tag ?> class="mw-panel mw-subscribe" aria-labelledby="<?= $id ?>-title">
    <h2 id="<?= $id ?>-title" class="h5 fw-bold">Get new posts by email</h2>
    <p class="small mb-3">One email when a new post is published, nothing else. Every email has an unsubscribe link.</p>
    <form action="<?= Url::to(['/blog/subscribe']) ?>" method="post" novalidate>
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
        <?= FormGuard::field() ?>
        <label for="<?= $id ?>-email" class="visually-hidden">Email address</label>
        <div class="input-group">
            <input type="email" id="<?= $id ?>-email" name="BlogSubscriber[email]" value="<?= Html::encode($value) ?>" class="form-control<?= $error ? ' is-invalid' : '' ?>" placeholder="you@company.com" autocomplete="email" maxlength="255" required<?= $error ? ' aria-describedby="' . $id . '-error" aria-invalid="true"' : '' ?>>
            <button type="submit" class="btn btn-primary">Subscribe</button>
        </div>
        <?php if ($error): ?>
            <div id="<?= $id ?>-error" class="invalid-feedback d-block"><?= Html::encode($error) ?></div>
        <?php endif; ?>
    </form>
</<?= $tag ?>>
