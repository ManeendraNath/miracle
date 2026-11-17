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
/** @var common\models\BlogTag|null $tag */
/** @var common\models\BlogPost|null $featured */
/** @var int|null $month first second of the month shown */
/** @var string $search */
/** @var array<string, int> $archive "2026-11" => posts that month, newest first */
/** @var common\models\BlogPost[] $popular */

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$heading = $current->name ?? match (true) {
    $tag !== null => 'Posts tagged “' . $tag->name . '”',
    $search !== '' => 'Results for “' . $search . '”',
    $month !== null => 'Posts from ' . date('F Y', $month),
    default => null,
};
if ($search !== '') {
    // Search results are for visitors, not for search engines.
    $this->params['robots'] = 'noindex, follow';
}
$this->title = ($heading !== null ? $heading . ' | Blog' : 'Blog') . ' | ' . $siteName;
$this->params['bare'] = true;
$this->params['meta_description'] = $heading !== null
    ? $heading . ': articles from ' . $siteName . ': practical advice on building and running websites, online stores and web applications.'
    : 'Practical advice on building and running websites, online stores and web applications, from ' . $siteName . '.';
// Posts first: the page number is only checked against the page count once they are loaded.
$posts = $dataProvider->getModels();
$page = $dataProvider->getPagination() ? $dataProvider->getPagination()->getPage() + 1 : 1;
$this->params['canonical'] = Url::to(['/blog/index'] + ($current !== null ? ['category' => $current->slug] : []) + ($tag !== null ? ['tag' => $tag->slug] : [])
    + ($month !== null ? ['year' => date('Y', $month), 'month' => date('m', $month)] : []) + ($search !== '' ? ['q' => $search] : []) + ($page > 1 ? ['page' => $page] : []), true);
$this->registerLinkTag(['rel' => 'alternate', 'type' => 'application/rss+xml', 'title' => $siteName . ' Blog', 'href' => Url::to(['/blog/feed'], true)]);
?>
<div class="blog-index">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <p class="mw-eyebrow mb-2">Blog</p>
            <h1 class="mw-h1 mb-3"><?= Html::encode($heading ?? 'Notes on building for the web') ?></h1>
            <p class="mw-lead mb-0">Practical advice on websites, online stores and web applications: what they cost, how they are built and how to keep them running.</p>
        </div>
    </div>

    <section class="mw-section">
        <div class="container">
            <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
                <form action="<?= Url::to(['/blog/index']) ?>" method="get" role="search" class="mw-blog-search flex-grow-1">
                    <label for="blog-q" class="visually-hidden">Search the blog</label>
                    <div class="input-group">
                        <input type="search" id="blog-q" name="q" value="<?= Html::encode($search) ?>" class="form-control" placeholder="Search the blog" maxlength="100">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search" aria-hidden="true"></i><span class="visually-hidden">Search</span></button>
                    </div>
                </form>
                <?php if (count($archive) > 1): ?>
                    <details class="mw-archive">
                        <summary class="btn btn-outline-secondary">Posts by month</summary>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($archive as $ym => $count): ?>
                                <?php $isCurrent = $month !== null && date('Y-m', $month) === $ym; ?>
                                <li><?= Html::a(date('F Y', (int) strtotime($ym . '-01')) . ' (' . (int) $count . ')', ['/blog/index', 'year' => substr($ym, 0, 4), 'month' => substr($ym, 5, 2)], ['aria-current' => $isCurrent ? 'page' : null, 'class' => $isCurrent ? 'fw-bold' : null]) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </div>
            <?php if ($categories): ?>
                <nav class="mw-chips mb-5" aria-label="Blog categories">
                    <?php $all = $heading === null; ?>
                    <?= Html::a('All posts', ['/blog/index'], ['class' => 'mw-chip' . ($all ? ' active' : ''), 'aria-current' => $all ? 'page' : null]) ?>
                    <?php foreach ($categories as $category): ?>
                        <?= Html::a(Html::encode($category->name), ['/blog/index', 'category' => $category->slug], ['class' => 'mw-chip' . ($current?->id === $category->id ? ' active' : ''), 'aria-current' => $current?->id === $category->id ? 'page' : null]) ?>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>

            <?php if ($featured !== null && $page === 1): ?>
                <div class="mb-5"><?= $this->render('_card', ['post' => $featured, 'featured' => true]) ?></div>
            <?php endif; ?>
            <?php if (count($popular) > 1 && $page === 1): ?>
                <aside class="mw-panel mb-5" aria-labelledby="most-read">
                    <h2 id="most-read" class="h5 fw-bold mb-3">Most read in the last 30 days</h2>
                    <ol class="mb-0">
                        <?php foreach ($popular as $item): ?>
                            <li class="mb-1"><?= Html::a(Html::encode($item->title), ['/blog/view', 'slug' => $item->slug]) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </aside>
            <?php endif; ?>
            <?php if (!$posts && $featured === null && $heading !== null): ?>
                <div class="mw-panel text-center py-5">
                    <h2 class="h4 fw-bold">Nothing found</h2>
                    <p class="mb-3"><?= $search !== '' ? 'No post mentions that yet. Try another word, or browse all posts.' : 'There are no posts here yet.' ?></p>
                    <?= Html::a('All posts', ['/blog/index'], ['class' => 'btn btn-primary']) ?>
                </div>
            <?php elseif (!$posts && $featured === null): ?>
                <div class="mw-panel text-center py-5">
                    <h2 class="h4 fw-bold">The first posts are on their way</h2>
                    <p class="mb-3">In the meantime, tell us what you want to build and we'll reply with the next steps.</p>
                    <?= Html::a('Discuss your project', ['/site/contact', 'subject' => 'Project enquiry'], ['class' => 'btn btn-primary']) ?>
                </div>
            <?php elseif ($posts): ?>
                <div class="row g-4">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-md-6 col-lg-4"><?= $this->render('_card', ['post' => $post]) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-center mt-5">
                    <?= LinkPager::widget(['pagination' => $dataProvider->pagination, 'options' => ['class' => 'pagination'], 'linkContainerOptions' => ['class' => 'page-item'], 'linkOptions' => ['class' => 'page-link'], 'disabledListItemSubTagOptions' => ['class' => 'page-link']]) ?>
                </div>
            <?php endif; ?>
            <div class="mt-5"><?= $this->render('_subscribe') ?></div>
        </div>
    </section>
</div>
