<?php

namespace common\models;

use yii\db\ActiveQuery;

class TransactionQuery extends ActiveQuery
{

    /**
     * Scope: Filter manual user-uploaded UPI/Bank transfer items waiting for review
     */
    public function pendingManualReviews()
    {
        return $this->andWhere(['gateway_id' => 2, 'status' => 'Pending']);
    }

    /**
     * Scope: Extract daily transactional gross processing overview trends
     */
    public function processedToday()
    {
        $startOfDay = strtotime('today');
        $endOfDay = strtotime('tomorrow') - 1;

        return $this->andWhere(['status' => 'Captured'])
                        ->andWhere(['between', 'paid_at', $startOfDay, $endOfDay]);
    }
}
