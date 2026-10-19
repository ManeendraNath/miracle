<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "customer_billing_profile".
 *
 * @property int $id
 * @property int $user_id
 * @property string $legal_name
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string $state
 * @property string|null $state_code
 * @property string|null $postal_code
 * @property string|null $gstin
 * @property string|null $pan
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */
class CustomerBillingProfile extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'customer_billing_profile';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['address_line_1', 'address_line_2', 'city', 'state_code', 'postal_code', 'gstin', 'pan'], 'default', 'value' => null],
            [['user_id', 'legal_name', 'state', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'created_at', 'updated_at'], 'integer'],
            [['legal_name', 'address_line_1', 'address_line_2'], 'string', 'max' => 255],
            [['city', 'state'], 'string', 'max' => 100],
            [['state_code', 'pan'], 'string', 'max' => 10],
            [['postal_code'], 'string', 'max' => 20],
            [['gstin'], 'string', 'max' => 15],
            [['user_id'], 'unique'],
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
            'legal_name' => 'Legal Name',
            'address_line_1' => 'Address Line 1',
            'address_line_2' => 'Address Line 2',
            'city' => 'City',
            'state' => 'State',
            'state_code' => 'State Code',
            'postal_code' => 'Postal Code',
            'gstin' => 'Gstin',
            'pan' => 'Pan',
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
