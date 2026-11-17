<?php

declare(strict_types=1);

/** @var common\models\BlogPost $post */
/** @var string $postUrl */
/** @var string $unsubscribeUrl */
/** @var string $site */
?>
New on the blog: <?= $post->title ?>


<?= $post->summary ?>


Read the post:
<?= $postUrl ?>


You get this because you subscribed to the <?= $site ?> blog. Unsubscribe:
<?= $unsubscribeUrl ?>
