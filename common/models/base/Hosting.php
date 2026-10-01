<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "hosting".
 *
 * @property int $ID
 * @property string $Domain
 * @property string $cPanelIP
 * @property string $cPanelUserName
 * @property string $cPanelPassword
 * @property int|null $Plan 1 : Yearly, 2 : Monthly
 * @property int $PlanPrice
 * @property string $RegisteredDate
 * @property string $nextRenewalDate
 * @property int $UserID
 * @property int $Status 0 : Inactive, 1 : Active, 2 : Expired
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
            [['Plan'], 'default', 'value' => null],
            [['PlanPrice'], 'default', 'value' => 2000],
            [['Domain', 'cPanelIP', 'cPanelUserName', 'cPanelPassword', 'RegisteredDate', 'nextRenewalDate', 'UserID', 'Status'], 'required'],
            [['Plan', 'PlanPrice', 'UserID', 'Status'], 'integer'],
            [['RegisteredDate', 'nextRenewalDate'], 'safe'],
            [['Domain', 'cPanelUserName', 'cPanelPassword'], 'string', 'max' => 255],
            [['cPanelIP'], 'string', 'max' => 100],
            [['Domain'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ID' => 'ID',
            'Domain' => 'Domain',
            'cPanelIP' => 'C Panel Ip',
            'cPanelUserName' => 'C Panel User Name',
            'cPanelPassword' => 'C Panel Password',
            'Plan' => 'Plan',
            'PlanPrice' => 'Plan Price',
            'RegisteredDate' => 'Registered Date',
            'nextRenewalDate' => 'Next Renewal Date',
            'UserID' => 'User ID',
            'Status' => 'Status',
        ];
    }

}
