<?php

namespace common\models;

/**
 * This is the model class for table "lead".
 *
 * @property int $id
 * @property string $lead_name
 * @property string|null $phone_number
 * @property string|null $source
 * @property string $status
 * @property int $created_at
 * @property int $updated_at
 */
class Lead extends base\Lead
{

    public static function getSources()
    {
        return [
            'Google Search' => 'Google Search',
            'Social Media' => 'Social Media Referral',
            'Direct Client' => 'Direct / Inbound Call',
            'Frontend Request' => 'Website Audit Form',
        ];
    }
}
