<?php

declare(strict_types=1);

use common\components\CustomYii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\BlogCategory[] $categories */
/** @var common\models\BlogCategory|null $current */

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$this->title = ($current !== null ? $current->name . ' | Blog' : 'Blog') . ' | ' . $siteName;
$this->params['bare'] = true;
$this->params['meta_description'] = $current !== null
    ? $current->name . ' articles from ' . $siteName . ': practical advice on building and running websites, online stores and web applications.'
    : 'Practical advice on building and running websites, online stores and web applications, from ' . $siteName . '.';
// Posts first: the page number is only checked against the page count once they are loaded.
$posts = $dataProvider->getModels();
$page = $dataProvider->getPagination() ? $dataProvider->getPagination()->getPage() + 1 : 1;
$this->params['canonical'] = Url::to(['/blog/index'] + ($current !== null ? ['category' => $current->slug] : []) + ($page > 1 ? ['page' => $page] : []), true);
$this->registerLinkTag(['rel' => 'alternate', 'type' => 'application/rss+xml', 'title' => $siteName . ' Blog', 'href' => Url::to(['/blog/feed'], true)]);
?>
<div class="blog-index">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <p class="mw-eyebrow mb-2">Blog</p>
            <h1 class="mw-h1 mb-3"><?= Html::encode($current->name ?? 'Notes on building for the web') ?></h1>
            <p class="mw-lead mb-0">Practical advice on websites, online stores and web applications: what they cost, how they are built and how to keep them running.</p>
        </div>
    </div>

    <section class="mw-section">
        <div class="container">
            <?php if ($categories): ?>
                <nav class="mw-chips mb-5" aria-label="Blog categories">
                    <?= Html::a('All posts', ['/blog/index'], ['class' => 'mw-chip' . ($current === null ? ' active' : ''), 'aria-current' => $current === null ? 'page' : null]) ?>
                    <?php foreach ($categories as $category): ?>
                        <?= Html::a(Html::encode($category->name), ['/blog/index', 'category' => $category->slug], ['class' => 'mw-chip' . ($current?->id === $category->id ? ' active' : ''), 'aria-current' => $current?->id === $category->id ? 'page' : null]) ?>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>

            <?php if (!$posts): ?>
                <div class="mw-panel text-center py-5">
                    <h2 class="h4 fw-bold">The first posts are on their way</h2>
                    <p class="mb-3">In the meantime, tell us what you want to build and we'll reply with the next steps.</p>
                    <?= Html::a('Discuss your project', ['/site/contact', 'subject' => 'Project enquiry'], ['class' => 'btn btn-primary']) ?>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-md-6 col-lg-4"><?= $this->render('_card', ['post' => $post]) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-center mt-5">
                    <?= LinkPager::widget(['pagination' => $dataProvider->pagination, 'options' => ['class' => 'pagination'], 'linkContainerOptions' => ['class' => 'page-item'], 'linkOptions' => ['class' => 'page-link'], 'disabledListItemSubTagOptions' => ['class' => 'page-link']]) ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
