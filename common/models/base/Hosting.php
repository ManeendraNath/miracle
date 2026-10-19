<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "hosting".
 *
 * @property int $id
 * @property int $user_id
 * @property string $primary_domain
 * @property string|null $cpanel_ip
 * @property string|null $cpanel_username
 * @property string|null $cpanel_password
 * @property string $current_expiry_date
 * @property int $status 0: Inactive, 1: Active, 2: Suspended/Expired
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $renewal_cycle_months
 * @property float|null $renewal_increase_percent
 * @property float|null $renewal_base_amount Base amount used when no previous billed renewal exists
 *
 * @property User $user
 */
class Hosting extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'hosting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cpanel_ip', 'cpanel_username', 'cpanel_password', 'renewal_cycle_months', 'renewal_increase_percent', 'renewal_base_amount'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1],
            [['user_id', 'primary_domain', 'current_expiry_date', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'status', 'created_at', 'updated_at', 'renewal_cycle_months'], 'integer'],
            [['current_expiry_date'], 'safe'],
            [['renewal_increase_percent', 'renewal_base_amount'], 'number'],
            [['primary_domain', 'cpanel_password'], 'string', 'max' => 255],
            [['cpanel_ip'], 'string', 'max' => 45],
            [['cpanel_username'], 'string', 'max' => 100],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'primary_domain' => 'Primary Domain',
            'cpanel_ip' => 'Cpanel Ip',
            'cpanel_username' => 'Cpanel Username',
            'cpanel_password' => 'Cpanel Password',
            'current_expiry_date' => 'Current Expiry Date',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'renewal_cycle_months' => 'Renewal Cycle Months',
            'renewal_increase_percent' => 'Renewal Increase Percent',
            'renewal_base_amount' => 'Renewal Base Amount',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

}
