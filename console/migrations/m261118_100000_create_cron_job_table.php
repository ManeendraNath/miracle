<?php

declare(strict_types=1);

use yii\db\Migration;

/** On/off switches for the cron jobs. No row = on, so a job never stops just because nobody has touched it. */
final class m261118_100000_create_cron_job_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%cron_job}}', [
            'job' => $this->string(64)->notNull(),
            'is_enabled' => $this->boolean()->notNull()->defaultValue(true),
            'updated_by' => $this->string(255)->null(),
            'updated_at' => $this->integer()->null(),
            'PRIMARY KEY(job)',
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%cron_job}}');
    }
}
