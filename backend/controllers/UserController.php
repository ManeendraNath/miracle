<?php

namespace backend\controllers;

use Yii;
use backend\models\searches\UserSearch;
use common\models\User;
use yii\web\NotFoundHttpException;

class UserController extends BaseController
{
    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * ⚡ SECURE ONE-CLICK USER ACTIVATION TOGGLE
     * Switches the profile status between Active (10) and Inactive (0) dynamically.
     */
    public function actionToggleStatus($id)
    {
        $model = $this->findModel($id);

        // Suspended (not Inactive): Inactive is the unverified-email state, which
        // the customer could flip back to Active via the verification link.
        if ((int) $model->status === User::getStatusActive()) {
            $model->status = User::getStatusSuspended();
            Yii::$app->session->setFlash('warning', "Account for profile '{$model->username}' has been suspended successfully.");
        } else {
            $model->status = User::getStatusActive();
            Yii::$app->session->setFlash('success', "Account for profile '{$model->username}' has been activated and unlocked successfully!");
        }

        $model->updated_at = time(); // save(false) skips the validation step that refreshes it
        $model->save(false); // Bypasses password validation restrictions during simple status toggles
        $this->warnAboutOpenBusiness($model);
        return $this->redirect(['index']);
    }

    /**
     * Displays a single User model.
     * @param int $id System ID Reference
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new User();
        if (!$this->request->isPost) {
            $model->status = User::getStatusActive(); // staff-created customers verify nothing, so they start usable
        }
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'User created. Now add the billing details, or leave them for the customer to complete after logging in.');
            $this->sendWelcome($model);
            return $this->redirect(['/customer-billing-profile/create', 'user_id' => $model->id]);
        }
        $model->clearPlainPassword();

        return $this->render('create', ['model' => $model]);
    }

    /** They didn't sign up, so nothing else tells them they have an account, and its one-month clock is running. */
    private function sendWelcome(User $customer): void
    {
        if ((int) $customer->status !== User::getStatusActive()) {
            return;
        }
        try {
            $sent = Yii::$app->mailer
                ->compose(['html' => '@common/mail/account-created-html', 'text' => '@common/mail/account-created-text'], ['client' => $customer])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo($customer->email)
                ->setSubject('Your customer account is ready')
                ->send();
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Welcome email failed.', 'userId' => (int) $customer->id, 'error' => $e->getMessage()], __METHOD__);
            $sent = false;
        }
        if (!$sent) {
            Yii::$app->session->addFlash('warning', 'The welcome email could not be sent: please tell the customer their account exists.');
        }
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id System ID Reference
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->clearPlainPassword();

        // Below Admin only the status can change: username, email or password would take over the customer's login.
        if (self::canAccess(Yii::$app->user->identity, 'user', 'credentials')) {
            $loaded = $model->load(Yii::$app->request->post());
        } else {
            $posted = Yii::$app->request->post($model->formName());
            $loaded = is_array($posted);
            if ($loaded) {
                $model->setAttributes(array_intersect_key($posted, ['status' => true]));
            }
        }
        if ($loaded && $model->save()) {
            Yii::$app->session->setFlash('success', 'User updated successfully.');
            $this->warnAboutOpenBusiness($model);
            return $this->redirect(['view', 'id' => $model->id]);
        }
        $model->clearPlainPassword();

        return $this->render('update', ['model' => $model]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id System ID Reference
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */

    /** Billing doesn't stop by itself for a suspended or inactive customer, and a deleted one's invoices stay in Outstanding. */
    private function warnAboutOpenBusiness(User $model): void
    {
        $status = (int) $model->status;
        if ($status === User::getStatusActive()) {
            return;
        }
        $services = 0;
        foreach ([\common\models\Domains::class, \common\models\Hosting::class, \common\models\Maintenance::class] as $class) {
            $services += (int) $class::find()->where(['user_id' => (int) $model->id, 'status' => 1])->count();
        }
        $invoices = (int) \common\models\Invoice::find()->where(['user_id' => (int) $model->id, 'status' => [\common\models\Invoice::STATUS_DRAFT, \common\models\Invoice::STATUS_UNPAID, \common\models\Invoice::STATUS_PARTIALLY_PAID]])->count();
        if ($services === 0 && $invoices === 0) {
            return;
        }
        $held = "{$services} active service(s) and {$invoices} open invoice(s)";
        Yii::$app->session->addFlash('warning', $status === User::getStatusDeleted()
            ? "This customer has {$held}. Renewals and reminders have stopped, but open invoices stay in Outstanding until cancelled or written off."
            : "This customer can't log in but has {$held}: renewals and reminders continue. Disable the services or cancel the invoices if they should stop.");
    }

    /**
     * A one-off email to the customer (any subject: a corrected invoice, a service notice, a reply), written by staff
     * from a prefilled draft and sent in the standard email design with a copy to billing. With $invoice the draft is
     * about that invoice (billing staff only).
     */
    public function actionEmail(int $id, ?int $invoice = null)
    {
        $client = $this->findModel($id);
        $about = null;
        if ($invoice !== null) {
            $about = \common\models\Invoice::findOne(['id' => $invoice, 'user_id' => (int) $client->id]);
            if ($about === null || !self::canAccess(Yii::$app->user->identity, 'invoice')) {
                throw new NotFoundHttpException('The requested page does not exist.');
            }
            // Held for review, or cancelled while held: the customer never got it and it only has a temporary number.
            // (A Draft held before held invoices got TMP- numbers has a real one.)
            if ($about->status === \common\models\Invoice::STATUS_DRAFT || str_starts_with((string) $about->invoice_number, 'TMP-')) {
                Yii::$app->session->setFlash('error', $about->status === \common\models\Invoice::STATUS_DRAFT
                    ? 'This invoice is waiting for review: the customer can\'t see it yet. Send it to the customer first.'
                    : 'This invoice was cancelled before it was sent, so the customer never received it.');

                return $this->redirect(['/invoice/view', 'number' => $about->invoice_number]);
            }
        }
        $mail = new \common\services\mail\CustomerEmailService();
        $templates = $mail->templates($client, $about);
        $first = reset($templates);
        $form = new \yii\base\DynamicModel(['subject' => $first['subject'], 'message' => $first['message']]);
        $form->addRule(['subject', 'message'], 'trim')
            ->addRule(['subject', 'message'], 'required')
            ->addRule('subject', 'string', ['max' => 200])
            ->addRule('message', 'string', ['max' => 5000])
            ->addRule(['subject', 'message'], function (string $attribute) use ($form): void {
                if (str_contains((string) $form[$attribute], '[EDIT')) {
                    $form->addError($attribute, 'Replace the [EDIT: ...] part with your own words before sending.');
                }
            });
        $back = $about !== null ? ['/invoice/view', 'number' => $about->invoice_number] : ['view', 'id' => $client->id];
        if ($this->request->isPost && $form->load($this->request->post()) && $form->validate()) {
            try {
                $mail->send($client, (string) $form['subject'], (string) $form['message'], $about !== null);
                Yii::info(['message' => 'Staff emailed a customer.', 'userId' => (int) $client->id, 'invoiceId' => $about?->id, 'adminId' => Yii::$app->user->id, 'subject' => $form['subject']], __METHOD__);
                Yii::$app->session->setFlash('success', 'Email sent to ' . $client->email . '.');

                return $this->redirect($back);
            } catch (\Throwable $e) {
                Yii::error(['message' => 'Customer email failed.', 'userId' => (int) $client->id, 'error' => $e->getMessage()], __METHOD__);
                Yii::$app->session->setFlash('error', $e instanceof \DomainException ? $e->getMessage() : 'The email could not be sent. Please try again.');
            }
        }

        return $this->render('email', ['client' => $client, 'invoice' => $about, 'form' => $form, 'back' => $back, 'templates' => $templates]);
    }

    /**
     * One email to all active customers or to the ones picked (one or more). Each gets their own copy, greeted by
     * name, so no customer sees another's address; billing gets one copy listing who got it.
     * ponytail: sent one by one in this request; queue it if the customer list grows to hundreds.
     */
    public function actionEmailMany()
    {
        $mail = new \common\services\mail\CustomerEmailService();
        $templates = $mail->templates(null);
        $first = reset($templates);
        $form = new \yii\base\DynamicModel(['recipients' => 'selected', 'subject' => $first['subject'], 'message' => $first['message']]);
        $form->defineAttribute('user_ids');
        $form->addRule(['subject', 'message'], 'trim')
            ->addRule(['recipients', 'subject', 'message'], 'required')
            ->addRule('recipients', 'in', ['range' => ['all', 'selected']])
            ->addRule('user_ids', 'each', ['rule' => ['integer']])
            ->addRule('subject', 'string', ['max' => 200])
            ->addRule('message', 'string', ['max' => 5000])
            ->addRule(['subject', 'message'], function (string $attribute) use ($form): void {
                if (str_contains((string) $form[$attribute], '[EDIT')) {
                    $form->addError($attribute, 'Replace the [EDIT: ...] part with your own words before sending.');
                }
            })
            ->addRule('user_ids', function () use ($form): void {
                if ($form['recipients'] === 'selected' && empty($form['user_ids'])) {
                    $form->addError('user_ids', 'Choose at least one client.');
                }
            }, ['skipOnEmpty' => false]);
        $active = static fn (): \yii\db\ActiveQuery => User::find()->where(['status' => User::getStatusActive()])->andWhere(['<>', 'email', '']);
        if ($this->request->isPost && $form->load($this->request->post()) && $form->validate()) {
            $query = $active();
            if ($form['recipients'] === 'selected') {
                $query->andWhere(['id' => array_map('intval', (array) $form['user_ids'])]);
            }
            $sent = [];
            $failed = [];
            foreach ($query->orderBy(['id' => SORT_ASC])->all() as $client) {
                try {
                    $mail->send($client, (string) $form['subject'], (string) $form['message'], false, false);
                    $sent[] = $client->username;
                } catch (\Throwable $e) {
                    $failed[] = $client->username;
                    Yii::error(['message' => 'Customer email failed.', 'userId' => (int) $client->id, 'error' => $e->getMessage()], __METHOD__);
                }
            }
            Yii::info(['message' => 'Staff emailed customers.', 'adminId' => Yii::$app->user->id, 'subject' => $form['subject'], 'sent' => count($sent), 'failed' => count($failed)], __METHOD__);
            if ($sent !== []) {
                try {
                    $mail->sendCopy((string) $form['subject'], (string) $form['message'], $sent);
                } catch (\Throwable $e) {
                    Yii::error(['message' => 'Billing copy of a customer email failed.', 'error' => $e->getMessage()], __METHOD__);
                }
            }
            if ($failed !== []) {
                Yii::$app->session->setFlash('warning', 'Sent to ' . count($sent) . ' client(s). Not sent to: ' . implode(', ', $failed) . '. Try them again.');
            } elseif ($sent === []) {
                Yii::$app->session->setFlash('error', 'No active client with an email address matched.');
            } else {
                Yii::$app->session->setFlash('success', 'Email sent to ' . count($sent) . ' client(s).');
            }

            return $this->redirect(['email-many']);
        }
        $clients = [];
        foreach ($active()->orderBy(['username' => SORT_ASC])->all() as $client) {
            $clients[$client->id] = $client->username . ' (' . $client->email . ')';
        }

        return $this->render('email-many', ['form' => $form, 'templates' => $templates, 'clients' => $clients]);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id System ID Reference
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
