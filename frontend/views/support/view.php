<div class="card shadow-sm mb-4">

    <div class="card-header">

        <strong>
            Conversation
        </strong>

    </div>

    <div class="card-body">

        <?php
        foreach (
                $model->messages
        as $message
        ):
            ?>

            <?php
            $customer = $message->sender_type ===
                    \common\models\SupportTicketMessage::SENDER_CUSTOMER;
            ?>

            <div class="
                 d-flex
                 <?=
                 $customer ? 'justify-content-end' : 'justify-content-start'
                 ?>
                 mb-3
                 ">

                <div
                    class="
                    rounded-3
                    p-3
                    <?=
                    $customer ? 'bg-primary text-white' : 'bg-light border'
                    ?>
                    "
                    style="max-width:75%;"
                    >

                    <div class="
                         small
                         fw-semibold
                         mb-1
                         ">
                             <?=
                             $customer ? 'You' : 'Support Team'
                             ?>
                    </div>

                    <div>
                        <?=
                        nl2br(
                                Html::encode(
                                        $message->message
                                )
                        )
                        ?>
                    </div>

                    <div
                        class="
                        small mt-2
                        <?=
                        $customer ? 'text-white-50' : 'text-muted'
                        ?>
                        "
                        >
                            <?=
                                    Yii::$app
                                    ->formatter
                                    ->asDatetime(
                                            $message
                                            ->created_at,
                                            'php:d M Y, h:i A'
                                    )
                            ?>
                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>
<?php
if (
        $model->status !== \common\models\SupportTicket::STATUS_CLOSED
):
    ?>

    <?=
    Html::beginForm(
            [
                'reply',
                'number' =>
                $model->ticket_number,
            ],
            'post'
    )
    ?>

    <div class="card shadow-sm">

        <div class="card-header">
            <strong>
                Reply
            </strong>
        </div>

        <div class="card-body">

            <?=
            Html::textarea(
                    'message',
                    '',
                    [
                        'class' =>
                        'form-control',
                        'rows' =>
                        5,
                        'maxlength' =>
                        10000,
                        'required' =>
                        true,
                        'placeholder' =>
                        'Write your reply...',
                    ]
            )
            ?>

        </div>

        <div class="card-footer">

            <?=
            Html::submitButton(
                    'Send Reply',
                    [
                        'class' =>
                        'btn btn-primary',
                    ]
            )
            ?>

        </div>

    </div>

    <?= Html::endForm() ?>

<?php endif; ?>