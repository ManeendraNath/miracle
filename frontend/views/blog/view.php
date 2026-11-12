<?php

declare(strict_types=1);

use common\components\CustomYii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\BlogPost $post */
/** @var common\models\BlogPost[] $related */

$siteName = CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies';
$url = Url::to(['/blog/view', 'slug' => $post->slug], true);
$image = $post->cover_image ? Url::to('@web/' . $post->cover_image, true) : null;
$this->title = ($post->seo_title ?: $post->title) . ' | ' . $siteName;
$this->params['bare'] = true;
$this->params['meta_description'] = $post->summary;
$this->params['canonical'] = $url;
$this->params['og_type'] = 'article';
if ($image !== null) {
    $this->params['og_image'] = $image;
}
$this->registerMetaTag(['property' => 'article:published_time', 'content' => date(DATE_ATOM, (int) $post->published_at)]);
$this->registerMetaTag(['property' => 'article:modified_time', 'content' => date(DATE_ATOM, (int) $post->updated_at)]);

// Article details for Google (structured data). A data block, not a script that runs.
$schema = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post->title,
    'description' => $post->summary,
    'image' => $image,
    'datePublished' => date(DATE_ATOM, (int) $post->published_at),
    'dateModified' => date(DATE_ATOM, (int) $post->updated_at),
    'mainEntityOfPage' => $url,
    'author' => ['@type' => 'Organization', 'name' => $siteName, 'url' => Url::home(true)],
    'publisher' => ['@type' => 'Organization', 'name' => $siteName, 'logo' => ['@type' => 'ImageObject', 'url' => Url::to('@web/images/logo-full.svg', true)]],
]);
?>
<article class="blog-view">
    <script type="application/ld+json"><?= Json::htmlEncode($schema) ?></script>
    <div class="mw-glow">
        <div class="container mw-page-head">
            <nav aria-label="breadcrumb" class="mb-3">
                <?= Html::a('Blog', ['/blog/index'], ['class' => 'mw-crumb']) ?>
                <?php if ($post->category): ?>
                    <span class="mx-2 opacity-50">/</span><?= Html::a(Html::encode($post->category->name), ['/blog/index', 'category' => $post->category->slug], ['class' => 'mw-crumb']) ?>
                <?php endif; ?>
            </nav>
            <h1 class="mw-h1 mb-3"><?= Html::encode($post->title) ?></h1>
            <p class="mw-lead mb-3"><?= Html::encode($post->summary) ?></p>
            <p class="small mb-0"><?= Yii::$app->formatter->asDate($post->published_at, 'php:d M Y') ?> · <?= $post->readingMinutes() ?> min read</p>
        </div>
    </div>

    <div class="container py-5">
        <div class="mw-article mx-auto">
            <?php if ($image !== null): ?>
                <?= Html::img($image, ['alt' => '', 'class' => 'mw-article-cover mb-4']) ?>
            <?php endif; ?>
            <div class="mw-article-body">
                <?= $post->body /* cleaned to article markup when saved (BlogPost::cleanBody) */ ?>
            </div>

            <aside class="mw-panel mt-5">
                <h2 class="h4 fw-bold">Planning something similar?</h2>
                <p>Tell us what you want to build or improve. You get a written quote before any work starts.</p>
                <?= Html::a('Discuss your project', ['/site/contact', 'subject' => 'Project enquiry'], ['class' => 'btn btn-primary']) ?>
            </aside>
        </div>
    </div>

    <?php if ($related): ?>
        <section class="mw-section mw-section-soft">
            <div class="container">
                <h2 class="mw-h2 mb-4">More from the blog</h2>
                <div class="row g-4">
                    <?php foreach ($related as $item): ?>
                        <div class="col-md-6 col-lg-4"><?= $this->render('_card', ['post' => $item]) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</article>
