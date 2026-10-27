<?php
declare(strict_types=1);

use yii\helpers\Html;

/** @var common\models\CustomerNotification $model */
$this->title = $model->title;
?>

<div class="card border-0 shadow-sm">

    <div class="card-header">

        <strong>
<?=
Html::encode(
        $model->title
)
?>
        </strong>

    </div>

    <div class="card-body">

        <div class="small text-muted mb-3">

            <?=
                    Yii::$app
                    ->formatter
                    ->asDatetime(
                            $model->created_at
                    )
            ?>

        </div>

        <div>

            <?=
            nl2br(
                    Html::encode(
                            $model->message
                    )
            )
            ?>

        </div>

            <?php if (!empty($model->route)): ?>

            <div class="mt-4">

                <?=
                Html::a(
                        'Open Related Item',
                        $model->route,
                        [
                            'class' =>
                            'btn btn-primary',
                        ]
                )
                ?>

            </div>

<?php endif; ?>

    </div>

</div>