<?php

namespace common\models\base;

use Yii;

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
class SiteSetting extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'site_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mobile_2', 'email_2', 'email_3', 'address_line_1', 'address_line_2', 'address_line_3', 'facebook', 'x', 'youtube', 'linkedin', 'instagram', 'updated_at'], 'default', 'value' => null],
            [['site_name', 'site_url', 'mobile_1', 'email_1'], 'required'],
            [['mobile_1', 'mobile_2', 'updated_at'], 'integer'],
            [['site_name', 'site_url', 'email_1', 'email_2', 'email_3', 'address_line_1', 'address_line_2', 'address_line_3', 'facebook', 'x', 'youtube', 'linkedin', 'instagram'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'site_name' => 'Site Name',
            'site_url' => 'Site Url',
            'mobile_1' => 'Mobile 1',
            'mobile_2' => 'Mobile 2',
            'email_1' => 'Email 1',
            'email_2' => 'Email 2',
            'email_3' => 'Email 3',
            'address_line_1' => 'Address Line 1',
            'address_line_2' => 'Address Line 2',
            'address_line_3' => 'Address Line 3',
            'facebook' => 'Facebook',
            'x' => 'X',
            'youtube' => 'Youtube',
            'linkedin' => 'Linkedin',
            'instagram' => 'Instagram',
            'updated_at' => 'Updated At',
        ];
    }

}
