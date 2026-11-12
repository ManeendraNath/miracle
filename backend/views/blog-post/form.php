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
}
$this->params['help'] = [
    'Title' => 'What the post answers, in words a client abroad would search for, e.g. "How much does a custom web app cost in 2026?".',
    'Summary' => 'One or two sentences. Shown under the title in the blog list and, usually, by Google under the search result (about the first 155 characters).',
    'Post' => 'Use Heading 2 for sections and Heading 3 inside them. The image button uploads a picture (JPG, PNG or WebP, up to 5 MB). Pasted text keeps only plain formatting.',
    'Status and Publish on' => 'Draft: only staff can see it. Published: live now, or at the Publish on time if that is later. Leave Publish on empty to publish straight away.',
    'Web address' => 'Made from the title. Keep it short; changing it after publishing breaks links that point to the old address.',
    'Cover image' => 'Shown on the blog list, at the top of the post, and when the post is shared on LinkedIn or WhatsApp. About 1600 x 900 works best.',
    'Search result title' => 'Optional, up to 70 characters, for when the title is too long for Google. Leave empty to use the title.',
    'Preview' => 'Shows the post as visitors will see it, drafts too. Save first to preview your latest changes.',
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
    document.getElementById('blog-post-form').addEventListener('submit', () => {
        const html = quill.getSemanticHTML().split(/(<pre[\\s\\S]*?<\\/pre>)/).map((part, i) => i % 2 ? part : part.replace(/&nbsp;/g, ' ')).join('');
        body.value = quill.getText().trim() === '' && !html.includes('<img') ? '' : html;
    });
})();
JS);
?>
<div class="blog-post-form">
    <?php $form = ActiveForm::begin(['id' => 'blog-post-form', 'options' => ['enctype' => 'multipart/form-data']]); ?>
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
                            <?= Html::checkbox('remove_cover', false, ['class' => 'form-check-input', 'id' => 'remove-cover']) ?>
                            <label class="form-check-label small" for="remove-cover">Remove the cover image</label>
                        </div>
                    <?php endif; ?>
                    <?= $form->field($model, 'cover_image')->fileInput(['accept' => 'image/jpeg,image/png,image/webp'])->label($model->cover_image ? 'Replace cover image' : 'Cover image')->hint('JPG, PNG or WebP, up to 5 MB. About 1600 x 900 works best.') ?>
                    <?= $form->field($model, 'seo_title')->textInput(['maxlength' => 70])->hint('Optional. Leave empty to use the title.') ?>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
