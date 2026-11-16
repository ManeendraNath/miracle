<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "cron_job".
 *
 * @property string $job
 * @property int $is_enabled
 * @property string|null $updated_by
 * @property int|null $updated_at
 */
class CronJob extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cron_job';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['updated_by', 'updated_at'], 'default', 'value' => null],
            [['is_enabled'], 'default', 'value' => 1],
            [['job'], 'required'],
            [['is_enabled', 'updated_at'], 'integer'],
            [['job'], 'string', 'max' => 64],
            [['updated_by'], 'string', 'max' => 255],
            [['job'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'job' => 'Job',
            'is_enabled' => 'Is Enabled',
            'updated_by' => 'Updated By',
            'updated_at' => 'Updated At',
        ];
    }
}
