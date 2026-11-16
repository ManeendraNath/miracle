<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "transaction_refund".
 *
 * @property int $id
 * @property int $transaction_id
 * @property float $amount
 * @property int $refunded_at
 * @property string|null $note
 *
 * @property Transaction $transaction
 */
class TransactionRefund extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'transaction_refund';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['note'], 'default', 'value' => null],
            [['transaction_id', 'amount', 'refunded_at'], 'required'],
            [['transaction_id', 'refunded_at'], 'integer'],
            [['amount'], 'number'],
            [['note'], 'string', 'max' => 255],
            [['transaction_id'], 'exist', 'skipOnError' => true, 'targetClass' => Transaction::class, 'targetAttribute' => ['transaction_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'transaction_id' => 'Transaction ID',
            'amount' => 'Amount',
            'refunded_at' => 'Refunded At',
            'note' => 'Note',
        ];
    }

    /**
     * Gets query for [[Transaction]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTransaction()
    {
        return $this->hasOne(Transaction::class, ['id' => 'transaction_id']);
    }
}
