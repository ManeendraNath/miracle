<?php

declare(strict_types=1);

use common\models\BlogCategory;
use common\models\BlogPost;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\BlogPost $model */

$this->title = $model->isNewRecord ? 'Write a post' : 'Edit post';
$this->params['breadcrumbs'][] = ['label' => 'Blog Posts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['actions'] = [Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], ['class' => 'btn btn-light border'])];
if (!$model->isNewRecord) {
    $this->params['actions'][] = Html::a('<i class="fas fa-eye me-1"></i> Preview', ['preview', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']);
    $this->params['actions'][] = Html::a('<i class="fab fa-google me-1"></i> Search Console', Yii::$app->params['searchConsoleUrl'], ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener']);
}
$this->params['help'] = [
    'Title' => 'What the post answers, in words a client abroad would search for, e.g. "How much does a custom web app cost in 2026?".',
    'Summary' => 'One or two sentences. Shown under the title in the blog list and, usually, by Google under the search result (about the first 155 characters).',
    'Table of contents' => 'Posts with three or more headings get an "In this post" list at the top, built from the Heading 2 and Heading 3 titles.',
    'FAQ section' => 'Add a Heading 2 named "Frequently asked questions", then each question as a Heading 3 with its answer below. The questions are marked up for search engines and AI assistants (Google shows them as rich results only for a few official sites).',
    'Code' => 'Text in a code block is shown with colours on the website.',
    'Post' => 'Use Heading 2 for sections and Heading 3 inside them. The image button uploads a picture (JPG, PNG or WebP, up to 5 MB). Pasted text keeps only plain formatting.',
    'Status and Publish on' => 'Draft: only staff can see it. Published: live now, or at the Publish on time if that is later. Leave Publish on empty to publish straight away.',
    'Web address' => 'Made from the title. Keep it short; changing it after publishing breaks links that point to the old address.',
    'Cover image' => 'Shown on the blog list, at the top of the post, and when the post is shared on LinkedIn or WhatsApp. About 1600 x 900 works best.',
    'Search result title' => 'Optional, up to 70 characters, for when the title is too long for Google. Leave empty to use the title.',
    'Tags' => 'A few topics, separated by commas, e.g. "Web apps, Costs". Each gets its own page on the blog. Reuse existing tags rather than near-duplicates.',
    'Cover description' => 'What the cover image shows, for people using screen readers and for Google Images. Leave empty only if the picture is decoration.',
    'Feature at the top of the blog' => 'Shown large at the top of the blog page. If several are ticked, the newest one is shown.',
    'Keep out of search engines' => 'The post stays on the website but asks Google not to list it, and is left out of the sitemap. For thin or time-limited posts.',
    'Google preview' => 'About how the post may look in Google results. Google may still choose its own text.',
    'Earlier versions' => 'Every save that changes the title, summary or text keeps the version before it (the last 30). View one to compare, then Restore to put it back.',
    'Unsaved changes' => 'What you type is kept in this browser while you write. If the page is closed before saving, opening the post again offers to bring it back.',
    'Preview' => 'Shows the post as visitors will see it, drafts too. Save first to preview your latest changes.',
    'Search Console' => 'Opens Google Search Console for this website. New posts reach Google through the sitemap within days; to speed it up, after publishing: URL inspection > paste the post address > Request indexing. The message after publishing shows the address.',
];

// The editor (Quill) from jsDelivr, pinned with integrity hashes; its scripts get the page's CSP nonce.
$this->registerCssFile('https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css', ['integrity' => 'sha384-ecIckRi4QlKYya/FQUbBUjS4qp65jF/J87Guw5uzTbO1C1Jfa/6kYmd6dXUF6D7i', 'crossorigin' => 'anonymous']);
$this->registerJsFile('https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js', ['integrity' => 'sha384-utBUCeG4SYaCm4m7GQZYr8Hy8Fpy3V4KGjBZaf4WTKOcwhCYpt/0PfeEe3HNlwx8', 'crossorigin' => 'anonymous', 'position' => $this::POS_END]);
$uploadUrl = Json::htmlEncode(Url::to(['upload-image']));
$csrfParam = Json::htmlEncode(Yii::$app->request->csrfParam);
$this->registerJs(<<<JS
(() => {
    const body = document.getElementById('blogpost-body');
    const quill = new Quill('#post-editor', {
        theme: 'snow',
        placeholder: 'Write the post…',
        modules: {
            toolbar: {
                container: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['blockquote', 'code-block', 'link', 'image'], ['clean']],
                handlers: {
                    image: () => {
                        const input = document.createElement('input');
                        input.type = 'file';
                        input.accept = 'image/jpeg,image/png,image/webp';
                        input.onchange = async () => {
                            if (!input.files.length) return;
                            const data = new FormData();
                            data.append('image', input.files[0]);
                            data.append($csrfParam, document.querySelector('meta[name=csrf-token]').content);
                            const response = await fetch($uploadUrl, { method: 'POST', body: data });
                            const result = await response.json().catch(() => ({ error: 'The image could not be uploaded.' }));
                            if (!response.ok || !result.url) {
                                alert(result.error || 'The image could not be uploaded.');
                                return;
                            }
                            const range = quill.getSelection(true);
                            quill.insertEmbed(range.index, 'image', result.url, 'user');
                            quill.setSelection(range.index + 1);
                        };
                        input.click();
                    },
                },
            },
        },
    });
    // Proper <ul>/<ol> markup; outside code blocks, Quill's non-breaking spaces become normal ones so lines wrap.
    const form = document.getElementById('blog-post-form');
    const field = (name) => form.querySelector('[name="BlogPost[' + name + ']"]');
    form.addEventListener('submit', () => {
        const html = quill.getSemanticHTML().split(/(<pre[\\s\\S]*?<\\/pre>)/).map((part, i) => i % 2 ? part : part.replace(/&nbsp;/g, ' ')).join('');
        body.value = quill.getText().trim() === '' && !html.includes('<img') ? '' : html;
        // The browser copy stays until the post is really saved (the save may fail or the session may have ended).
        write();
    });

    // Unsaved changes are kept in this browser, so a closed tab or lost connection doesn't lose them.
    const draftKey = 'blog-draft-' + form.dataset.post;
    const current = () => ({ title: field('title').value, summary: field('summary').value, body: quill.root.innerHTML });
    const saved = JSON.stringify(current());
    let timer = null;
    const write = () => {
        clearTimeout(timer);
        const now = current();
        try {
            // After a failed save the form shows text the database doesn't have: then the copy is always kept.
            if (JSON.stringify(now) === saved && !('unsaved' in form.dataset)) { localStorage.removeItem(draftKey); } else { localStorage.setItem(draftKey, JSON.stringify({ ...now, at: Date.now(), base: form.dataset.updated })); }
        } catch (e) {}
    };
    const keep = () => { clearTimeout(timer); timer = setTimeout(write, 1500); };
    window.addEventListener('pagehide', write);
    quill.on('text-change', keep);
    field('title').addEventListener('input', keep);
    field('summary').addEventListener('input', keep);
    let draft = null;
    try {
        draft = JSON.parse(localStorage.getItem(draftKey) || 'null');
        // Saved since this copy was made (by this tab or another): the copy is out of date.
        if (draft && draft.base !== form.dataset.updated) { localStorage.removeItem(draftKey); draft = null; }
        // A new post that was just created: its unsaved copy was kept under "new".
        const fresh = JSON.parse(localStorage.getItem('blog-draft-new') || 'null');
        if (form.dataset.post !== 'new' && fresh && String(fresh.title).trim() === field('title').value.trim()) { localStorage.removeItem('blog-draft-new'); }
    } catch (e) {}
    if (draft && JSON.stringify({ title: draft.title, summary: draft.summary, body: draft.body }) !== saved) {
        const banner = document.getElementById('draft-banner');
        banner.querySelector('[data-when]').textContent = new Date(draft.at).toLocaleString();
        banner.classList.remove('d-none');
        banner.querySelector('[data-restore]').addEventListener('click', () => {
            field('title').value = draft.title;
            field('summary').value = draft.summary;
            quill.clipboard.dangerouslyPasteHTML(draft.body);
            banner.classList.add('d-none');
        });
        banner.querySelector('[data-discard]').addEventListener('click', () => {
            try { localStorage.removeItem(draftKey); } catch (e) {}
            banner.classList.add('d-none');
        });
    }

    // Google preview, as the title, address and summary are typed.
    const preview = document.getElementById('serp-preview');
    const cut = (text, n) => text.length > n ? text.slice(0, n - 1).trimEnd() + '…' : text;
    const showPreview = () => {
        preview.querySelector('[data-title]').textContent = cut(field('seo_title').value || field('title').value || 'Post title', 60);
        preview.querySelector('[data-summary]').textContent = cut(field('summary').value || 'The summary is usually shown here.', 155);
    };
    ['title', 'seo_title', 'summary'].forEach((name) => field(name).addEventListener('input', showPreview));
    showPreview();
})();
JS);
?>
<div class="blog-post-form">
    <div id="draft-banner" class="alert alert-warning d-none d-flex flex-wrap align-items-center gap-2" role="status">
        <span><i class="fas fa-history me-1"></i> This browser kept changes you had not saved (<span data-when></span>).</span>
        <button type="button" class="btn btn-sm btn-warning" data-restore>Bring them back</button>
        <button type="button" class="btn btn-sm btn-link" data-discard>Discard</button>
    </div>
    <?php $form = ActiveForm::begin(['id' => 'blog-post-form', 'options' => ['enctype' => 'multipart/form-data', 'data-post' => $model->isNewRecord ? 'new' : (string) $model->id, 'data-updated' => (string) (int) $model->updated_at, 'data-unsaved' => $model->hasErrors() ? '' : null]]); ?>
    <div class="row">
        <div class="col-xl-8">
            <div class="card settings-card shadow-sm mb-4">
                <div class="card-body">
                    <?= $form->field($model, 'title')->textInput(['maxlength' => 200, 'placeholder' => 'e.g. How much does a custom web application cost?', 'class' => 'form-control form-control-lg']) ?>
                    <?= $form->field($model, 'summary')->textarea(['rows' => 2, 'maxlength' => 300])->hint('One or two sentences. Google usually shows about the first 155 characters.') ?>
                    <?= $form->field($model, 'body', ['template' => "{label}\n{editor}\n{input}\n{error}", 'parts' => ['{editor}' => '<div id="post-editor" class="blog-editor">' . BlogPost::cleanBody($model->body) . '</div>']])->textarea(['class' => 'd-none']) ?>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card settings-card shadow-sm mb-4">
                <div class="card-header bg-white"><h3 class="card-title fw-semibold">Publishing</h3></div>
                <div class="card-body">
                    <?= $form->field($model, 'status')->dropDownList(BlogPost::statuses()) ?>
                    <?= $form->field($model, 'publishAt')->input('datetime-local')->hint('Leave empty to publish as soon as it is saved as Published.') ?>
                    <?= $form->field($model, 'category_id')->dropDownList(BlogCategory::options(), ['prompt' => 'No category'])->hint(Html::a('Manage categories', ['/blog-category/index'])) ?>
                    <?= $form->field($model, 'tagNames')->textInput(['maxlength' => 500, 'placeholder' => 'e.g. Web apps, Costs', 'list' => 'blog-tags'])->hint('Separate with commas.') ?>
                    <datalist id="blog-tags"><?php foreach (\common\models\BlogTag::find()->select('name')->orderBy(['name' => SORT_ASC])->column() as $tagName):
                        ?><option value="<?= Html::encode($tagName) ?>"></option><?php
                                             endforeach; ?></datalist>
                    <?= $form->field($model, 'is_featured')->checkbox() ?>
                    <?php
                    $delivered = $model->newsletter_started_at !== null ? (int) $model->getBlogNewsletterDeliveries()->andWhere(['failed' => 0])->count() : 0;
                    $newsletterHint = match (true) {
                        $model->newsletter_done_at !== null && $delivered === 0 => 'Not emailed: it was switched off, kept out of search engines, published before the newsletter existed, or there were no subscribers yet.',
                        $model->newsletter_done_at !== null => 'Done: emailed to ' . $delivered . ' subscriber(s). It is not emailed again after edits.',
                        $model->newsletter_started_at !== null => 'Sending now: ' . $delivered . ' sent so far.',
                        default => 'Sent once, within about 15 minutes of going live, to confirmed subscribers (not if kept out of search engines).',
                    };
                    ?>
                    <?= $form->field($model, 'send_newsletter')->checkbox(['disabled' => $model->newsletter_started_at !== null])->hint($newsletterHint) ?>
                    <?= $form->field($model, 'comments_open')->checkbox()->hint('Readers can comment; each waits for approval under Blog > Comments.') ?>
                    <?= $form->field($model, 'slug', ['enableClientValidation' => false])->textInput(['maxlength' => 220, 'placeholder' => 'Made from the title'])->hint('/blog/' . Html::encode($model->slug ?: 'made-from-the-title')) ?>
                    <?= Html::submitButton('<i class="fas fa-save me-1"></i> Save', ['class' => 'btn btn-primary w-100']) ?>
                </div>
            </div>
            <div class="card settings-card shadow-sm mb-4">
                <div class="card-header bg-white"><h3 class="card-title fw-semibold">Cover and search</h3></div>
                <div class="card-body">
                    <?php if ($model->cover_image): ?>
                        <?= Html::img(rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/') . '/' . $model->cover_image, ['class' => 'img-fluid rounded mb-2', 'alt' => 'Current cover image']) ?>
                        <div class="form-check mb-2">
                            <?= Html::checkbox('remove_cover', (bool) Yii::$app->request->post('remove_cover'), ['class' => 'form-check-input', 'id' => 'remove-cover']) ?>
                            <label class="form-check-label small" for="remove-cover">Remove the cover image</label>
                        </div>
                    <?php endif; ?>
                    <?= $form->field($model, 'cover_image')->fileInput(['accept' => 'image/jpeg,image/png,image/webp'])->label($model->cover_image ? 'Replace cover image' : 'Cover image')->hint('JPG, PNG or WebP, up to 5 MB. About 1600 x 900 works best.') ?>
                    <?= $form->field($model, 'cover_alt')->textInput(['maxlength' => 200, 'placeholder' => 'e.g. Laptop showing a shopping cart dashboard'])->hint('What the cover shows, for screen readers and Google Images.') ?>
                    <?= $form->field($model, 'seo_title')->textInput(['maxlength' => 70])->hint('Optional. Leave empty to use the title.') ?>
                    <div id="serp-preview" class="border rounded p-2 mb-3 small" aria-label="Google preview">
                        <div class="text-muted text-truncate"><?= Html::encode(preg_replace('#^https?://#', '', rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/'))) ?> › blog › <?= Html::encode($model->slug ?: '…') ?></div>
                        <div data-title class="text-primary fs-6"></div>
                        <div data-summary class="text-secondary"></div>
                    </div>
                    <?= $form->field($model, 'noindex')->checkbox() ?>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
    <?php /** @var common\models\BlogPostRevision[] $revisions */ $revisions = $model->isNewRecord ? [] : $model->getRevisions()->with('author')->all(); ?>
    <?php if ($revisions !== []): ?>
        <div class="row">
            <div class="col-xl-8">
                <div class="card settings-card shadow-sm mb-4">
                    <div class="card-header bg-white"><h3 class="card-title fw-semibold">Earlier versions</h3></div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($revisions as $revision): ?>
                            <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                                <span class="me-auto"><?= Yii::$app->formatter->asDatetime($revision->created_at, 'php:d M Y, H:i') ?><?= $revision->author ? ' · replaced by ' . Html::encode($revision->author->authorName() ?? $revision->author->username) : '' ?></span>
                                <?= Html::a('View', ['revision', 'id' => $revision->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                                <?= Html::a('Restore', ['restore', 'id' => $revision->id], ['class' => 'btn btn-sm btn-outline-primary', 'data' => ['method' => 'post', 'confirm' => 'Put this version\'s title, summary and text back? The current text is kept in this list.']]) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
