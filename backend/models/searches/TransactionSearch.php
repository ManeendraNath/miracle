<?php

namespace backend\models\searches;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Transaction;

/**
 * TransactionSearch represents the model behind the search form of `common\models\Transaction`.
 */
class TransactionSearch extends Transaction
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'invoice_id', 'gateway_id', 'paid_at', 'created_at', 'updated_at'], 'integer'],
            [['gateway_payment_id', 'gateway_order_id', 'status', 'user_payment_notes', 'payment_receipt_file', 'raw_payload'], 'safe'],
            [['amount', 'gateway_fee'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param string|null $formName Form name to be used into `->load()` method.
     *
     * @return ActiveDataProvider
     */
    public function search($params, $formName = null)
    {
        $query = Transaction::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'gateway_id' => $this->gateway_id,
            'amount' => $this->amount,
            'gateway_fee' => $this->gateway_fee,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'gateway_payment_id', $this->gateway_payment_id])
            ->andFilterWhere(['like', 'gateway_order_id', $this->gateway_order_id])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'user_payment_notes', $this->user_payment_notes])
            ->andFilterWhere(['like', 'payment_receipt_file', $this->payment_receipt_file])
            ->andFilterWhere(['like', 'raw_payload', $this->raw_payload]);

        return $dataProvider;
    }
}
