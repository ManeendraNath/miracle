<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Coupons;

/**
 * CouponsSearch represents the model behind the search form of `common\models\Coupons`.
 */
class CouponsSearch extends Coupons
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['coupon_id', 'uses_per_coupon', 'uses_per_user'], 'integer'],
            [['coupon_name', 'coupon_description', 'coupon_type', 'coupon_code', 'coupon_start_date', 'coupon_expire_date', 'coupon_active', 'date_created'], 'safe'],
            [['coupon_amount', 'coupon_minimum_order'], 'number'],
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
        $query = Coupons::find();

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
            'coupon_id' => $this->coupon_id,
            'coupon_amount' => $this->coupon_amount,
            'coupon_minimum_order' => $this->coupon_minimum_order,
            'coupon_start_date' => $this->coupon_start_date,
            'coupon_expire_date' => $this->coupon_expire_date,
            'uses_per_coupon' => $this->uses_per_coupon,
            'uses_per_user' => $this->uses_per_user,
            'date_created' => $this->date_created,
        ]);

        $query->andFilterWhere(['like', 'coupon_name', $this->coupon_name])
            ->andFilterWhere(['like', 'coupon_description', $this->coupon_description])
            ->andFilterWhere(['like', 'coupon_type', $this->coupon_type])
            ->andFilterWhere(['like', 'coupon_code', $this->coupon_code])
            ->andFilterWhere(['like', 'coupon_active', $this->coupon_active]);

        return $dataProvider;
    }
}
