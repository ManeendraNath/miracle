<?php
declare(strict_types=1);

use common\models\SupportTicket;
use yii\helpers\Html;

/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Support Tickets';

$tickets = $dataProvider->getModels();
?>

<div class="card shadow-sm border-0">

    <div class="card-header d-flex justify-content-between align-items-center">

        <strong>
            Support Tickets
        </strong>

<?=
Html::a(
        'Create Ticket',
        ['create'],
        [
            'class' => 'btn btn-primary btn-sm',
        ]
)
?>

    </div>

    <div class="card-body p-0">

        <?php if (empty($tickets)): ?>

            <div class="text-center py-5 text-muted">

                <i class="fas fa-headset fa-3x mb-3"></i>

                <div>
                    No support tickets found.
                </div>

            </div>

        <?php else: ?>

            <div class="list-group list-group-flush">

                <?php foreach ($tickets as $ticket): ?>

                    <?php
                    $badge = match ($ticket->status) {
                        'Open' => 'bg-danger',
                        'In Progress' => 'bg-warning text-dark',
                        'Resolved' => 'bg-success',
                        'Closed' => 'bg-secondary',
                        default => 'bg-secondary',
                    };
                    ?>

                    <a
                        href="<?=
            \yii\helpers\Url::to([
                '/support/view',
                'number' => $ticket->ticket_number,
            ])
                    ?>"
                        class="list-group-item list-group-item-action"
                        >

                        <div class="d-flex justify-content-between">

                            <div>

                                <h6 class="mb-1">

                                    <?=
                                    Html::encode(
                                            $ticket->subject
                                    )
                                    ?>

                                </h6>

                                <small class="text-muted">

                                    <?=
                                    Html::encode(
                                            $ticket->ticket_number
                                    )
                                    ?>

                                </small>

                            </div>

                            <span
                                class="badge <?= $badge ?>"
                                >
        <?=
        Html::encode(
                $ticket->status
        )
        ?>
                            </span>

                        </div>

                    </a>

    <?php endforeach; ?>

            </div>

<?php endif; ?>

    </div>

</div>