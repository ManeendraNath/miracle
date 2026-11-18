<?php

declare(strict_types=1);

use common\components\CustomYii;
use frontend\components\FormGuard;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\BlogPost $post */
/** @var common\models\BlogPost[] $related */
/** @var common\models\BlogPost|null $older */
/** @var common\models\BlogPost|null $newer */
/** @var common\models\BlogComment[] $comments */
/** @var common\models\BlogComment $comment */

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
    if ((string) $post->cover_alt !== '') {
        $this->registerMetaTag(['property' => 'og:image:alt', 'content' => $post->cover_alt]);
    }
}
if ($post->noindex) {
    $this->params['robots'] = 'noindex, follow';
}
$authorName = $post->author?->authorName();
$content = $post->rendered();
$updatedAt = $post->contentUpdatedAt();
$frontendBase = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');
// Code blocks get colours from highlight.js (pinned, with integrity hashes), only on posts that have one.
if (str_contains($content['html'], '<pre')) {
    $this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github-dark.min.css', ['integrity' => 'sha384-wH75j6z1lH97ZOpMOInqhgKzFkAInZPPSPlZpYKYTOqsaizPvhQZmAtLcPKXpLyH', 'crossorigin' => 'anonymous']);
    $this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js', ['integrity' => 'sha384-RH2xi4eIQ/gjtbs9fUXM68sLSi99C7ZWBRX1vDrVv6GQXRibxXLbwO2NGZB74MbU', 'crossorigin' => 'anonymous', 'position' => $this::POS_END]);
    $this->registerJs("document.querySelectorAll('.mw-article-body pre').forEach((block) => { if (window.hljs) { hljs.highlightElement(block); } });");
}
$this->registerJs(<<<'JS'
    document.querySelectorAll('[data-copy-link]').forEach((button) => button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.copyLink);
            button.querySelector('span').textContent = 'Link copied';
        } catch (e) {
            window.prompt('Copy this link:', button.dataset.copyLink);
        }
    }));
    JS);
$this->registerMetaTag(['property' => 'article:published_time', 'content' => date(DATE_ATOM, (int) $post->published_at)]);
$this->registerMetaTag(['property' => 'article:modified_time', 'content' => date(DATE_ATOM, (int) ($updatedAt ?? $post->published_at))]);

// Article details for Google (structured data). A data block, not a script that runs.
$crumbs = array_values(array_filter([
    ['Blog', Url::to(['/blog/index'], true)],
    $post->category ? [$post->category->name, Url::to(['/blog/index', 'category' => $post->category->slug], true)] : null,
    [$post->title, Url::to(['/blog/view', 'slug' => $post->slug], true)],
]));
$schema = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post->title,
    'description' => $post->summary,
    'image' => $image,
    'datePublished' => date(DATE_ATOM, (int) $post->published_at),
    'dateModified' => date(DATE_ATOM, $updatedAt ?? (int) $post->published_at),
    'mainEntityOfPage' => $url,
    'author' => $authorName !== null ? ['@type' => 'Person', 'name' => $authorName] : ['@type' => 'Organization', 'name' => $siteName, 'url' => Url::home(true)],
    'keywords' => implode(', ', array_map(static fn($tag): string => $tag->name, $post->tags)) ?: null,
    'publisher' => ['@type' => 'Organization', 'name' => $siteName, 'logo' => ['@type' => 'ImageObject', 'url' => Url::to('@web/images/logo-full.svg', true)]],
]);
?>
<article class="blog-view">
    <script type="application/ld+json"><?= Json::htmlEncode($schema) ?></script>
    <?php // Blog › Category › Post, shown by search engines in place of the bare address. ?>
    <script type="application/ld+json"><?= Json::htmlEncode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(static fn(int $i, array $crumb): array => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb[0], 'item' => $crumb[1]], array_keys($crumbs), $crumbs),
    ]) ?></script>
    <?php if ($content['faq'] !== []): ?>
        <script type="application/ld+json"><?= Json::htmlEncode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn(array $item): array => ['@type' => 'Question', 'name' => $item['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']]], $content['faq']),
        ]) ?></script>
    <?php endif; ?>
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
            <p class="small mb-0"><?= $authorName !== null ? 'By ' . Html::encode($authorName) . ' · ' : '' ?><?= Yii::$app->formatter->asDate($post->published_at, 'php:d M Y') ?><?= $updatedAt !== null ? ' · Updated ' . Yii::$app->formatter->asDate($updatedAt, 'php:d M Y') : '' ?> · <?= $post->readingMinutes() ?> min read</p>
        </div>
    </div>

    <div class="container py-5">
        <div class="mw-article mx-auto">
            <?php if ($image !== null): ?>
                <?= Html::img($image, ['alt' => (string) $post->cover_alt !== '' ? (string) $post->cover_alt : $post->title, 'class' => 'mw-article-cover mb-4']) ?>
            <?php endif; ?>
            <?php if (count($content['toc']) >= 3): ?>
                <nav class="mw-toc mb-4" aria-labelledby="toc-title">
                    <h2 id="toc-title" class="h6 fw-bold text-uppercase mb-2">In this post</h2>
                    <ol class="mb-0">
                        <?php foreach ($content['toc'] as $entry): ?>
                            <li class="<?= $entry['level'] === 3 ? 'ms-3' : '' ?>"><a href="#<?= Html::encode($entry['id']) ?>"><?= Html::encode($entry['text']) ?></a></li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
            <div class="mw-article-body">
                <?= $content['html'] /* cleaned to article markup when saved (BlogPost::cleanBody); headings given ids */ ?>
            </div>

            <div class="mw-share d-flex flex-wrap align-items-center gap-2 mt-5" role="group" aria-label="Share this post">
                <span class="small fw-semibold me-1">Share</span>
                <?php $shareText = rawurlencode($post->title);
                $shareUrl = rawurlencode($url); ?>
                <?= Html::a('<i class="fab fa-linkedin-in" aria-hidden="true"></i><span class="visually-hidden">LinkedIn</span>', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $shareUrl, ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener', 'title' => 'Share on LinkedIn']) ?>
                <?= Html::a('<i class="fab fa-twitter" aria-hidden="true"></i><span class="visually-hidden">X</span>', 'https://x.com/intent/post?text=' . $shareText . '&url=' . $shareUrl, ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener', 'title' => 'Share on X']) ?>
                <?= Html::a('<i class="fab fa-facebook-f" aria-hidden="true"></i><span class="visually-hidden">Facebook</span>', 'https://www.facebook.com/sharer/sharer.php?u=' . $shareUrl, ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener', 'title' => 'Share on Facebook']) ?>
                <?= Html::a('<i class="fab fa-whatsapp" aria-hidden="true"></i><span class="visually-hidden">WhatsApp</span>', 'https://wa.me/?text=' . $shareText . '%20' . $shareUrl, ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener', 'title' => 'Share on WhatsApp']) ?>
                <?= Html::a('<i class="fas fa-envelope" aria-hidden="true"></i><span class="visually-hidden">Email</span>', 'mailto:?subject=' . $shareText . '&body=' . $shareUrl, ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'Share by email']) ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-link="<?= Html::encode($url) ?>"><i class="fas fa-link me-1" aria-hidden="true"></i><span aria-live="polite">Copy link</span></button>
            </div>

            <?php if ($post->tags): ?>
                <nav class="mw-tags mt-4" aria-label="Tags">
                    <?php foreach ($post->tags as $tag): ?>
                        <?= Html::a('#' . Html::encode($tag->name), ['/blog/index', 'tag' => $tag->slug], ['class' => 'mw-chip']) ?>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>

            <?php if ($authorName !== null): ?>
                <section class="mw-author d-flex gap-3 align-items-start mt-5" aria-label="About the author">
                    <?php if ($post->author->photo): ?>
                        <?= Html::img($frontendBase . '/' . $post->author->photo, ['alt' => 'Photo of ' . $authorName, 'width' => 64, 'height' => 64, 'loading' => 'lazy', 'class' => 'rounded-circle flex-shrink-0', 'style' => 'object-fit: cover']) ?>
                    <?php endif; ?>
                    <div>
                        <p class="fw-bold mb-1">Written by <?= Html::encode($authorName) ?></p>
                        <?php if ((string) $post->author->bio !== ''): ?>
                            <p class="small mb-0"><?= Html::encode($post->author->bio) ?></p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($older !== null || $newer !== null): ?>
                <nav class="mw-post-nav row g-3 mt-4" aria-label="More posts">
                    <div class="col-sm-6">
                        <?php if ($older !== null): ?>
                            <?= Html::a('<span class="small d-block text-muted">← Older post</span>' . Html::encode($older->title), ['/blog/view', 'slug' => $older->slug], ['rel' => 'prev', 'class' => 'd-block text-decoration-none']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <?php if ($newer !== null): ?>
                            <?= Html::a('<span class="small d-block text-muted">Newer post →</span>' . Html::encode($newer->title), ['/blog/view', 'slug' => $newer->slug], ['rel' => 'next', 'class' => 'd-block text-decoration-none']) ?>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>

            <div class="mt-5"><?= $this->render('_subscribe', ['id' => 'subscribe-post']) ?></div>

            <?php $notice = Yii::$app->session->getFlash('comment'); ?>
            <?php if ($comments || $post->comments_open || is_array($notice)): ?>
                <section id="comments" class="mw-comments mt-5" aria-labelledby="comments-title">
                    <h2 id="comments-title" class="h4 fw-bold"><?= $comments ? count($comments) . ' ' . (count($comments) === 1 ? 'comment' : 'comments') : 'Comments' ?></h2>
                    <?php if (is_array($notice)): ?>
                        <div class="alert alert-<?= $notice[0] === 'success' ? 'success' : 'danger' ?>" role="<?= $notice[0] === 'success' ? 'status' : 'alert' ?>"><?= Html::encode($notice[1]) ?></div>
                    <?php endif; ?>
                    <?php foreach ($comments as $item): ?>
                        <div class="mw-comment">
                            <p class="mb-1"><strong><?= Html::encode($item->author_name) ?></strong> <span class="small text-muted">· <?= Yii::$app->formatter->asDate($item->created_at, 'php:d M Y') ?></span></p>
                            <div class="mw-comment-body"><?= nl2br(Html::encode($item->body)) ?></div>
                            <?php foreach ($item->replies as $reply): ?>
                                <div class="mw-comment mw-comment-reply">
                                    <p class="mb-1"><strong><?= Html::encode($reply->author_name) ?></strong> <span class="badge text-bg-primary">Team</span> <span class="small text-muted">· <?= Yii::$app->formatter->asDate($reply->created_at, 'php:d M Y') ?></span></p>
                                    <div class="mw-comment-body"><?= nl2br(Html::encode($reply->body)) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($post->comments_open): ?>
                        <?php $form = ActiveForm::begin(['id' => 'comment-form', 'action' => ['/blog/view', 'slug' => $post->slug, '#' => 'comments'], 'options' => ['class' => 'mw-panel mt-4']]); ?>
                            <h3 class="h5 fw-bold">Leave a comment</h3>
                            <p class="small text-muted">Comments appear once we have read them. Your email is not shown.</p>
                            <?= FormGuard::field() ?>
                            <div class="row g-3">
                                <div class="col-sm-6"><?= $form->field($comment, 'author_name')->textInput(['maxlength' => 100, 'autocomplete' => 'name']) ?></div>
                                <div class="col-sm-6"><?= $form->field($comment, 'author_email')->input('email', ['maxlength' => 255, 'autocomplete' => 'email']) ?></div>
                            </div>
                            <?= $form->field($comment, 'body')->textarea(['rows' => 5, 'maxlength' => 5000]) ?>
                            <?= Html::submitButton('Post comment', ['class' => 'btn btn-primary']) ?>
                        <?php ActiveForm::end(); ?>
                    <?php else: ?>
                        <p class="small text-muted">Comments are closed on this post.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

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
