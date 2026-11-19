<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "site_setting".
 *
 * @property int $id
 * @property string $site_name
 * @property string $site_url
 * @property string|null $tagline
 * @property string|null $services_line
 * @property string|null $grievance_name
 * @property string|null $grievance_email
 * @property int|null $founded_year
 * @property int $mobile_1
 * @property int|null $mobile_2
 * @property int|null $whatsapp
 * @property string|null $tawk_chat
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
 * @property int $review_invoices
 * @property int $razorpay_india
 * @property int $razorpay_abroad
 * @property int $paypal_abroad
 * @property int $usd_rate_auto
 * @property float $usd_rate_margin
 * @property float|null $usd_inr_rate
 * @property float|null $usd_market_rate
 * @property int|null $usd_rate_checked_at
 * @property int|null $usd_rate_fetched_at
 * @property float $paypal_fee_percent
 * @property float $paypal_fee_fixed_usd
 * @property string|null $google_site_verification
 * @property string|null $bing_site_verification
 * @property string|null $indexnow_key
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
            [['tagline', 'services_line', 'grievance_name', 'grievance_email', 'founded_year', 'mobile_2', 'whatsapp', 'tawk_chat', 'email_2', 'email_3', 'address_line_1', 'address_line_2', 'address_line_3', 'facebook', 'x', 'youtube', 'linkedin', 'instagram', 'updated_at', 'usd_inr_rate', 'usd_market_rate', 'usd_rate_checked_at', 'usd_rate_fetched_at', 'google_site_verification', 'bing_site_verification', 'indexnow_key'], 'default', 'value' => null],
            [['usd_rate_auto'], 'default', 'value' => 1],
            [['paypal_abroad'], 'default', 'value' => 0],
            [['usd_rate_margin'], 'default', 'value' => 3.00],
            [['paypal_fee_percent'], 'default', 'value' => 4.40],
            [['paypal_fee_fixed_usd'], 'default', 'value' => 0.30],
            [['site_name', 'site_url', 'mobile_1', 'email_1'], 'required'],
            [['founded_year', 'mobile_1', 'mobile_2', 'whatsapp', 'updated_at', 'review_invoices', 'razorpay_india', 'razorpay_abroad', 'paypal_abroad', 'usd_rate_auto', 'usd_rate_checked_at', 'usd_rate_fetched_at'], 'integer'],
            [['usd_rate_margin', 'usd_inr_rate', 'usd_market_rate', 'paypal_fee_percent', 'paypal_fee_fixed_usd'], 'number'],
            [['site_name', 'site_url', 'grievance_email', 'email_1', 'email_2', 'email_3', 'address_line_1', 'address_line_2', 'address_line_3', 'facebook', 'x', 'youtube', 'linkedin', 'instagram'], 'string', 'max' => 255],
            [['tagline'], 'string', 'max' => 150],
            [['services_line'], 'string', 'max' => 200],
            [['grievance_name', 'tawk_chat', 'google_site_verification', 'bing_site_verification'], 'string', 'max' => 100],
            [['indexnow_key'], 'string', 'max' => 64],
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
            'tagline' => 'Tagline',
            'services_line' => 'Services Line',
            'grievance_name' => 'Grievance Name',
            'grievance_email' => 'Grievance Email',
            'founded_year' => 'Founded Year',
            'mobile_1' => 'Mobile 1',
            'mobile_2' => 'Mobile 2',
            'whatsapp' => 'Whatsapp',
            'tawk_chat' => 'Tawk Chat',
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
            'review_invoices' => 'Review Invoices',
            'razorpay_india' => 'Razorpay India',
            'razorpay_abroad' => 'Razorpay Abroad',
            'paypal_abroad' => 'Paypal Abroad',
            'usd_rate_auto' => 'Usd Rate Auto',
            'usd_rate_margin' => 'Usd Rate Margin',
            'usd_inr_rate' => 'Usd Inr Rate',
            'usd_market_rate' => 'Usd Market Rate',
            'usd_rate_checked_at' => 'Usd Rate Checked At',
            'usd_rate_fetched_at' => 'Usd Rate Fetched At',
            'paypal_fee_percent' => 'Paypal Fee Percent',
            'paypal_fee_fixed_usd' => 'Paypal Fee Fixed Usd',
            'google_site_verification' => 'Google Site Verification',
            'bing_site_verification' => 'Bing Site Verification',
            'indexnow_key' => 'Indexnow Key',
        ];
    }
}
