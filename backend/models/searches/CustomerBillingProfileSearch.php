<?php

declare(strict_types=1);

namespace backend\models\searches;

use common\helpers\GridPaginationHelper;
use common\models\CustomerBillingProfile;
use yii\base\Model;
use yii\data\ActiveDataProvider;

final class CustomerBillingProfileSearch extends CustomerBillingProfile
{
    public function rules(): array
    {
        return [
            [['user_id'], 'integer'],
            [['legal_name', 'state', 'gstin', 'pan'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params, ?string $formName = null): ActiveDataProvider
    {
        $query = CustomerBillingProfile::find()->with('user');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => GridPaginationHelper::getPageSize()],
            'sort' => ['defaultOrder' => ['legal_name' => SORT_ASC]],
        ]);

        $this->load($params, $formName);
        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere(['user_id' => $this->user_id])
            ->andFilterWhere(['like', 'legal_name', $this->legal_name])
            // One box for "State / Country": foreign profiles show their country.
            ->andFilterWhere(['or', ['like', 'state', $this->state], ['like', 'country', $this->state]])
            ->andFilterWhere(['like', 'gstin', $this->gstin])
            ->andFilterWhere(['like', 'pan', $this->pan]);

        return $dataProvider;
    }
}
