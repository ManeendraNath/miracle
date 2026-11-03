<?php

namespace backend\models\searches;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Maintenance;
use common\models\traits\DateRangeSearchTrait;

/**
 * MaintenanceSearch represents the model behind the search form of `common\models\Maintenance`.
 */
class MaintenanceSearch extends Maintenance
{
    use DateRangeSearchTrait;

    public ?string $expiry_date_range = null;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'user_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['website_url', 'start_date', 'current_expiry_date', 'expiry_date_range'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
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
        $query = Maintenance::find()->with('user');

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
            'start_date' => $this->start_date,
            'current_expiry_date' => $this->current_expiry_date,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'website_url', $this->website_url]);
        $this->applySqlDateRange($query, 'current_expiry_date', $this->expiry_date_range);

        return $dataProvider;
    }
}
