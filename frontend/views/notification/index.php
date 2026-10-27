<?php
declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Notifications';

$notifications = $dataProvider->getModels();
?>

<div class="card border-0 shadow-sm">

    <div class="card-header d-flex justify-content-between">

        <strong>
            Notifications
        </strong>

<?=
Html::beginForm(
        ['/notification/mark-all-read'],
        'post'
)
?>

        <?=
        Html::submitButton(
                'Mark All Read',
                [
                    'class' =>
                    'btn btn-sm btn-outline-primary',
                ]
        )
        ?>

<?= Html::endForm() ?>

    </div>

    <div class="list-group list-group-flush">

<?php foreach ($notifications as $notification): ?>

            <a
                href="<?=
                \yii\helpers\Url::to([
                    '/notification/view',
                    'id' => $notification->id,
                ])
                ?>"
                class="
                list-group-item
                list-group-item-action
    <?=
    !$notification->is_read ? 'border-start border-primary border-4' : ''
    ?>
                "
                >

                <div class="fw-semibold">

    <?=
    Html::encode(
            $notification->title
    )
    ?>

                </div>

                <div class="small text-muted">

    <?=
            Yii::$app
            ->formatter
            ->asDatetime(
                    $notification->created_at
            )
    ?>

                </div>

            </a>

<?php endforeach; ?>

    </div>

</div>