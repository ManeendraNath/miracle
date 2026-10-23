<?php

declare(strict_types=1);

namespace backend\models\searches;

use common\helpers\GridPaginationHelper;
use common\models\Coupon;
use yii\base\Model;
use yii\data\ActiveDataProvider;

final class CouponSearch extends Coupon
{

    public ?string $validity_range = null;

    public function rules(): array
    {
        return [
            [
                [
                    'id',
                    'uses_per_coupon',
                    'uses_per_user',
                ],
                'integer',
            ],
            [
                [
                    'name',
                    'code',
                    'type',
                    'is_active',
                    'validity_range',
                ],
                'safe',
            ],
            [
                [
                    'amount',
                    'minimum_order',
                ],
                'number',
            ],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(
            array $params,
            ?string $formName = null
    ): ActiveDataProvider
    {
        $query = Coupon::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' =>
                GridPaginationHelper::getPageSize(),
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ],
        ]);

        $this->load(
                $params,
                $formName
        );

        if (!$this->validate()) {
            $query->where('0=1');

            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'uses_per_coupon' => $this->uses_per_coupon,
            'uses_per_user' => $this->uses_per_user,
        ]);

        $query
                ->andFilterWhere([
                    'like',
                    'name',
                    $this->name,
                ])
                ->andFilterWhere([
                    'like',
                    'code',
                    $this->code,
        ]);

        if ($this->amount !== null) {
            $query->andWhere([
                'amount' => $this->amount,
            ]);
        }

        if ($this->minimum_order !== null) {
            $query->andWhere([
                'minimum_order' =>
                $this->minimum_order,
            ]);
        }

        $this->applyValidityRange($query);

        return $dataProvider;
    }

    private function applyValidityRange($query): void
    {
        if (empty($this->validity_range)) {
            return;
        }

        $parts = preg_split(
                '/\s+-\s+/',
                trim($this->validity_range)
        );

        if (
                !is_array($parts) || count($parts) !== 2
        ) {
            return;
        }

        $from = strtotime(trim($parts[0]));
        $to = strtotime(trim($parts[1]));

        if ($from === false || $to === false) {
            return;
        }

        $query->andWhere([
            'and',
            [
                '>=',
                'start_date',
                date('Y-m-d 00:00:00', $from),
            ],
            [
                '<=',
                'end_date',
                date('Y-m-d 23:59:59', $to),
            ],
        ]);
    }
}
