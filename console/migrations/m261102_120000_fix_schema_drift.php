<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Brings databases that drifted from the migrations (found by `php yii schema/check`)
 * back in line. Every step checks first, so on a database that is already correct
 * this changes nothing.
 *
 * - invoice_item.source_invoice_id must be UNIQUE: an invoice can be carried forward
 *   as arrears only once. Duplicates abort the migration before any change.
 * - invoice.status gets its index (dashboard, reminders and billing filter on it).
 * - coupon.created_at / updated_at become NOT NULL everywhere (the model always sets
 *   them); any NULLs are filled first.
 */
class m261102_120000_fix_schema_drift extends Migration
{
    public function up()
    {
        // Validate before any DDL: MySQL cannot roll schema changes back.
        $duplicates = $this->db->createCommand(
            'SELECT source_invoice_id, COUNT(*) AS n FROM {{%invoice_item}}
             WHERE source_invoice_id IS NOT NULL GROUP BY source_invoice_id HAVING n > 1'
        )->queryAll();
        if ($duplicates !== []) {
            $list = implode(', ', array_map(static fn(array $r): string => "invoice #{$r['source_invoice_id']} ({$r['n']} items)", $duplicates));
            echo "    > ABORTED: invoices carried forward more than once: {$list}.\n"
                . "      Resolve these invoice_item rows manually, then re-run the migration.\n";
            return false;
        }

        if (!$this->hasIndex('{{%invoice_item}}', ['source_invoice_id'], true)) {
            // Create the unique index first so the foreign key can switch to it,
            // then drop the old non-unique one.
            $this->createIndex('uq-invoice_item-source_invoice', '{{%invoice_item}}', 'source_invoice_id', true);
            foreach ($this->indexNamesOn('{{%invoice_item}}', ['source_invoice_id'], false) as $name) {
                $this->dropIndex($name, '{{%invoice_item}}');
            }
        } else {
            echo "    > invoice_item.source_invoice_id already unique, skipped\n";
        }

        if (!$this->hasIndex('{{%invoice}}', ['status'])) {
            $this->createIndex('idx-invoice-status', '{{%invoice}}', 'status');
        } else {
            echo "    > invoice.status already indexed, skipped\n";
        }

        foreach (['created_at', 'updated_at'] as $column) {
            $schema = $this->db->getTableSchema('{{%coupon}}', true)->getColumn($column);
            if ($schema->allowNull) {
                $this->update('{{%coupon}}', [$column => time()], [$column => null]);
                $this->alterColumn('{{%coupon}}', $column, $this->integer()->notNull());
            } else {
                echo "    > coupon.{$column} already NOT NULL, skipped\n";
            }
        }

        return true;
    }

    public function down()
    {
        echo "m261102_120000_fix_schema_drift only restores missing constraints and cannot be reverted.\n";
        return false;
    }

    private function hasIndex(string $table, array $columns, bool $uniqueOnly = false): bool
    {
        foreach ($this->db->getSchema()->getTableIndexes($table, true) as $index) {
            if ($index->columnNames === $columns && (!$uniqueOnly || $index->isUnique)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string[] names of indexes covering exactly $columns with the given uniqueness
     */
    private function indexNamesOn(string $table, array $columns, bool $unique): array
    {
        $names = [];
        foreach ($this->db->getSchema()->getTableIndexes($table, true) as $index) {
            if ($index->columnNames === $columns && $index->isUnique === $unique && !$index->isPrimary) {
                $names[] = $index->name;
            }
        }
        return $names;
    }
}
