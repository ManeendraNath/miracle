<?php

namespace backend\models\searches;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Hosting;

/**
 * HostingSearch represents the model behind the search form of `common\models\Hosting`.
 */
class HostingSearch extends Hosting
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['primary_domain', 'cpanel_ip', 'cpanel_username', 'cpanel_password', 'current_expiry_date'], 'safe'],
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
        $query = Hosting::find();

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
            'current_expiry_date' => $this->current_expiry_date,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'primary_domain', $this->primary_domain])
            ->andFilterWhere(['like', 'cpanel_ip', $this->cpanel_ip])
            ->andFilterWhere(['like', 'cpanel_username', $this->cpanel_username])
            ->andFilterWhere(['like', 'cpanel_password', $this->cpanel_password]);

        return $dataProvider;
    }
}
