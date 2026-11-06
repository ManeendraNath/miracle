<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Indexes for the admin lists, reports and daily cron scans, which otherwise read whole tables as data grows.
 *
 * MySQL DDL isn't transactional, so each one can be added again after a failure partway.
 */
final class m261121_100000_add_lookup_indexes extends Migration
{
    private const INDEXES = [
        'idx-transaction-paid_at' => ['{{%transaction}}', ['paid_at']],
        'idx-invoice-due_date' => ['{{%invoice}}', ['due_date']],
        'idx-invoice-created_at' => ['{{%invoice}}', ['created_at']],
        'idx-hosting-status-expiry' => ['{{%hosting}}', ['status', 'current_expiry_date']],
        'idx-domains-status-expiry' => ['{{%domains}}', ['status', 'current_expiry_date']],
        'idx-maintenance-status-expiry' => ['{{%maintenance}}', ['status', 'current_expiry_date']],
        'idx-admin_notification-read-created' => ['{{%admin_notification}}', ['is_read', 'created_at']],
        'idx-user-status' => ['{{%user}}', ['status']],
        'idx-support_ticket-status' => ['{{%support_ticket}}', ['status']],
        'idx-support_ticket-updated_at' => ['{{%support_ticket}}', ['updated_at']],
    ];

    public function safeUp(): void
    {
        foreach (self::INDEXES as $name => [$table, $columns]) {
            if (!$this->hasIndex($table, $name)) {
                $this->createIndex($name, $table, $columns);
            }
        }
    }

    public function safeDown(): void
    {
        foreach (self::INDEXES as $name => [$table]) {
            if ($this->hasIndex($table, $name)) {
                $this->dropIndex($name, $table);
            }
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return array_filter($this->db->schema->getTableIndexes($table, true), static fn($index): bool => $index->name === $name) !== [];
    }
}
