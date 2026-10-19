<?php

namespace backend\models\searches;

use common\helpers\GridPaginationHelper;
use common\models\Lead;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * LeadSearch represents the model behind the search form of `common\models\Lead`. 
 */
class LeadSearch extends Lead
{

    use \common\models\traits\DateRangeSearchTrait;

    public ?string $created_at_range = null;
    public $search_keyword; // Handles universal searches across name/phone/email at once

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'created_at', 'updated_at'], 'integer'],
            [['lead_name', 'phone_number', 'source', 'status', 'date_range', 'search_keyword'], 'safe'],
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
        $query = Lead::find();

        // add conditions that should always apply here 
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => GridPaginationHelper::getPageSize(),
            ],
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ],
        ]);

        $this->load($params, $formName);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails 
            // $query->where('0=1'); 
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
                ->andFilterWhere(['like', 'status', $this->status])
                ->andFilterWhere(['like', 'source', $this->source]);

        // 📅 4. HIGH-PRECISION DATE RANGE CALENDAR PARAMETERS PICKER INTERCEPTOR
        if (!empty($this->created_at_range) && strpos($this->created_at_range, ' - ') !== false) {
            $this->applyTimestampDateRange($query, 'lead.created_at', $this->created_at_range);
        }
        return $dataProvider;
    }
}
