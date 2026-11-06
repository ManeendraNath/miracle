<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\LoginForm;
use common\models\AuditRequest;
use common\components\CustomYii;
use common\services\UserLoginHistoryService;
use frontend\models\ContactForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResendVerificationEmailForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use common\services\admin\AdminAlertService;
use yii\captcha\CaptchaAction;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\mail\MailerInterface;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly MailerInterface $mailer,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
            'captcha' => [
                'class' => CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        return $this->render('index');
    }

    /**
     * Logs in a user.
     *
     * @return string|Response
     */
    public function actionLogin(): string|Response
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect([
            '/dashboard/index',
            ]);
        }

        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            (new UserLoginHistoryService())->record((int) Yii::$app->user->id);
            /** @var \common\models\User $identity */
            $identity = Yii::$app->user->identity;
            return \frontend\components\ProfileGate::mustCompleteFirst($identity) ? $this->redirect(['/account/profile']) : $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
                    'model' => $model,
        ]);
    }

    /**
     * Logs out the current user.
     *
     * @return Response
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Action handling standard text messages.
     */
    public function actionContact()
    {
        $model = new ContactForm();
        // A hosting plan's Enquire button fills in the subject.
        $subject = Yii::$app->request->get('subject');
        if (is_string($subject)) {
            $model->subject = mb_substr($subject, 0, 255);
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $adminEmail = CustomYii::getSetting('email_1', 'info@miraclewebtechnologies.com');

            // Sent from our own address (visitor addresses fail SPF/DMARC); Reply-To reaches the visitor.
            $saved = false;
            try {
                $saved = $model->saveAsLead();
            } catch (\Throwable $e) {
                Yii::error(['message' => 'Contact enquiry could not be saved.', 'error' => $e->getMessage()], __METHOD__);
            }
            $sent = false;
            try {
                $sent = $model->sendEmail(Yii::$app->mailer, $adminEmail, Yii::$app->params['senderEmail'], Yii::$app->params['senderName']);
            } catch (\Throwable $e) {
                Yii::error(['message' => 'Contact form email failed.', 'error' => $e->getMessage()], __METHOD__);
            }

            if ($saved) {
                try {
                    AdminAlertService::leadCreated($model->name, $model->email);
                } catch (\Throwable $e) {
                    Yii::error(['message' => 'Contact enquiry admin alert failed.', 'error' => $e->getMessage()], __METHOD__);
                }
            }

            // Lost only if both failed: then keep what they typed, it exists nowhere else.
            if ($saved || $sent) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond shortly.');
                return $this->refresh();
            }
            $model->verifyCode = '';
            Yii::$app->session->setFlash('error', 'Your message could not be sent. Please try again, or call or email us using the details on this page.');
        }

        return $this->render('contact', [
                    'model' => $model,
        ]);
    }

    /**
     * Action handling high-end infrastructure audits data storage.
     */
    public function actionAudit()
    {
        $model = new AuditRequest(['scenario' => AuditRequest::SCENARIO_PUBLIC]);
        if ($model->load(Yii::$app->request->post())) {
            $model->user_ip = Yii::$app->request->userIP;
            $model->status = AuditRequest::STATUS_PENDING;
            if ($model->save()) {
                $adminEmail = CustomYii::getSetting('email_1', 'info@miraclewebtechnologies.com');

                // The request is already saved; a mail failure must not turn into a 500.
                try {
                    Yii::$app->mailer->compose(['html' => 'enquiry-html', 'text' => 'enquiry-text'], [
                                'eyebrow' => 'Audit request',
                                'title' => 'New audit request from ' . $model->name,
                                'rows' => [
                                    ['Name', $model->name],
                                    ['Email', $model->email],
                                    ['Phone', $model->phone],
                                    ['Website', $model->company_url],
                                    ['Framework', $model->current_framework],
                                    ['Hosting', $model->hosting_environment],
                                ],
                                'message' => (string) $model->message,
                                'replyEmail' => $model->email,
                                'replySubject' => 'Your audit request',
                            ])
                            ->setTo($adminEmail)
                            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                            ->setReplyTo([$model->email => $model->name])
                            ->setSubject('New Audit Request: ' . $model->name)
                            ->send();
                } catch (\Throwable $e) {
                    Yii::error(['message' => 'Audit request email failed.', 'auditRequestId' => (int) $model->id, 'error' => $e->getMessage()], __METHOD__);
                }
                try {
                    AdminAlertService::auditRequestCreated((string) $model->company_url);
                } catch (\Throwable $e) {
                    Yii::error(['message' => 'Audit request admin alert failed.', 'auditRequestId' => (int) $model->id, 'error' => $e->getMessage()], __METHOD__);
                }

                Yii::$app->session->setFlash('success', 'Your system matrix data has been securely logged. Our engineers are reviewing your case.');
                return $this->refresh();
            }
        }
        return $this->render('audit', ['model' => $model]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout(): string
    {
        return $this->render('about');
    }

    public function actionTerms(): string
    {
        return $this->render('terms');
    }

    public function actionPrivacy(): string
    {
        return $this->render('privacy');
    }

    public function actionRefundPolicy(): string
    {
        return $this->render('refund-policy');
    }

    public function actionShippingPolicy(): string
    {
        return $this->render('shipping-policy');
    }

    /**
     * Signs user up.
     *
     * @return string|Response
     */
    public function actionSignup(): string|Response
    {
        $model = new SignupForm();

        $signed = $model->load(Yii::$app->request->post()) && $model->signup(
            $this->mailer,
            Yii::$app->params['supportEmail'],
            Yii::$app->name,
        );

        if ($signed && !$model->verificationEmailSent) {
            Yii::$app->session->setFlash('warning', 'Your account was created, but we could not send the verification email. Please use "Resend verification email" in a few minutes.');
            return $this->redirect(['resend-verification-email']);
        }
        if ($signed) {
            Yii::$app->session->setFlash('success', 'Thank you for registration. Please check your inbox for verification email.');
            return $this->goHome();
        }

        return $this->render('signup', [
                    'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return string|Response
     */
    public function actionRequestPasswordReset(): string|Response
    {
        $model = new PasswordResetRequestForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            try {
                $sent = $model->sendEmail(
                    $this->mailer,
                    Yii::$app->params['supportEmail'],
                    Yii::$app->name,
                );
            } catch (\Throwable $e) {
                // Mail outage: answered like any other unsent email below.
                Yii::error(['message' => 'Account email failed.', 'error' => $e->getMessage()], __METHOD__);
                $sent = false;
            }

            if ($sent) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            }

            // Same response whether or not the address exists, so the form can't be used to discover accounts.
            Yii::warning(['message' => 'Account email not sent (unknown address or mail failure).', 'email' => $model->email], __METHOD__);
            Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
            return $this->goHome();
        }

        return $this->render('requestPasswordResetToken', [
                    'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return string|Response
     * @throws BadRequestHttpException
     */
    public function actionResetPassword(string $token): string|Response
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
                    'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionVerifyEmail(string $token): Response
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->verifyEmail()) {
            Yii::$app->session->setFlash('success', 'Your email has been confirmed! Log in to complete your billing details and start using your account. If you asked for a new link, use "Forgot password?" first.');
            return $this->redirect(['/site/login']);
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return string|Response
     */
    public function actionResendVerificationEmail(): string|Response
    {
        $model = new ResendVerificationEmailForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            try {
                $sent = $model->sendEmail(
                    $this->mailer,
                    Yii::$app->params['supportEmail'],
                    Yii::$app->name,
                );
            } catch (\Throwable $e) {
                // Mail outage: answered like any other unsent email below.
                Yii::error(['message' => 'Account email failed.', 'error' => $e->getMessage()], __METHOD__);
                $sent = false;
            }

            if ($sent) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }

            // Same response whether or not the address exists, so the form can't be used to discover accounts.
            Yii::warning(['message' => 'Account email not sent (unknown address or mail failure).', 'email' => $model->email], __METHOD__);
            Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
            return $this->goHome();
        }

        return $this->render('resendVerificationEmail', [
                    'model' => $model,
        ]);
    }
}
