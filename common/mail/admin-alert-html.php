<?php

use yii\helpers\Html;

/** @var string $title */
/** @var string $message */
?>

<div
    style="
    font-family:Arial,sans-serif;
    padding:20px;
    "
    >

    <h2>
        <?= Html::encode($title) ?>
    </h2>

    <pre
        style="
        white-space:pre-wrap;
        font-family:Arial,sans-serif;
        "
        >
            <?= Html::encode($message) ?>
    </pre>

</div>