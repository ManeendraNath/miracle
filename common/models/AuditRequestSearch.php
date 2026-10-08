<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\AuditRequest;

/**
 * AuditRequestSearch represents the model behind the search form of `common\models\AuditRequest`.
 */
class AuditRequestSearch extends AuditRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'created_at', 'updated_at'], 'integer'],
            [['name', 'email', 'phone', 'company_url', 'current_framework', 'hosting_environment', 'message', 'status', 'user_ip'], 'safe'],
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
        $query = AuditRequest::find();

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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'company_url', $this->company_url])
            ->andFilterWhere(['like', 'current_framework', $this->current_framework])
            ->andFilterWhere(['like', 'hosting_environment', $this->hosting_environment])
            ->andFilterWhere(['like', 'message', $this->message])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'user_ip', $this->user_ip]);

        return $dataProvider;
    }
}
