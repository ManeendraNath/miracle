<?php

namespace backend\models\searches;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Invoice;

/**
 * InvoiceSearch represents the model behind the search form of `common\models\Invoice`.
 */
class InvoiceSearch extends Invoice
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'coupon_id', 'created_at', 'updated_at'], 'integer'],
            [['invoice_number', 'client_name', 'client_address_line_1', 'client_address_line_2', 'coupon_code', 'status', 'due_date'], 'safe'],
            [['subtotal_amount', 'discount_amount', 'cgst_percent', 'sgst_percent', 'igst_percent', 'total_payable'], 'number'],
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
        $query = Invoice::find();

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
            'user_id' => $this->user_id,
            'subtotal_amount' => $this->subtotal_amount,
            'coupon_id' => $this->coupon_id,
            'discount_amount' => $this->discount_amount,
            'cgst_percent' => $this->cgst_percent,
            'sgst_percent' => $this->sgst_percent,
            'igst_percent' => $this->igst_percent,
            'total_payable' => $this->total_payable,
            'due_date' => $this->due_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'invoice_number', $this->invoice_number])
            ->andFilterWhere(['like', 'client_name', $this->client_name])
            ->andFilterWhere(['like', 'client_address_line_1', $this->client_address_line_1])
            ->andFilterWhere(['like', 'client_address_line_2', $this->client_address_line_2])
            ->andFilterWhere(['like', 'coupon_code', $this->coupon_code])
            ->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }
}
