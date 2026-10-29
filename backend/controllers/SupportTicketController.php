<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\searches\SupportTicketSearch;
use common\models\SupportTicket;
use common\models\SupportTicketMessage;
use common\services\notification\NotificationService;
use common\services\SupportTicketMailer;
use Yii;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

final class SupportTicketController extends BaseController
{

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'reply' => ['POST'],
                'resolve' => ['POST'],
                'reopen' => ['POST'],
                'close' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionIndex()
    {
        $searchModel = new SupportTicketSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', ['searchModel' => $searchModel, 'dataProvider' => $dataProvider]);
    }

    public function actionView(string $number)
    {
        $model = $this->findModelByNumber($number);
        return $this->render('view', ['model' => $model]);
    }

    public function actionReply(string $number)
    {
        $ticket = $this->findModelByNumber($number);
        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            Yii::$app->session->setFlash('warning', 'Closed support requests ' . 'cannot be updated.');
            return $this->redirect(['view', 'number' => $ticket->ticket_number]);
        }
        $message = trim((string) Yii::$app->request->post('message', ''));
        if ($message === '' || mb_strlen($message) > 10000) {
            Yii::$app->session->setFlash('error', 'Please enter a valid reply.');
            return $this->redirect(['view', 'number' => $ticket->ticket_number]);
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $reply = new SupportTicketMessage();
            $reply->ticket_id = (int) $ticket->id;
            $reply->sender_type = SupportTicketMessage::SENDER_ADMIN;
            $reply->sender_id = (int) Yii::$app->user->id;
            $reply->message = $message;
            $reply->created_at = time();
            if (!$reply->save()) {
                throw new \RuntimeException('Support reply could not ' . 'be saved.');
            }
            if ($ticket->status === SupportTicket::STATUS_OPEN || $ticket->status === SupportTicket::STATUS_RESOLVED) {
                $ticket->status = SupportTicket::STATUS_IN_PROGRESS;
            }
            $ticket->updated_at = time();
            if (!$ticket->save(false, ['status', 'updated_at'])) {
                throw new \RuntimeException('Support ticket could not ' . 'be updated.');
            }
            (new NotificationService())
                    ->create(
                            (int) $ticket->user_id,
                            'support',
                            'Support Team Replied',
                            'A new response was added to ' . 'support request ' . $ticket->ticket_number . '.',
                            '/support/view?number=' . rawurlencode($ticket->ticket_number)
                    );
            SupportTicketMailer::adminReply($ticket);
            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            Yii::error(['message' => 'Admin support reply failed.', 'ticketId' => (int) $ticket->id, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->session->setFlash('error', 'The support reply ' . 'could not be saved.');
            return $this->redirect(['view', 'number' => $ticket->ticket_number]);
        }
        Yii::$app->session->setFlash('success', 'Reply sent successfully.');
        return $this->redirect(['view', 'number' => $ticket->ticket_number]);
    }

    public function actionResolve(string $number)
    {
        $ticket = $this->findModelByNumber($number);
        $this->changeStatus($ticket, SupportTicket::STATUS_RESOLVED);
        try {
            (new NotificationService())->create(
                    (int) $ticket->user_id,
                    'support',
                    'Support Request Resolved',
                    'Support request ' . $ticket->ticket_number . ' has been marked as resolved.',
                    '/support/view?number=' . rawurlencode($ticket->ticket_number)
            );
            SupportTicketMailer::ticketResolved($ticket);
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
        }

        Yii::$app->session->setFlash('success', 'Support request marked as resolved.');
        return $this->redirect(['view', 'number' => $ticket->ticket_number]);
    }

    public function actionReopen(string $number)
    {
        $ticket = $this->findModelByNumber($number);
        $this->changeStatus($ticket, SupportTicket::STATUS_OPEN);
        Yii::$app->session->setFlash('success', 'Support request reopened.');
        return $this->redirect(['view', 'number' => $ticket->ticket_number]);
    }

    public function actionClose(string $number)
    {
        $ticket = $this->findModelByNumber($number);
        $ticket->status = SupportTicket::STATUS_CLOSED;
        $ticket->closed_at = time();
        $ticket->updated_at = time();
        if (!$ticket->save(false, ['status', 'closed_at', 'updated_at'])) {
            throw new \RuntimeException('Support request could not be closed.');
        }
        try {
            (new NotificationService())->create(
                    (int) $ticket->user_id,
                    'support',
                    'Support Request Closed',
                    'Support request ' . $ticket->ticket_number . ' has been closed.',
                    '/support/view?number=' . rawurlencode($ticket->ticket_number)
            );
            SupportTicketMailer::ticketClosed($ticket);
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
        }
        Yii::$app->session->setFlash('success', 'Support request closed.');
        return $this->redirect(['view', 'number' => $ticket->ticket_number]);
    }

    private function changeStatus(SupportTicket $ticket, string $status): void
    {
        $ticket->status = $status;
        $ticket->closed_at = null;
        $ticket->updated_at = time();
        if (!$ticket->save(false, ['status', 'closed_at', 'updated_at'])) {
            throw new \RuntimeException('Support request status ' . 'could not be updated.');
        }
    }

    private function findModelByNumber(string $number): SupportTicket
    {
        $model = SupportTicket::find()->with(['user', 'messages'])->where(['ticket_number' => $number])->one();
        if ($model !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Support request does not exist.');
    }
}
