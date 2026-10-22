<?php

declare(strict_types=1);

namespace backend\models\searches;

use common\models\SupportTicket;
use yii\base\Model;
use yii\data\ActiveDataProvider;

final class SupportTicketSearch extends SupportTicket
{

    public function rules(): array
    {
        return [
            [
                [
                    'ticket_number',
                    'subject',
                    'priority',
                    'status',
                ],
                'safe',
            ],
        ];
    }

    public function search(
            array $params
    ): ActiveDataProvider
    {
        $query = SupportTicket::find()
                ->with('user');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'updated_at' =>
                    SORT_DESC,
                    'id' =>
                    SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');

            return $dataProvider;
        }

        $query->andFilterWhere([
            'priority' =>
            $this->priority,
            'status' =>
            $this->status,
        ]);

        $query->andFilterWhere([
            'like',
            'ticket_number',
            $this->ticket_number,
        ]);

        $query->andFilterWhere([
            'like',
            'subject',
            $this->subject,
        ]);

        return $dataProvider;
    }
}
