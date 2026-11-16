<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "testimonial".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $client_name
 * @property string|null $role
 * @property string|null $company
 * @property string|null $country
 * @property string $quote
 * @property int $is_published
 * @property int $sort_order
 * @property int|null $consent_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */
class Testimonial extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'testimonial';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'role', 'company', 'country', 'consent_at'], 'default', 'value' => null],
            [['sort_order'], 'default', 'value' => 0],
            [['user_id', 'is_published', 'sort_order', 'consent_at', 'created_at', 'updated_at'], 'integer'],
            [['client_name', 'quote', 'created_at', 'updated_at'], 'required'],
            [['quote'], 'string'],
            [['client_name', 'role', 'company'], 'string', 'max' => 255],
            [['country'], 'string', 'max' => 100],
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
            'client_name' => 'Client Name',
            'role' => 'Role',
            'company' => 'Company',
            'country' => 'Country',
            'quote' => 'Quote',
            'is_published' => 'Is Published',
            'sort_order' => 'Sort Order',
            'consent_at' => 'Consent At',
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
