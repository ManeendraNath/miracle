<?php

namespace backend\models\searches;

use yii\data\ActiveDataProvider;
use common\models\Lead;

class LeadSearch extends Lead
{
    public $date_range;

    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['lead_name', 'phone_number', 'source', 'status', 'date_range'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = Lead::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['id' => $this->id, 'status' => $this->status]);

        $query->andFilterWhere(['like', 'lead_name', $this->lead_name])
              ->andFilterWhere(['like', 'phone_number', $this->phone_number])
              ->andFilterWhere(['like', 'source', $this->source]);

        // 📅 DATE RANGE INTERCEPTOR FOR CRM INBOUND LEADS
        if (!empty($this->date_range) && strpos($this->date_range, ' - ') !== false) {
            list($start_date, $end_date) = explode(' - ', $this->date_range);
            $query->andFilterWhere(['between', 'DATE(created_at)', $start_date, $end_date]);
        }

        return $dataProvider;
    }
}
