<?php

namespace common\models;

/**
 * This is the model class for table "site_setting".
 *
 * @property int $id
 * @property string $site_name
 * @property string $site_url
 * @property int $mobile_1
 * @property int|null $mobile_2
 * @property string $email_1
 * @property string|null $email_2
 * @property string|null $email_3
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $address_line_3
 * @property string|null $facebook
 * @property string|null $x
 * @property string|null $youtube
 * @property string|null $linkedin
 * @property string|null $instagram
 * @property int|null $updated_at
 */
class SiteSetting extends base\SiteSetting
{
    
    public function attributeLabels(): array
    {
        $labels = [
            'site_name' => 'Agency Corporate Name',
            'site_url' => 'Production Server Web Domain',
            'mobile_1' => 'Primary Technical Support Hotline',
            'mobile_2' => 'Alternative Backup Line',
            'email_1' => 'Primary Helpdesk Inbound Email',
        ];
        return \yii\helpers\ArrayHelper::merge(parent::attributeLabels(), $labels);
    }
}
