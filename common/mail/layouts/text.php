<?php
/** @var \yii\web\View $this view component instance */
/** @var \yii\mail\MessageInterface $message the message being composed */
/** @var string $content main view render result */
?>
<?php $this->beginPage() ?>
<?php $this->beginBody() ?>
<?= \common\components\CustomYii::businessName() . "\n" ?>

==================================================

<?= trim($content) ?>


--------------------------------------------------

Support:
<?= Yii::$app->params['supportEmail'] ?>


Website:
<?= \common\components\CustomYii::businessUrl() . "\n" ?>

==================================================

<?php if (empty($this->params['replyAllowed'])): ?>
This is an automated system generated email.

Please do not reply directly to this message.
<?php endif; ?>
<?php unset($this->params['replyAllowed']); ?>
<?php $this->endBody() ?>
<?php
$this->endPage();
