<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "maintenance".
 *
 * @property int $id
 * @property int $user_id
 * @property string $website_url
 * @property string $start_date
 * @property string $current_expiry_date Extended automatically when a renewal history ledger record is verified
 * @property int $status 0: Cancelled, 1: Active, 2: Overdue
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */
class Maintenance extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'maintenance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status'], 'default', 'value' => 1],
            [['user_id', 'website_url', 'start_date', 'current_expiry_date', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['start_date', 'current_expiry_date'], 'safe'],
            [['website_url'], 'string', 'max' => 255],
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
            'website_url' => 'Website Url',
            'start_date' => 'Start Date',
            'current_expiry_date' => 'Current Expiry Date',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
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
