<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string|null $password_reset_token
 * @property string $email
 * @property string|null $mobile
 * @property string|null $whatsapp
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property string|null $verification_token
 *
 * @property BillingEvent[] $billingEvents
 * @property CouponAssignedUser[] $couponAssignedUsers
 * @property CouponRedemption[] $couponRedemptions
 * @property CustomerBillingProfile $customerBillingProfile
 * @property CustomerNotification[] $customerNotifications
 * @property CustomerPreference $customerPreference
 * @property Domains[] $domains
 * @property Hosting[] $hostings
 * @property Invoice[] $invoices
 * @property Maintenance[] $maintenances
 * @property ServiceReminderLog[] $serviceReminderLogs
 * @property SupportTicketAttachment[] $supportTicketAttachments
 * @property SupportTicket[] $supportTickets
 * @property UserLoginHistory[] $userLoginHistories
 */
class User extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['password_reset_token', 'mobile', 'whatsapp', 'verification_token'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 10],
            [['username', 'auth_key', 'password_hash', 'email', 'created_at', 'updated_at'], 'required'],
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['username', 'password_hash', 'password_reset_token', 'email', 'verification_token'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            [['mobile', 'whatsapp'], 'string', 'max' => 20],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['password_reset_token'], 'unique'],
            [['mobile'], 'unique'],
            [['whatsapp'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password Hash',
            'password_reset_token' => 'Password Reset Token',
            'email' => 'Email',
            'mobile' => 'Mobile',
            'whatsapp' => 'Whatsapp',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'verification_token' => 'Verification Token',
        ];
    }

    /**
     * Gets query for [[BillingEvents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBillingEvents()
    {
        return $this->hasMany(BillingEvent::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[CouponAssignedUsers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCouponAssignedUsers()
    {
        return $this->hasMany(CouponAssignedUser::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[CouponRedemptions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCouponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[CustomerBillingProfile]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomerBillingProfile()
    {
        return $this->hasOne(CustomerBillingProfile::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[CustomerNotifications]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomerNotifications()
    {
        return $this->hasMany(CustomerNotification::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[CustomerPreference]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomerPreference()
    {
        return $this->hasOne(CustomerPreference::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Domains]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDomains()
    {
        return $this->hasMany(Domains::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Hostings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHostings()
    {
        return $this->hasMany(Hosting::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Invoices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        return $this->hasMany(Invoice::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Maintenances]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMaintenances()
    {
        return $this->hasMany(Maintenance::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[ServiceReminderLogs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getServiceReminderLogs()
    {
        return $this->hasMany(ServiceReminderLog::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[SupportTicketAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSupportTicketAttachments()
    {
        return $this->hasMany(SupportTicketAttachment::class, ['uploaded_by' => 'id']);
    }

    /**
     * Gets query for [[SupportTickets]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSupportTickets()
    {
        return $this->hasMany(SupportTicket::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[UserLoginHistories]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserLoginHistories()
    {
        return $this->hasMany(UserLoginHistory::class, ['user_id' => 'id']);
    }

}
