<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Software sold by subscription (product.kind = software, e.g. the billing portal): the website a licence is for, and
 * when an installed copy last checked its licence with us. MySQL can't roll back table changes: each step checks.
 */
final class m261219_100000_software_licences extends Migration
{
    public function safeUp(): void
    {
        $table = $this->db->getTableSchema('{{%product_order}}', true);
        if ($table->getColumn('licence_site') === null) {
            // Host name the licence works on, e.g. billing.example.com (from the website given at checkout).
            $this->addColumn('{{%product_order}}', 'licence_site', $this->string(255)->null()->after('licence_key'));
        }
        if ($table->getColumn('licence_checked_at') === null) {
            $this->addColumn('{{%product_order}}', 'licence_checked_at', $this->integer()->null()->after('licence_site'));
        }
    }

    public function safeDown(): void
    {
        $table = $this->db->getTableSchema('{{%product_order}}', true);
        foreach (['licence_checked_at', 'licence_site'] as $column) {
            if ($table->getColumn($column) !== null) {
                $this->dropColumn('{{%product_order}}', $column);
            }
        }
    }
}
