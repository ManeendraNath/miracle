<?php

declare(strict_types=1);

namespace backend\models\searches;

use common\models\traits\DateRangeSearchTrait;
use common\models\User;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Search model for common\models\User.
 */
class UserSearch extends User
{
    use DateRangeSearchTrait;

    public ?string $created_at_range = null;

    public ?string $updated_at_range = null;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [
                ['id', 'status'],
                'integer',
            ],

            [
                [
                    'username',
                    'email',
                    'created_at_range',
                    'updated_at_range',
                ],
                'safe',
            ],
        ];
    }

    /**
     * Search models intentionally bypass the parent model scenarios.
     */
    public function scenarios(): array
    {
        return Model::scenarios();
    }

    /**
     * Build filtered User data provider.
     */
    public function search(
        array $params,
        ?string $formName = null
    ): ActiveDataProvider {
        $query = User::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,

            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ],

            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            return $dataProvider;
        }

        /*
         * Exact-value filters.
         */
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);

        /*
         * Text filters.
         */
        $query
            ->andFilterWhere([
                'like',
                'username',
                $this->username,
            ])
            ->andFilterWhere([
                'like',
                'email',
                $this->email,
            ]);

        /*
         * UNIX timestamp date-range filters.
         */
        $this->applyTimestampDateRange(
            $query,
            'user.created_at',
            $this->created_at_range
        );

        $this->applyTimestampDateRange(
            $query,
            'user.updated_at',
            $this->updated_at_range
        );

        return $dataProvider;
    }
}