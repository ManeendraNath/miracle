<?php

declare(strict_types=1);

namespace backend\models\searches;

use common\models\CustomerPreference;
use yii\data\ActiveDataProvider;

final class CustomerPreferenceSearch extends CustomerPreference
{

    public ?string $customer = null;

    #[\Override]
    public function rules(): array
    {
        return [
            [
                [
                    'renewal_reminders',
                    'payment_reminders',
                    'service_expiry_notifications',
                    'support_notifications',
                ],
                'boolean',
            ],
            [
                ['customer'],
                'safe',
            ],
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = CustomerPreference::find()
                ->alias('cp')
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
            'cp.renewal_reminders' =>
            $this->renewal_reminders,
            'cp.payment_reminders' =>
            $this->payment_reminders,
            'cp.service_expiry_notifications' =>
            $this->service_expiry_notifications,
            'cp.support_notifications' =>
            $this->support_notifications,
        ]);

        if ($this->customer !== null && $this->customer !== '') {
            $query->andWhere([
                'or',
                ['like', 'u.username', $this->customer],
                ['like', 'u.email', $this->customer],
            ]);
        }

        return $dataProvider;
    }
}
