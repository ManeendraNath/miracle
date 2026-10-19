<?php

declare(strict_types=1);

use yii\db\Migration;

class m261015_042628_add_billing_overrides_to_assets extends Migration
{

    private $tables = ['{{%domains}}', '{{%hosting}}', '{{%maintenance}}'];

    #[\Override]
    public function safeUp(): void
    {
        foreach ($this->tables as $table) {
            $this->addColumn($table, 'renewal_cycle_months', $this->integer()->null());
            $this->addColumn($table, 'renewal_increase_percent', $this->decimal(5, 2)->null());
            $this->addColumn($table, 'renewal_base_amount', $this->decimal(10, 2)->null()->comment('Base amount used when no previous billed renewal exists'));
        }
    }

    #[\Override]
    public function safeDown(): void
    {
        foreach ($this->tables as $table) {
            $this->dropColumn($table, 'renewal_increase_percent');
            $this->dropColumn($table, 'renewal_cycle_months');
            $this->dropColumn($table, 'renewal_base_amount');
        }
    }
}
