<?php

declare(strict_types=1);

namespace backend\models\searches;

use common\helpers\GridPaginationHelper;
use common\models\SupportTicket;
use yii\base\Model;
use yii\data\ActiveDataProvider;

final class SupportTicketSearch extends SupportTicket
{
    use \common\models\traits\ScalarInputTrait;

    public function rules(): array
    {
        return [[['ticket_number', 'subject', 'priority', 'status'], 'safe']];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = SupportTicket::find()->with('user');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => GridPaginationHelper::getPageSize(),
            ],
            'sort' => [
                'defaultOrder' => [
                    'updated_at' => SORT_DESC,
                    'id' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');

            return $dataProvider;
        }

        $query->andFilterWhere([
            'priority' => $this->priority,
            // "active" = the dashboard's open cards: everything still needing work.
            'status' => $this->status === 'active' ? [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS] : $this->status,
        ]);

        $query->andFilterWhere(['like', 'ticket_number', $this->ticket_number]);

        $query->andFilterWhere(['like', 'subject', $this->subject]);

        return $dataProvider;
    }
}
