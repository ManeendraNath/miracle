<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "audit_request".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone Unified from legacy customer_queries
 * @property string|null $company_url
 * @property string|null $current_framework Nullable to support general contact messages
 * @property string|null $hosting_environment Nullable to support general contact messages
 * @property string $message
 * @property string $status
 * @property string|null $user_ip
 * @property int $created_at
 * @property int $updated_at
 */
class AuditRequest extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const STATUS_PENDING = 'Pending';
    const STATUS_IN_REVIEW = 'In_Review';
    const STATUS_CONTACTED = 'Contacted';
    const STATUS_CLOSED = 'Closed';

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
            [['phone', 'company_url', 'current_framework', 'hosting_environment', 'user_ip'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 'Pending'],
            [['name', 'email', 'message', 'created_at', 'updated_at'], 'required'],
            [['message', 'status'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
            [['name', 'email', 'company_url', 'current_framework', 'hosting_environment'], 'string', 'max' => 255],
            [['phone', 'user_ip'], 'string', 'max' => 50],
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
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
            'phone' => 'Phone',
            'company_url' => 'Company Url',
            'current_framework' => 'Current Framework',
            'hosting_environment' => 'Hosting Environment',
            'message' => 'Message',
            'status' => 'Status',
            'user_ip' => 'User Ip',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }


    /**
     * column status ENUM value labels
     * @return string[]
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_REVIEW => 'In_Review',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /**
     * @return string
     */
    public function displayStatus()
    {
        return self::optsStatus()[$this->status];
    }

    /**
     * @return bool
     */
    public function isStatusPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function setStatusToPending()
    {
        $this->status = self::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function isStatusInreview()
    {
        return $this->status === self::STATUS_IN_REVIEW;
    }

    public function setStatusToInreview()
    {
        $this->status = self::STATUS_IN_REVIEW;
    }

    /**
     * @return bool
     */
    public function isStatusContacted()
    {
        return $this->status === self::STATUS_CONTACTED;
    }

    public function setStatusToContacted()
    {
        $this->status = self::STATUS_CONTACTED;
    }

    /**
     * @return bool
     */
    public function isStatusClosed()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function setStatusToClosed()
    {
        $this->status = self::STATUS_CLOSED;
    }
}
