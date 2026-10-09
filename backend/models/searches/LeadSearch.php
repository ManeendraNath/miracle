<?php

namespace backend\models\searches;

use yii\data\ActiveDataProvider;
use common\models\Lead;

class LeadSearch extends Lead
{

    public $date_range;
    public $search_keyword; // Handles universal searches across name/phone/email at once

    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['lead_name', 'phone_number', 'source', 'status', 'date_range', 'search_keyword'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = Lead::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC], // Fresh hot customer requests float to top row
                'attributes' => ['id', 'lead_name', 'created_at']
            ],
            'pagination' => ['pageSize' => 15]
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        // 1. ADVANCED STATUS SPECIFIC GROUP CORES EXCLUSIONS/INCLUSIONS
        if (!empty($this->status)) {
            $query->andFilterWhere(['status' => $this->status]);
        }

        // 2. UNIVERSAL KEYWORD COMBINATOR SEARCH FIELD
        if (!empty($this->search_keyword)) {
            $query->andFilterWhere(['or',
                ['like', 'lead_name', $this->search_keyword],
                ['like', 'phone_number', $this->search_keyword],
                ['like', 'source', $this->search_keyword]
            ]);
        }

        // 3. COLUMN BY COLUMN DATA GRID FIELDS FILTERS
        $query->andFilterWhere(['like', 'lead_name', $this->lead_name])
                ->andFilterWhere(['like', 'phone_number', $this->phone_number])
                ->andFilterWhere(['like', 'source', $this->source]);

        // 📅 4. HIGH-PRECISION DATE RANGE CALENDAR PARAMETERS PICKER INTERCEPTOR
        if (!empty($this->date_range) && strpos($this->date_range, ' - ') !== false) {
            list($start_date, $end_date) = explode(' - ', $this->date_range);
            $query->andFilterWhere(['between', 'DATE(created_at)', $start_date, $end_date]);
        }

        return $dataProvider;
    }
}
