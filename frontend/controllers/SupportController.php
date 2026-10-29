<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Domains;
use common\models\Hosting;
use common\models\Maintenance;
use common\models\SupportTicket;
use common\models\SupportTicketMessage;
use common\services\notification\NotificationService;
use common\services\admin\AdminAlertService;
use common\services\support\SupportTicketMailer;
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
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'reply' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $query = SupportTicket::find()
                ->where(['user_id' => (int) Yii::$app->user->id])
                ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
        return $this->render(
                        'index',
                        [
                            'dataProvider' =>
                                new ActiveDataProvider([
                                    'query' => $query,
                                    'pagination' => ['pageSize' => 20],
                                ]),
                        ]
                );
    }

    public function actionCreate()
    {
        $model = new SupportTicket();
        $model->priority = SupportTicket::PRIORITY_NORMAL;
        $userId = (int) Yii::$app->user->id;

        $services = [
            'domain' => Domains::find()
                    ->select(['domain_url', 'id'])
                    ->where(['user_id' => $userId])
                    ->orderBy(['domain_url' => SORT_ASC])
                    ->indexBy('id')
                    ->column(),
            'hosting' => Hosting::find()
                    ->select(['primary_domain', 'id'])
                    ->where(['user_id' => $userId])
                    ->orderBy(['primary_domain' => SORT_ASC])
                    ->indexBy('id')
                    ->column(),
            'maintenance' => Maintenance::find()
                    ->select(['website_url', 'id'])
                    ->where(['user_id' => $userId])
                    ->orderBy(['website_url' => SORT_ASC])
                    ->indexBy('id')
                    ->column(),
        ];

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $model->user_id = $userId;
            if (!$this->validateServiceOwnership($model)) {
                $model->addError('service_id', 'Selected service is not available.');
            }
            if (!$model->hasErrors()) {
                $now = time();
                do {
                    $ticketNumber = 'SUP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                } while (
                    SupportTicket::find()->where(['ticket_number' => $ticketNumber])->exists()
                );
                $model->ticket_number = $ticketNumber;
                $model->status = SupportTicket::STATUS_OPEN;
                $model->created_at = $now;
                $model->updated_at = $now;
                if ($model->save()) {
                    (new NotificationService())->create($userId, 'support', 'Support Ticket Created', sprintf('Ticket %s has been created.', $model->ticket_number), '/support/view?number=' . $model->ticket_number);
                    SupportTicketMailer::ticketCreated($model);
                    AdminAlertService::supportTicketCreated($model->ticket_number, Yii::$app->user->identity->username, $model->subject);
                    Yii::$app->session->setFlash('success', 'Support request submitted successfully.');
                    return $this->redirect(['view', 'number' => $model->ticket_number]);
                }
            }
        }
        return $this->render('create', ['model' => $model, 'services' => $services]);
    }

    public function actionView(string $number)
    {
        $model = SupportTicket::findOne(['ticket_number' => $number, 'user_id' => (int) Yii::$app->user->id]);
        if ($model === null) {
            throw new NotFoundHttpException('Support request does not exist.');
        }
        $messages = $model->messages;
        return $this->render('view', ['model' => $model, 'messages' => $messages]);
    }

    private function validateServiceOwnership(SupportTicket $model): bool
    {
        if (empty($model->service_type) || empty($model->service_id)) {
            return true;
        }
        $userId = (int) Yii::$app->user->id;
        return match ($model->service_type) {
            'domain' => Domains::find()->where(['id' => $model->service_id, 'user_id' => $userId])->exists(),
            'hosting' => Hosting::find()->where(['id' => $model->service_id, 'user_id' => $userId])->exists(),
            'maintenance' => Maintenance::find()->where(['id' => $model->service_id, 'user_id' => $userId])->exists(),
            default => false,
        };
    }

    public function actionReply(string $number)
    {
        $ticket = SupportTicket::findOne(['ticket_number' => $number, 'user_id' => (int) Yii::$app->user->id]);
        if ($ticket === null) {
            throw new NotFoundHttpException('Support request does not exist.');
        }
        if (in_array($ticket->status, [SupportTicket::STATUS_CLOSED], true)) {
            Yii::$app->session->setFlash('warning', 'This support request is closed.');
            return $this->redirect(['view', 'number' => $number]);
        }
        $message = trim((string) Yii::$app->request->post('message', ''));
        if ($message === '' || mb_strlen($message) > 10000) {
            Yii::$app->session->setFlash('error', 'Please enter a valid reply.');
            return $this->redirect(['view', 'number' => $number]);
        }
        $model = new SupportTicketMessage();
        $model->ticket_id = (int) $ticket->id;
        $model->sender_type = SupportTicketMessage::SENDER_CUSTOMER;
        $model->sender_id = (int) Yii::$app->user->id;
        $model->message = $message;
        $model->created_at = time();
        if (!$model->save()) {
            throw new \RuntimeException('Support reply could not be saved.');
        }
        $ticket->updated_at = time();
        if ($ticket->status === SupportTicket::STATUS_RESOLVED) {
            $ticket->status = SupportTicket::STATUS_OPEN;
        }
        $ticket->save(false, ['status', 'updated_at']);
        (new NotificationService())->create((int) $ticket->user_id, 'support', 'Reply Added', sprintf('A new reply has been added to ticket %s.', $ticket->ticket_number), '/support/view?number=' . $ticket->ticket_number);
        SupportTicketMailer::customerReply($ticket);
        AdminAlertService::supportCustomerReply($ticket->ticket_number, Yii::$app->user->identity->username);
        Yii::$app->session->setFlash('success', 'Your reply has been added.');
        return $this->redirect(['view', 'number' => $ticket->ticket_number]);
    }
}
