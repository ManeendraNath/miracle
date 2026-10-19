<?php

declare(strict_types=1);

namespace backend\models\searches;

use common\helpers\GridPaginationHelper;
use common\models\AuditRequest;
use common\models\traits\DateRangeSearchTrait;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Search model for Audit Requests.
 */
class AuditRequestSearch extends AuditRequest
{
    use DateRangeSearchTrait;

    public ?string $created_at_range = null;

    /**
     * Search validation rules.
     */
    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['name', 'email', 'phone', 'company_url', 'current_framework', 'hosting_environment', 'message', 'status', 'user_ip', 'created_at_range'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    /**
     * Build the Audit Request GridView provider.
     */
    public function search(array $params, ?string $formName = null): ActiveDataProvider {
        $query = AuditRequest::find();

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

        $query->andFilterWhere([
            'audit_request.id' => $this->id,
            'audit_request.status' => $this->status,
        ]);

        $query
            ->andFilterWhere(['like', 'audit_request.name', $this->name])
            ->andFilterWhere(['like', 'audit_request.email', $this->email])
            ->andFilterWhere(['like', 'audit_request.phone', $this->phone])
            ->andFilterWhere(['like', 'audit_request.company_url', $this->company_url])
            ->andFilterWhere(['like', 'audit_request.current_framework', $this->current_framework])
            ->andFilterWhere(['like', 'audit_request.hosting_environment', $this->hosting_environment]);

        /*
         * created_at is stored as UNIX timestamp.
         */
        $this->applyTimestampDateRange($query, 'audit_request.created_at', $this->created_at_range);

        return $dataProvider;
    }
}