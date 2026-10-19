<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "billing_run".
 *
 * @property int $id
 * @property string $run_number
 * @property string $status
 * @property int $started_at
 * @property int|null $completed_at
 * @property string|null $failure_message
 * @property int $created_at
 * @property int $updated_at
 *
 * @property BillingEvent[] $billingEvents
 * @property Invoice[] $invoices
 */
class BillingRun extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const STATUS_RUNNING = 'Running';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_FAILED = 'Failed';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'billing_run';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['completed_at', 'failure_message'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 'Running'],
            [['run_number', 'started_at', 'created_at', 'updated_at'], 'required'],
            [['status', 'failure_message'], 'string'],
            [['started_at', 'completed_at', 'created_at', 'updated_at'], 'integer'],
            [['run_number'], 'string', 'max' => 100],
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
            [['run_number'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'run_number' => 'Run Number',
            'status' => 'Status',
            'started_at' => 'Started At',
            'completed_at' => 'Completed At',
            'failure_message' => 'Failure Message',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[BillingEvents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBillingEvents()
    {
        return $this->hasMany(BillingEvent::class, ['billing_run_id' => 'id']);
    }

    /**
     * Gets query for [[Invoices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        return $this->hasMany(Invoice::class, ['billing_run_id' => 'id']);
    }


    /**
     * column status ENUM value labels
     * @return string[]
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_RUNNING => 'Running',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
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
    public function isStatusRunning()
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function setStatusToRunning()
    {
        $this->status = self::STATUS_RUNNING;
    }

    /**
     * @return bool
     */
    public function isStatusCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function setStatusToCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
    }

    /**
     * @return bool
     */
    public function isStatusFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function setStatusToFailed()
    {
        $this->status = self::STATUS_FAILED;
    }
}
