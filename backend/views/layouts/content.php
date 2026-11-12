<?php

/* @var $content string */

use yii\bootstrap4\Breadcrumbs;

?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-md" style="min-width: 16rem">
                    <h1 class="m-0">
                        <?php
                        if (!is_null($this->title)) {
                            echo \yii\helpers\Html::encode($this->title);
                        } else {
                            echo \yii\helpers\Inflector::camelize($this->context->id);
                        }
                        ?>
                    </h1>
                    <?php /* Pages set these instead of repeating the title in their own heading. */ ?>
                    <?php if (!empty($this->params['subtitle'])): ?>
                        <div class="text-muted mt-1"><?= $this->params['subtitle'] ?></div>
                    <?php endif; ?>
                </div><!-- /.col -->
                <div class="col-md-auto mw-100"><?php /* As wide as its buttons, so they stay on one line when they fit; drops below the title when they don't. */ ?>
                    <?php
                    echo Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        'options' => [
                            'class' => 'breadcrumb float-sm-right'
                        ]
                    ]);
                    ?>
                    <?php if (!empty($this->params['actions'])): ?>
                        <div class="clearfix"></div>
                        <?php
                        // A button without its own tooltip gets the help text written for its label (see 'help' below).
                        $help = (array) ($this->params['help'] ?? []);
                        $actions = array_map(static function (string $button) use ($help): string {
                            $label = trim(html_entity_decode(strip_tags($button)));
                            if (!is_string($help[$label] ?? null) || preg_match('/^<[^>]*\stitle=/', $button)) {
                                return $button;
                            }

                            return (string) preg_replace('/^<(a|button)\b/', '<$1 title="' . \yii\helpers\Html::encode($help[$label]) . '"', $button, 1);
                        }, (array) $this->params['actions']);
                        ?>
                        <div class="d-flex flex-wrap justify-content-sm-end gap-2 mt-2"><?= implode("\n", $actions) ?></div>
                    <?php endif; ?>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <?php /* Pages set $this->params['help']: ['Button or term' => 'what it does', 'A plain note', ...]. Plain text, encoded here. */ ?>
            <?php if (!empty($this->params['help'])): ?>
                <details class="page-help card card-body py-2 mb-0 small">
                    <summary class="text-primary"><i class="fas fa-question-circle me-1"></i> How this page works</summary>
                    <ul class="mb-1 mt-2 ps-3">
                        <?php foreach ((array) $this->params['help'] as $term => $text): ?>
                            <li><?= is_string($term) ? '<strong>' . \yii\helpers\Html::encode($term) . ':</strong> ' : '' ?><?= \yii\helpers\Html::encode($text) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <?php foreach (Yii::$app->session->getAllFlashes() as $type => $messages): ?>
                <?php foreach ((array) $messages as $message): ?>
                    <?= \yii\bootstrap4\Alert::widget([
                        'options' => ['class' => 'alert-' . ($type === 'error' ? 'danger' : $type)],
                        'body' => \yii\helpers\Html::encode($message),
                    ]) ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <?= $content ?><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>