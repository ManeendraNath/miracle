<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Domains;
use common\models\Hosting;
use common\models\Maintenance;
use common\models\SupportTicket;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

final class SupportController extends Controller
{
    
    public $layout = 'account';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' =>
                AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' =>
                VerbFilter::class,
                'actions' => [
                    'reply' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $query = SupportTicket::find()
                ->where([
                    'user_id' =>
                    (int)
                    Yii::$app->user->id,
                ])
                ->orderBy([
                    'created_at' =>
                    SORT_DESC,
                    'id' =>
                    SORT_DESC,
        ]);

        return $this->render(
                        'index',
                        [
                            'dataProvider' =>
                            new ActiveDataProvider([
                                'query' =>
                                $query,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                                    ]),
                        ]
                );
    }

    public function actionCreate()
    {
        $model = new SupportTicket();

        $model->priority = SupportTicket::PRIORITY_NORMAL;

        if (
                Yii::$app->request->isPost && $model->load(
                        Yii::$app->request->post()
                )
        ) {
            $model->user_id = (int)
                    Yii::$app->user->id;

            if (
                    !$this->validateServiceOwnership(
                            $model
                    )
            ) {
                $model->addError(
                        'service_id',
                        'Selected service is not available.'
                );
            }

            if (!$model->hasErrors()) {
                $now = time();

                $model->ticket_number = 'SUP-'
                        . date('Ymd')
                        . '-'
                        . strtoupper(
                                bin2hex(
                                        random_bytes(3)
                                )
                        );

                $model->status = SupportTicket::STATUS_OPEN;

                $model->created_at = $now;

                $model->updated_at = $now;

                if ($model->save()) {
                    Yii::$app->session
                            ->setFlash(
                                    'success',
                                    'Support request submitted successfully.'
                            );

                    return $this->redirect([
                                'view',
                                'number' =>
                                $model->ticket_number,
                    ]);
                }
            }
        }

        return $this->render(
                        'create',
                        [
                            'model' => $model,
                        ]
                );
    }

    public function actionView(
            string $number
    )
    {
        $model = SupportTicket::findOne([
            'ticket_number' =>
            $number,
            'user_id' =>
            (int)
            Yii::$app->user->id,
        ]);

        if ($model === null) {
            throw new NotFoundHttpException(
                            'Support request does not exist.'
                    );
        }

        return $this->render(
                        'view',
                        [
                            'model' => $model,
                        ]
                );
    }

    private function validateServiceOwnership(
            SupportTicket $model
    ): bool
    {
        if (
                empty($model->service_type) || empty($model->service_id)
        ) {
            return true;
        }

        $userId = (int)
                Yii::$app->user->id;

        return match (
        $model->service_type
        ) {
            'domain' =>
                    Domains::find()
                    ->where([
                        'id' =>
                        $model->service_id,
                        'user_id' =>
                        $userId,
                    ])
                    ->exists(),
            'hosting' =>
                    Hosting::find()
                    ->where([
                        'id' =>
                        $model->service_id,
                        'user_id' =>
                        $userId,
                    ])
                    ->exists(),
            'maintenance' =>
                    Maintenance::find()
                    ->where([
                        'id' =>
                        $model->service_id,
                        'user_id' =>
                        $userId,
                    ])
                    ->exists(),
            default =>
            false,
        };
    }

    public function actionReply(
            string $number
    )
    {
        $ticket = SupportTicket::findOne([
            'ticket_number' =>
            $number,
            'user_id' =>
            (int)
            Yii::$app->user->id,
        ]);

        if ($ticket === null) {
            throw new NotFoundHttpException(
                            'Support request does not exist.'
                    );
        }

        if (
                in_array(
                        $ticket->status,
                        [
                            SupportTicket::STATUS_CLOSED,
                        ],
                        true
                )
        ) {
            Yii::$app->session
                    ->setFlash(
                            'warning',
                            'This support request is closed.'
                    );

            return $this->redirect([
                        'view',
                        'number' => $number,
            ]);
        }

        $message = trim(
                (string)
                        Yii::$app->request
                        ->post('message', '')
        );

        if (
                $message === '' || mb_strlen($message) > 10000
        ) {
            Yii::$app->session
                    ->setFlash(
                            'error',
                            'Please enter a valid reply.'
                    );

            return $this->redirect([
                        'view',
                        'number' => $number,
            ]);
        }

        $model = new SupportTicketMessage();

        $model->ticket_id = (int) $ticket->id;

        $model->sender_type = SupportTicketMessage::SENDER_CUSTOMER;

        $model->sender_id = (int)
                Yii::$app->user->id;

        $model->message = $message;

        $model->created_at = time();

        if (!$model->save()) {
            throw new \RuntimeException(
                            'Support reply could not be saved.'
                    );
        }

        $ticket->updated_at = time();

        if (
                $ticket->status ===
                SupportTicket::STATUS_RESOLVED
        ) {
            $ticket->status = SupportTicket::STATUS_OPEN;
        }

        $ticket->save(
                false,
                [
                    'status',
                    'updated_at',
                ]
        );

        Yii::$app->session
                ->setFlash(
                        'success',
                        'Your reply has been added.'
                );

        return $this->redirect([
                    'view',
                    'number' =>
                    $ticket->ticket_number,
        ]);
    }
}
