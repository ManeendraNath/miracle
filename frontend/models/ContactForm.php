<?php

declare(strict_types=1);

namespace frontend\models;

use yii\base\Model;
use yii\mail\MailerInterface;

/**
 * ContactForm is the model behind the contact form.
 */
class ContactForm extends Model
{
    use \common\models\traits\ScalarInputTrait;

    public string $name = '';
    public string $email = '';
    public string $subject = '';
    public string $body = '';
    public string $verifyCode = '';
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // name, email, subject and body are required
            [['name', 'email', 'subject', 'body'], 'required'],
            // email has to be a valid email address
            ['email', 'email'],
            // As the Leads columns they are kept in, so an enquiry is not cut short silently.
            ['name', 'string', 'max' => 150],
            ['subject', 'string', 'max' => 255],
            ['body', 'string', 'max' => 10000],
            // verifyCode needs to be entered correctly
            ['verifyCode', 'captcha'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'name' => 'Full Name',
            'email' => 'Email Address',
            'subject' => 'Inquiry Subject',
            'body' => 'Message Body',
            'verifyCode' => 'Verification Code',
        ];
    }

    /**
     * Saved as Spam with no email or alert: random letter-and-digit strings in two or more fields (bots), or an
     * opt-out line next to a link, which mass outreach carries and a real enquiry doesn't.
     * Deliberately narrow: "SEO services" or "free trial" can be part of a real brief.
     */
    public function looksLikeSpam(): bool
    {
        $random = static fn(string $value): bool => mb_strlen(trim($value)) >= 12 && !preg_match('/\s/', trim($value))
            && preg_match('/\d/', $value) === 1 && preg_match('/[a-z]/i', $value) === 1;
        if (count(array_filter([$this->name, $this->subject, $this->body], $random)) >= 2) {
            return true;
        }

        return preg_match('/\b(unsubscribe|opt[ -]?out)\b/i', $this->body) === 1 && preg_match('~https?://~i', $this->body) === 1;
    }

    /** Keeps the enquiry in the admin panel (Leads), whatever happens to the email. */
    public function saveAsLead(bool $spam = false): bool
    {
        $lead = new \common\models\Lead();
        $lead->setAttributes([
            'lead_name' => mb_substr($this->name, 0, 150),
            'email' => mb_substr($this->email, 0, 255),
            'source' => \common\models\Lead::SOURCE_CONTACT_FORM,
            'subject' => mb_substr($this->subject, 0, 255),
            'message' => $this->body,
            'status' => $spam ? \common\models\Lead::STATUS_SPAM : 'Pending',
            'created_at' => time(),
            'updated_at' => time(),
        ], false);

        return $lead->save();
    }

    /**
     * Sends an email to the specified email address using the information collected by this model.
     *
     * @param MailerInterface $mailer the mailer component.
     * @param string $email the target email address.
     * @param string $senderEmail the sender email address.
     * @param string $senderName the sender name.
     *
     * @return bool whether the email was sent.
     */
    public function sendEmail(MailerInterface $mailer, string $email, string $senderEmail, string $senderName): bool
    {
        return $mailer->compose(['html' => 'enquiry-html', 'text' => 'enquiry-text'], [
                'eyebrow' => 'Contact form',
                'title' => $this->subject,
                'rows' => [['Name', $this->name], ['Email', $this->email]],
                'message' => $this->body,
                'replyEmail' => $this->email,
                'replySubject' => $this->subject,
            ])
            ->setTo($email)
            ->setFrom([$senderEmail => $senderName])
            ->setReplyTo([$this->email => $this->name])
            ->setSubject($this->subject)
            ->send();
    }
}
