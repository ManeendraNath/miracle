<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\BlogCategory;
use common\models\BlogComment;
use common\models\BlogPost;
use common\models\BlogSubscriber;
use common\models\BlogTag;
use common\services\admin\AdminAlertService;
use common\services\BlogNewsletterService;
use frontend\components\FormGuard;
use Yii;
use yii\base\DynamicModel;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** The public blog: /blog, /blog/category/{slug}, /blog/{slug} and the RSS feed at /blog/feed. */
class BlogController extends Controller
{
    public function actionIndex(?string $category = null, ?string $tag = null, ?string $year = null, ?string $month = null, mixed $q = null): string
    {
        $search = is_string($q) ? mb_substr(trim($q), 0, 100) : '';
        $start = $year !== null && $month !== null && preg_match('/^\d{4}$/', $year) === 1 && preg_match('/^(0[1-9]|1[0-2])$/', $month) === 1
            ? (int) strtotime("$year-$month-01") : null;
        if (($year !== null || $month !== null) && $start === null) {
            throw new NotFoundHttpException('This month does not exist.');
        }
        $current = $category === null ? null : (BlogCategory::findOne(['slug' => $category]) ?? throw new NotFoundHttpException('This category does not exist.'));
        // Only a tag on a live post has a page: not one used only on drafts, nor one no post uses any more.
        $currentTag = $tag === null ? null : (BlogTag::find()->where(['slug' => $tag])
            ->andWhere(['id' => (new Query())->select('tag_id')->from('{{%blog_post_tag}}')->where(['post_id' => BlogPost::findPublic()->select('id')])])
            ->one() ?? throw new NotFoundHttpException('This tag does not exist.'));
        $query = BlogPost::findPublic()->with('category')->orderBy(['published_at' => SORT_DESC, 'id' => SORT_DESC]);
        if ($current !== null) {
            $query->andWhere(['category_id' => $current->id]);
        }
        if ($currentTag !== null) {
            $query->andWhere(['id' => (new Query())->select('post_id')->from('{{%blog_post_tag}}')->where(['tag_id' => $currentTag->id])]);
        }
        if ($start !== null) {
            $query->andWhere(['>=', 'published_at', $start])->andWhere(['<', 'published_at', strtotime('+1 month', $start)]);
        }
        if ($search !== '') {
            // The words of the text, not its markup ("strong", "href"). ponytail: LIKE over every post is fine for hundreds
            // of posts; a FULLTEXT index on a plain-text column once there are thousands.
            $query->andWhere(['or', ['like', 'title', $search], ['like', 'summary', $search], ['like', new Expression("REGEXP_REPLACE(body, '<[^>]*>', ' ')"), $search]]);
        }
        $filtered = $current !== null || $currentTag !== null || $start !== null || $search !== '';
        // The newest featured post leads the unfiltered blog, and is not repeated in the list below it.
        /** @var BlogPost|null $featured */
        $featured = !$filtered ? BlogPost::findPublic()->andWhere(['is_featured' => 1])->with('category')->orderBy(['published_at' => SORT_DESC, 'id' => SORT_DESC])->one() : null;
        if ($featured !== null) {
            $query->andWhere(['<>', 'id', $featured->id]);
        }

        FormGuard::shown('subscribe');

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 9]]),
            'categories' => BlogCategory::find()->where(['id' => BlogPost::findPublic()->select('category_id')])->orderBy(['name' => SORT_ASC])->all(),
            'current' => $current,
            'tag' => $currentTag,
            'featured' => $featured,
            'month' => $start,
            'search' => $search,
            // Months with posts, newest first: "2026-11" => 3.
            'archive' => array_count_values(array_map(static fn($at): string => date('Y-m', (int) $at), BlogPost::findPublic()->select('published_at')->orderBy(['published_at' => SORT_DESC])->column())),
            'popular' => $filtered ? [] : $this->popular(),
        ]);
    }

    /**
     * The most-read posts of the last 30 days.
     *
     * @return BlogPost[]
     */
    private function popular(): array
    {
        $ids = (new Query())->select('post_id')->from('{{%blog_post_view}}')
            ->where(['>=', 'day', date('Y-m-d', strtotime('-29 days'))])->andWhere(['post_id' => BlogPost::findPublic()->select('id')])
            ->groupBy('post_id')->orderBy(['COUNT(*)' => SORT_DESC, 'post_id' => SORT_DESC])->limit(5)->column();
        $posts = BlogPost::find()->where(['id' => $ids])->indexBy('id')->all();

        return array_values(array_filter(array_map(static fn($id) => $posts[$id] ?? null, $ids)));
    }

    public function actionView(string $slug): Response|string
    {
        $post = BlogPost::findPublic()->andWhere(['slug' => $slug])->with(['category', 'tags', 'author'])->one();
        if (!$post instanceof BlogPost) {
            // An old address of a post that moved: send links and search engines to where it is now.
            $moved = BlogPost::findPublic()->andWhere(['id' => \common\models\BlogPostRedirect::find()->select('post_id')->where(['old_slug' => $slug])])->one();
            if ($moved instanceof BlogPost) {
                // Browsers keep a 301 for good unless told otherwise; a day, as the address may change again.
                Yii::$app->response->headers->set('Cache-Control', 'public, max-age=86400');

                return $this->redirect(['/blog/view', 'slug' => $moved->slug], 301);
            }
            throw new NotFoundHttpException('This post does not exist.');
        }
        $comment = new BlogComment(['scenario' => BlogComment::SCENARIO_READER]);
        $request = Yii::$app->request;
        if ($request->isPost) {
            if (!$post->comments_open) {
                Yii::$app->session->setFlash('comment', ['danger', 'Comments were closed on this post before yours arrived, so it was not saved.']);
            } elseif ($this->saveComment($post, $comment)) {
                return $this->redirect(['/blog/view', 'slug' => $post->slug, '#' => 'comments']);
            }
        } else {
            \common\models\BlogPostView::record($post, $request);
        }
        // Per post, so another post opened in a second tab doesn't restart this form's timer.
        FormGuard::shown('comment-' . $post->id);
        FormGuard::shown('subscribe');
        $related = BlogPost::findPublic()->andWhere(['<>', 'id', $post->id])
            ->orderBy($post->category_id ? [new Expression('category_id = :category DESC', [':category' => $post->category_id]), 'published_at' => SORT_DESC] : ['published_at' => SORT_DESC])
            ->limit(3)->all();
        $older = BlogPost::findPublic()->andWhere(['or', ['<', 'published_at', $post->published_at], ['and', ['published_at' => $post->published_at], ['<', 'id', $post->id]]])
            ->orderBy(['published_at' => SORT_DESC, 'id' => SORT_DESC])->one();
        $newer = BlogPost::findPublic()->andWhere(['or', ['>', 'published_at', $post->published_at], ['and', ['published_at' => $post->published_at], ['>', 'id', $post->id]]])
            ->orderBy(['published_at' => SORT_ASC, 'id' => SORT_ASC])->one();

        return $this->render('view', [
            'post' => $post, 'related' => $related, 'older' => $older, 'newer' => $newer,
            'comments' => BlogComment::shownFor($post->id)->all(), 'comment' => $comment,
        ]);
    }

    /**
     * A reader's comment waits for staff, who get an alert when it is the only one waiting (the sidebar counts the
     * rest). What the bot guard catches, or looks like spam, is kept as Spam without an alert: people trip the guard
     * too (an extension filling every field, a fast paste), so staff can still find it. Messages go to the comment
     * section ("comment" flash), where the page lands after sending.
     */
    private function saveComment(BlogPost $post, BlogComment $comment): bool
    {
        $session = Yii::$app->session;
        $bot = FormGuard::isBot('comment-' . $post->id);
        if (!$comment->load(Yii::$app->request->post()) || !$comment->validate()) {
            return false;
        }
        $ip = Yii::$app->request->userIP;
        if (BlogComment::tooManyFrom($ip)) {
            $session->setFlash('comment', ['danger', 'You have sent several comments in the last few minutes. Please wait ' . BlogComment::RATE_MINUTES . ' minutes and send it again.']);

            return false;
        }
        $comment->post_id = $post->id;
        $comment->ip = $ip;
        $comment->status = $bot || $comment->looksLikeSpam() ? BlogComment::STATUS_SPAM : BlogComment::STATUS_PENDING;
        $othersWaiting = BlogComment::onLivePosts()->andWhere(['status' => BlogComment::STATUS_PENDING])->exists();
        if (!$comment->save(false)) {
            $session->setFlash('comment', ['danger', 'Your comment could not be saved. Please try again.']);

            return false;
        }
        if ($comment->status === BlogComment::STATUS_PENDING && !$othersWaiting) {
            try {
                AdminAlertService::blogCommentWaiting($post->title, $comment->author_name);
            } catch (\Throwable $e) {
                Yii::error(['message' => 'Blog comment admin alert failed.', 'commentId' => (int) $comment->id, 'error' => $e->getMessage()], __METHOD__);
            }
        }
        $session->setFlash('comment', ['success', 'Thank you. Your comment will appear here once we have read it.']);

        return true;
    }

    /** The newsletter sign-up: a confirmation email first (double opt-in). */
    public function actionSubscribe(): Response|string
    {
        // Not the record itself: its "email is unique" rule would say who is subscribed.
        $model = (new DynamicModel(['email' => '']))
            ->addRule('email', 'trim')->addRule('email', 'required', ['message' => 'Enter your email address.'])
            ->addRule('email', 'email', ['message' => 'Enter a valid email address.'])->addRule('email', 'string', ['max' => 255]);
        $request = Yii::$app->request;
        if ($request->isPost) {
            $session = Yii::$app->session;
            $sent = 'Almost done: we sent you an email with a link to confirm. It can take a few minutes; look in your spam folder if it doesn\'t arrive.';
            if (FormGuard::isBot('subscribe')) {
                // Nothing kept or sent: a confirmation email must not go to an address a bot typed in.
                $session->setFlash('subscribe', ['success', $sent]);

                return $this->redirect(['subscribe']);
            }
            if ($model->load($request->post(), 'BlogSubscriber') && $model->validate()) {
                if (BlogSubscriber::overLimit($request->userIP)) {
                    $session->setFlash('subscribe', ['danger', 'Too many sign-ups from your connection in the last hour. Please try again later.']);
                } else {
                    try {
                        $subscriber = BlogSubscriber::signUp((string) $model->getAttributes(['email'])['email'], $request->userIP);
                        if ($subscriber !== null) {
                            if (!BlogNewsletterService::sendConfirmation($subscriber)) {
                                throw new \RuntimeException('The mailer did not send it.');
                            }
                            $subscriber->confirmationSent();
                        }
                        $session->setFlash('subscribe', ['success', $sent]);

                        return $this->redirect(['subscribe']);
                    } catch (\Throwable $e) {
                        Yii::error(['message' => 'Newsletter sign-up failed.', 'error' => $e->getMessage()], __METHOD__);
                        $session->setFlash('subscribe', ['danger', 'We could not send the confirmation email just now. Please try again in a few minutes.']);
                    }
                }
            }
        }
        FormGuard::shown('subscribe');

        return $this->render('subscribe', ['model' => $model]);
    }

    /**
     * The link in the confirmation email. Opening it shows a button: link checkers in mail systems open links, and
     * must not subscribe anyone. The POST subscribes a waiting sign-up only, never one that unsubscribed.
     */
    public function actionConfirm(mixed $token = null): string
    {
        $subscriber = $this->findSubscriber($token);
        if (!Yii::$app->request->isPost) {
            return $this->render('confirm', ['subscriber' => $subscriber]);
        }
        if (!$subscriber->confirm()) {
            return $this->render('newsletter-done', [
                'title' => 'This link has expired',
                'text' => $subscriber->email . ' unsubscribed after this link was sent. To get new posts again, subscribe on the blog.',
            ]);
        }

        return $this->render('newsletter-done', [
            'title' => 'You are subscribed',
            'text' => 'Thank you. New posts will come to ' . $subscriber->email . '. Every email has a link to unsubscribe.',
        ]);
    }

    /**
     * The link in every newsletter. Opening it asks first (link checkers in mail systems open links); the button, or a
     * mail app's one-click unsubscribe (a POST without our form, so no CSRF token), unsubscribes.
     */
    public function actionUnsubscribe(mixed $token = null): string
    {
        $subscriber = $this->findSubscriber($token);
        if (!Yii::$app->request->isPost) {
            return $this->render('unsubscribe', ['subscriber' => $subscriber]);
        }
        $subscriber->unsubscribe();

        return $this->render('newsletter-done', [
            'title' => 'You are unsubscribed',
            'text' => $subscriber->email . ' will get no more emails from the blog. Changed your mind? You can subscribe again at any time.',
        ]);
    }

    #[\Override]
    public function beforeAction($action): bool
    {
        if ($action->id === 'unsubscribe') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    private function findSubscriber(mixed $token): BlogSubscriber
    {
        $subscriber = is_string($token) && $token !== '' ? BlogSubscriber::findOne(['token' => $token]) : null;

        return $subscriber ?? throw new NotFoundHttpException('This link is not valid. Copy the whole link from the email, or subscribe again.');
    }

    /** RSS 2.0 of the 20 newest posts. */
    public function actionFeed(): Response
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/rss+xml; charset=UTF-8');
        $response->content = $this->renderPartial('feed', ['posts' => BlogPost::findPublic()->andWhere(['noindex' => 0])->orderBy(['published_at' => SORT_DESC])->limit(20)->all()]);

        return $response;
    }
}
