<?php

namespace common\models;

use yii\db\ActiveQuery;

class InvoiceQuery extends ActiveQuery
{

    /**
     * Scope: Aggregate gross total receivables value from unpaid invoices
     */
    public function getUnpaidRevenueSum()
    {
        return $this->andWhere(['status' => 'Unpaid'])->sum('total_payable') ?: 0.00;
    }

    /**
     * Scope: Aggregate net absolute lifetime collected billing settlement revenue
     */
    public function getLifetimeCollectedRevenue()
    {
        return $this->andWhere(['status' => 'Paid'])->sum('total_payable') ?: 0.00;
    }

    /**
     * Scope: Filter statements falling past due thresholds
     */
    public function overdue()
    {
        return $this->andWhere(['status' => 'Unpaid'])
                        ->andWhere(['<', 'due_date', date('Y-m-d')]);
    }
}
