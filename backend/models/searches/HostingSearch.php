<?php

declare(strict_types=1);

namespace backend\models\searches;

use common\helpers\GridPaginationHelper;
use common\models\Hosting;
use common\models\traits\DateRangeSearchTrait;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * HostingSearch represents the model behind the search form of `common\models\Hosting`.
 */
class HostingSearch extends Hosting
{

    use DateRangeSearchTrait;

    public ?string $expiry_date_range = null;

    public function rules(): array
    {
        return [
            [
                [
                    'id',
                    'user_id',
                    'status',
                ],
                'integer',
            ],
            [
                [
                    'primary_domain',
                    'cpanel_ip',
                    'cpanel_username',
                    'expiry_date_range',
                ],
                'safe',
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
        $query = Hosting::find()->with('user');

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
            return $dataProvider;
        }

        $query->andFilterWhere([
            'hosting.id' => $this->id,
            'hosting.user_id' => $this->user_id,
            'hosting.status' => $this->status,
        ]);

        $query->andFilterWhere([
            'like',
            'hosting.primary_domain',
            $this->primary_domain,
        ]);

        $query->andFilterWhere([
            'like',
            'hosting.cpanel_ip',
            $this->cpanel_ip,
        ]);

        $query->andFilterWhere([
            'like',
            'hosting.cpanel_username',
            $this->cpanel_username,
        ]);

        $this->applySqlDateRange(
                $query,
                'hosting.current_expiry_date',
                $this->expiry_date_range
        );

        return $dataProvider;
    }
}
