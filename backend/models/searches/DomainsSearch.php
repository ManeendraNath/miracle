<?php

declare(strict_types=1);

namespace backend\models\searches;

use common\helpers\GridPaginationHelper;
use common\models\Domains;
use common\models\traits\DateRangeSearchTrait;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * DomainsSearch represents the model behind the search form of `common\models\Domains`.
 */
class DomainsSearch extends Domains
{

    use DateRangeSearchTrait;

    /**
     * Search-only date range filters.
     */
    public ?string $registered_date_range = null;
    public ?string $current_expiry_date_range = null;

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
                    'domain_url',
                    'registrar',
                    'registered_date_range',
                    'current_expiry_date_range',
                ],
                'safe',
            ],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params, ?string $formName = null): ActiveDataProvider
    {
        $query = Domains::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => GridPaginationHelper::getPageSize(),
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            return $dataProvider;
        }

        /*
         * Exact filters.
         */
        $query->andFilterWhere([
            'domains.id' => $this->id,
            'domains.user_id' => $this->user_id,
            'domains.status' => $this->status,
        ]);

        /*
         * Text filters.
         */
        $query->andFilterWhere(['like', 'domains.domain_url', $this->domain_url])
                ->andFilterWhere(['like', 'domains.registrar', $this->registrar]);

        /*
         * SQL DATE / DATETIME range filters.
         *
         * These are not UNIX timestamp columns, so use applyDateRange()
         * rather than applyTimestampDateRange().
         */
        $this->applySqlDateRange($query, 'domains.registered_date', $this->registered_date_range);

        $this->applySqlDateRange($query, 'domains.current_expiry_date', $this->current_expiry_date_range);

        return $dataProvider;
    }
}
