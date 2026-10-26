<?php
/** @var \yii\web\View $this view component instance */
/** @var \yii\mail\MessageInterface $message the message being composed */
/** @var string $content main view render result */
?>
<?php $this->beginPage() ?>
<?php $this->beginBody() ?>
Miracle Web Technologies

==================================================

<?= trim($content) ?>


--------------------------------------------------

Support:
<?= Yii::$app->params['supportEmail'] ?>


Website:
https://miraclewebtechnologies.com

==================================================

This is an automated system generated email.

Please do not reply directly to this message.
<?php $this->endBody() ?>
<?php
$this->endPage();
