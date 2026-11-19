<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Admin;
use common\models\BlogComment;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** Readers' comments on blog posts: approve, mark as spam, delete, or reply on the website. */
class BlogCommentController extends BaseController
{
    public function actionIndex(?string $status = null): string
    {
        // Waiting first: that's the work. "all" shows every reader comment.
        $filter = match (true) {
            $status === 'all' => null,
            $status !== null && ctype_digit($status) && isset(BlogComment::statuses()[(int) $status]) => (int) $status,
            default => BlogComment::STATUS_PENDING,
        };
        $query = BlogComment::onLivePosts()->with(['post', 'replies'])->orderBy(['id' => SORT_DESC]);
        if ($filter !== null) {
            $query->andWhere(['status' => $filter]);
        }

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 30]]),
            'filter' => $filter,
            'counts' => BlogComment::onLivePosts()->select(['COUNT(*)', 'status'])->groupBy('status')->indexBy('status')->column(),
        ]);
    }

    public function actionApprove(int $id): Response
    {
        return $this->setStatus($id, BlogComment::STATUS_APPROVED, 'Comment approved: it shows on the post.');
    }

    public function actionSpam(int $id): Response
    {
        return $this->setStatus($id, BlogComment::STATUS_SPAM, 'Comment marked as spam: it does not show on the post.');
    }

    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Comment deleted.');

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    /** A staff answer shown under the comment; replying approves the comment too. */
    public function actionReply(int $id): Response|string
    {
        $comment = $this->findModel($id);
        if ($comment->parent_id !== null) {
            throw new NotFoundHttpException('Replies are answered on the comment they belong to.');
        }
        /** @var Admin $admin */
        $admin = Yii::$app->user->identity;
        $reply = new BlogComment();
        if ($reply->load(Yii::$app->request->post())) {
            $reply->setAttributes([
                'post_id' => $comment->post_id,
                'parent_id' => $comment->id,
                'admin_id' => $admin->id,
                'author_name' => $admin->authorName() ?? (Yii::$app->params['senderName'] ?? \common\components\CustomYii::businessName()),
                'author_email' => null,
                'ip' => null,
                'status' => BlogComment::STATUS_APPROVED,
            ], false);
            $saved = BlogComment::getDb()->transaction(static function () use ($reply, $comment): bool {
                if (!$reply->save()) {
                    return false;
                }
                $comment->updateAttributes(['status' => BlogComment::STATUS_APPROVED, 'updated_at' => time()]);

                return true;
            });
            if ($saved) {
                Yii::$app->session->setFlash('success', 'Reply posted. The comment and your reply show on the post.');

                return $this->redirect(['index', 'status' => BlogComment::STATUS_APPROVED]);
            }
        }

        return $this->render('reply', ['comment' => $comment, 'reply' => $reply]);
    }

    private function setStatus(int $id, int $status, string $message): Response
    {
        $this->findModel($id)->updateAttributes(['status' => $status, 'updated_at' => time()]);
        Yii::$app->session->setFlash('success', $message);

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    private function findModel(int $id): BlogComment
    {
        return BlogComment::findOne($id) ?? throw new NotFoundHttpException('The requested page does not exist.');
    }
}
