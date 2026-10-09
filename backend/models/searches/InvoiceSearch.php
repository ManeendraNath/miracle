<?php

namespace backend\models\searches;

use yii\data\ActiveDataProvider;
use common\models\Invoice;

/**
 * InvoiceSearch represents the model behind the search form of `common\models\Invoice`.
 */
class InvoiceSearch extends Invoice
{
    // Transient placeholder to handle the composite range string text
    public $date_range;

    public function rules()
    {
        return [
            [['id', 'user_id', 'coupon_id'], 'integer'],
            [['invoice_number', 'client_name', 'status', 'date_range'], 'safe'],
            [['subtotal_amount', 'discount_amount', 'total_payable'], 'number'],
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

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // Exact matches
        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
        ]);

        // String searches
        $query->andFilterWhere(['like', 'invoice_number', $this->invoice_number])
              ->andFilterWhere(['like', 'client_name', $this->client_name]);

        // 📅 DYNAMIC DATE RANGE FILTERING LOGIC
        if (!empty($this->date_range) && strpos($this->date_range, ' - ') !== false) {
            list($start_date, $end_date) = explode(' - ', $this->date_range);
            // Formats search against your core database 'created_at' or 'due_date' timestamp rows safely
            $query->andFilterWhere(['between', 'DATE(created_at)', $start_date, $end_date]);
        }

        return $dataProvider;
    }
}
