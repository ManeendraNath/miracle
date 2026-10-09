<?php

namespace backend\models\searches;

use yii\data\ActiveDataProvider;
use common\models\Transaction;

class TransactionSearch extends Transaction
{
    public $date_range;

    public function rules()
    {
        return [
            [['id', 'gateway_id'], 'integer'],
            [['transaction_reference', 'status', 'date_range'], 'safe'],
            [['amount'], 'number'],
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

        $query->andFilterWhere([
            'id' => $this->id,
            'gateway_id' => $this->gateway_id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'transaction_reference', $this->transaction_reference]);

        // 📅 DATE RANGE INTERCEPTOR FOR TRANSACTIONS (paid_at boundaries check)
        if (!empty($this->date_range) && strpos($this->date_range, ' - ') !== false) {
            list($start_date, $end_date) = explode(' - ', $this->date_range);
            $query->andFilterWhere(['between', 'DATE(FROM_UNIXTIME(paid_at))', $start_date, $end_date]);
        }

        return $dataProvider;
    }
}
