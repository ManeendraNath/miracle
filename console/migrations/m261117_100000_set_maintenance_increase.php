<?php

declare(strict_types=1);

use yii\db\Migration;

/** Maintenance rises 10% every three years. Services with their own increase keep it. */
final class m261117_100000_set_maintenance_increase extends Migration
{
    public function safeUp(): void
    {
        $this->update('{{%billing_settings}}', ['renewal_increase_percent' => 10], ['service_type' => 3]);
    }

    public function safeDown(): void
    {
        $this->update('{{%billing_settings}}', ['renewal_increase_percent' => 0], ['service_type' => 3]);
    }
}
