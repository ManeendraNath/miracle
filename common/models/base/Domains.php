<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "domains".
 *
 * @property int $id
 * @property int $user_id
 * @property string $domain_url
 * @property string $registrar
 * @property string $registered_date
 * @property string $current_expiry_date Automatically extended when a renewal history item is added
 * @property int $status 0: Inactive, 1: Active, 2: Expired
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $renewal_cycle_months
 * @property float|null $renewal_increase_percent
 * @property float|null $renewal_base_amount Base amount used when no previous billed renewal exists
 *
 * @property User $user
 */
class Domains extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'domains';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['renewal_cycle_months', 'renewal_increase_percent', 'renewal_base_amount'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1],
            [['user_id', 'domain_url', 'registrar', 'registered_date', 'current_expiry_date', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'status', 'created_at', 'updated_at', 'renewal_cycle_months'], 'integer'],
            [['registered_date', 'current_expiry_date'], 'safe'],
            [['renewal_increase_percent', 'renewal_base_amount'], 'number'],
            [['domain_url'], 'string', 'max' => 255],
            [['registrar'], 'string', 'max' => 100],
            [['domain_url'], 'unique'],
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
            'domain_url' => 'Domain Url',
            'registrar' => 'Registrar',
            'registered_date' => 'Registered Date',
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
