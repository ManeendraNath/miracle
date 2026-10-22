<?php

declare(strict_types=1);

namespace backend\models\searches;

use common\models\UserLoginHistory;
use common\models\traits\DateRangeSearchTrait;
use yii\data\ActiveDataProvider;

final class UserLoginHistorySearch extends UserLoginHistory
{

    use DateRangeSearchTrait;

    public ?string $customer = null;
    public ?string $logged_in_at_range = null;

    #[\Override]
    public function rules(): array
    {
        return [
            [
                [
                    'customer',
                    'ip_address',
                    'user_agent',
                    'logged_in_at_range',
                ],
                'safe',
            ],
            [
                ['user_id'],
                'integer',
            ],
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = UserLoginHistory::find()
                ->alias('lh')
                ->joinWith([
                    'user u',
        ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'logged_in_at' => SORT_DESC,
                    'id' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');

            return $dataProvider;
        }

        if ($this->customer !== null && $this->customer !== '') {
            $query->andWhere([
                'or',
                ['like', 'u.username', $this->customer],
                ['like', 'u.email', $this->customer],
            ]);
        }

        $query
                ->andFilterWhere([
                    'like',
                    'lh.ip_address',
                    $this->ip_address,
                ])
                ->andFilterWhere([
                    'like',
                    'lh.user_agent',
                    $this->user_agent,
        ]);
        $query->andFilterWhere([
            'lh.user_id' => $this->user_id,
        ]);
        $this->applyTimestampDateRange(
                $query,
                'lh.logged_in_at',
                $this->logged_in_at_range
        );

        return $dataProvider;
    }
}
