<?php

namespace common\models;

/**
 * This is the model class for table "lead".
 *
 * @property int $id
 * @property string $lead_name
 * @property string|null $phone_number
 * @property string|null $email
 * @property string|null $source
 * @property string|null $subject
 * @property string|null $message
 * @property string $status
 * @property int $created_at
 * @property int $updated_at
 */
class Lead extends base\Lead
{
    /** The admin "Update Status" form: only the status comes from the form. */
    public const SCENARIO_STATUS = 'status';
    public const SOURCE_CONTACT_FORM = 'Contact Form';

    /** Caught by ContactForm::looksLikeSpam(): saved without alerting anyone. */
    public const STATUS_SPAM = 'Spam';

    public static function getStatuses(): array
    {
        return [
            'Pending' => 'Pending Review',
            'In Progress' => 'In Progress',
            'Completed' => 'Completed',
            'Rejected' => 'Rejected',
            self::STATUS_SPAM => 'Spam',
        ];
    }

    public function scenarios(): array
    {
        return array_merge(parent::scenarios(), [self::SCENARIO_STATUS => ['status']]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // A status outside the list (older data) may be kept, just not newly chosen.
            ['status', 'in', 'range' => array_keys(self::getStatuses()), 'on' => self::SCENARIO_STATUS, 'when' => fn (self $m): bool => $m->isAttributeChanged('status')],
        ]);
    }

    public static function getSources()
    {
        return [
            'Google Search' => 'Google Search',
            'Social Media' => 'Social Media Referral',
            'Direct Client' => 'Direct / Inbound Call',
            'Frontend Request' => 'Website Audit Form',
            self::SOURCE_CONTACT_FORM => 'Website Contact Form',
        ];
    }
}
