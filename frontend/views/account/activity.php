<?php

use yii\helpers\Html;

$this->title = 'Activity Timeline';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="card shadow-sm">

    <div class="card-header">

        <h5 class="mb-0">
            Activity Timeline
        </h5>

    </div>

    <div class="card-body p-0">
        <?php

$groupedActivities = [];

foreach ($activities as $activity) {

    $dateKey =
        Yii::$app
            ->formatter
            ->asDate(
                $activity['time'],
                'php:Y-m-d'
            );

    $groupedActivities[$dateKey][] =
        $activity;
}
?>
        <?php if (empty($activities)): ?>

            <div class="text-center py-5 text-muted">

                No activity available.

            </div>

<?php else: ?>

            <div class="list-group list-group-flush">

    <?php
    foreach (
            $groupedActivities
    as $date => $items
    ):
        ?>

                    <?php
                    $today = Yii::$app
    ->formatter
    ->asDate(
        time(),
        'php:Y-m-d'
    );

$yesterday = Yii::$app
    ->formatter
    ->asDate(
        strtotime('-1 day'),
        'php:Y-m-d'
    );
                    if ($date === $today) {

                        $heading = 'Today';
                    } elseif (
                            $date === $yesterday
                    ) {

                        $heading = 'Yesterday';
                    } else {

                        $heading = Yii::$app
                                ->formatter
                                ->asDate(
                                        $date,
                                        'php:d M Y'
                                );
                    }
                    ?>

                    <div
    class="
        bg-light
        border-top
        border-bottom
        px-3
        py-2
        fw-bold
        text-secondary
        text-uppercase
        small
    "
>

        <?= $heading ?>
                    </div>

        <?php
        foreach (
                $items
        as $activity
        ):
            ?>

                        <a
                            href="<?= Html::encode($activity['url']) ?>"
                            class="
                            list-group-item
                            list-group-item-action
                            "
                            >

                            <div
                                class="
                                d-flex
                                align-items-start
                                "
                                >

                               <div class="me-3">

    <span
        class="
            badge
            rounded-pill
            <?= Html::encode($activity['badgeClass']) ?>
        "
        style="
            width:40px;
            height:40px;
            display:flex;
            align-items:center;
            justify-content:center;
        "
    >

        <i class="<?= Html::encode($activity['icon']) ?>"></i>

    </span>

</div>

                                <div
                                    class="flex-grow-1"
                                    >

                                    <div
                                        class="fw-semibold"
                                        >
            <?=
            Html::encode(
                    $activity['title']
            )
            ?>
                                    </div>

                                    <div
                                        class="
                                        text-muted
                                        small
                                        "
                                        >
            <?=
            Html::encode(
                    $activity['description']
            )
            ?>
                                    </div>

                                </div>

                                <div
                                    class="
                                    small
                                    text-muted
                                    text-nowrap
                                    "
                                    >
                                        <div>

    <?= Yii::$app
        ->formatter
        ->asTime(
            $activity['time'],
            'php:h:i A'
        ) ?>

</div>

<div
    class="
        small
        text-muted
    "
>

    <?= Yii::$app
        ->formatter
        ->asRelativeTime(
            $activity['time']
        ) ?>

</div>
                                </div>

                            </div>

                        </a>

                    <?php endforeach; ?>

    <?php endforeach; ?>

            </div>

<?php endif; ?>

    </div>

</div>