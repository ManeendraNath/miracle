<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "customer_quries".
 *
 * @property int $ID
 * @property string $CustomerName
 * @property int $Phone
 * @property string $Email
 * @property string $Message
 * @property string $UserIP
 * @property string $DateAdded
 */
class CustomerQuries extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'customer_quries';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['CustomerName', 'Phone', 'Email', 'Message', 'UserIP', 'DateAdded'], 'required'],
            [['Phone'], 'integer'],
            [['Message'], 'string'],
            [['DateAdded'], 'safe'],
            [['CustomerName', 'Email'], 'string', 'max' => 100],
            [['UserIP'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ID' => 'ID',
            'CustomerName' => 'Customer Name',
            'Phone' => 'Phone',
            'Email' => 'Email',
            'Message' => 'Message',
            'UserIP' => 'User Ip',
            'DateAdded' => 'Date Added',
        ];
    }

}
