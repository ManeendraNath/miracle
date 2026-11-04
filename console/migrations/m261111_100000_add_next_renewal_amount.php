<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * One-time price for a service's next renewal, set by an Admin (e.g. a negotiated rate). The next billing
 * event bills exactly this (as a manual amount, which later increases build on) and it is cleared once
 * that renewal is fulfilled.
 */
final class m261111_100000_add_next_renewal_amount extends Migration
{
    private const TABLES = ['{{%domains}}', '{{%hosting}}', '{{%maintenance}}'];

    public function safeUp(): void
    {
        foreach (self::TABLES as $table) {
            $this->addColumn($table, 'next_renewal_amount', $this->decimal(10, 2)->null()->after('renewal_base_amount'));
        }
    }

    public function safeDown(): void
    {
        foreach (self::TABLES as $table) {
            $this->dropColumn($table, 'next_renewal_amount');
        }
    }
}
