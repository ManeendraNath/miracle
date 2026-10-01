<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "audit_request".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $company_url
 * @property string $current_framework
 * @property string $hosting_environment
 * @property string $message
 * @property string|null $status
 * @property int $created_at
 * @property int $updated_at
 */
class AuditRequest extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'audit_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_url'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 'Pending'],
            [['name', 'email', 'current_framework', 'hosting_environment', 'message', 'created_at', 'updated_at'], 'required'],
            [['message'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
            [['name', 'email', 'company_url', 'current_framework', 'hosting_environment'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'company_url' => 'Company Url',
            'current_framework' => 'Current Framework',
            'hosting_environment' => 'Hosting Environment',
            'message' => 'Message',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

}
