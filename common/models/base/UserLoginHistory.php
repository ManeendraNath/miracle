<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "user_login_history".
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int $logged_in_at
 *
 * @property User $user
 */
class UserLoginHistory extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_login_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip_address', 'user_agent'], 'default', 'value' => null],
            [['user_id', 'logged_in_at'], 'required'],
            [['user_id', 'logged_in_at'], 'integer'],
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string', 'max' => 1000],
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
            'ip_address' => 'Ip Address',
            'user_agent' => 'User Agent',
            'logged_in_at' => 'Logged In At',
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
