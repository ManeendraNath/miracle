<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\models\SeoPage;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array<string, SeoPage> $saved route => what staff set */

$this->title = 'SEO';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Title and description' => 'What Google and Bing show for the page in their results. Empty = the page\'s built-in text.',
    'Blog posts' => 'Each post has its own search title and description on the post form.',
    'Verification codes' => 'Google and Bing site-ownership codes are under Site Settings → Search engines.',
];
$base = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');
$this->params['help']['Sitemap'] = 'Search engines find every page from ' . $base . '/sitemap.xml, which updates by itself: a page added to the website later appears there and in this list.';
?>
<div class="card card-outline card-primary shadow-sm">
    <?= AdminIndexHelper::pageHeader('The title and description search engines show for each page of the website.') ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th scope="col">Page</th><th scope="col">Title in search results</th><th scope="col">Description in search results</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
            <?php foreach (SeoPage::pages() as $route => $label): ?>
                <?php $page = $saved[$route] ?? null; ?>
                <tr>
                    <td class="fw-semibold"><?= Html::a(Html::encode($label) . '<span class="visually-hidden"> (opens in a new tab)</span>', $base . ($route === 'site/index' ? '/' : '/' . preg_replace('#^site/|/index$#', '', $route)), ['target' => '_blank', 'rel' => 'noopener']) ?></td>
                    <td><?= (string) $page?->title !== '' ? Html::encode($page->title) : '<span class="text-body-secondary">Built-in</span>' ?></td>
                    <td class="small"><?= (string) $page?->description !== '' ? Html::encode($page->description) : '<span class="text-body-secondary">Built-in</span>' ?></td>
                    <td class="text-end"><?= Html::a('Edit', ['update', 'route' => $route], ['class' => 'btn btn-sm btn-outline-primary', 'aria-label' => 'Edit ' . $label]) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
