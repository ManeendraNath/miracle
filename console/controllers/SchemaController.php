<?php

declare(strict_types=1);

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Detects drift between a live database and the schema the migrations produce.
 *
 * Read-only. Structure is compared, not names: an index or foreign key that
 * exists under a different name but covers the same columns is not drift.
 *
 *   php yii schema/check                 compare this DB with console/schema/expected.json
 *   php yii_test schema/snapshot         rewrite expected.json from the freshly migrated test DB
 */
class SchemaController extends Controller
{
    private const SNAPSHOT = '@console/schema/expected.json';

    /**
     * Writes the current database structure as the expected snapshot.
     * Run against a database built only by migrations (the test DB).
     */
    public function actionSnapshot(): int
    {
        $file = Yii::getAlias(self::SNAPSHOT);
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }
        $schema = $this->readSchema();
        file_put_contents($file, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        $this->stdout(sprintf("Snapshot of %d tables written to %s\n", count($schema['columns']), $file), Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Prints every difference between this database and the snapshot.
     * Exit code 1 when anything differs, so it can gate a deploy.
     */
    public function actionCheck(): int
    {
        $file = Yii::getAlias(self::SNAPSHOT);
        if (!is_file($file)) {
            $this->stderr("Snapshot not found: {$file}\n", Console::FG_RED);
            return ExitCode::CONFIG;
        }
        $expected = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $actual = $this->readSchema();

        $problems = [];
        foreach (['columns' => 'Column', 'indexes' => 'Index', 'foreignKeys' => 'Foreign key'] as $part => $label) {
            $tables = array_unique(array_merge(array_keys($expected[$part]), array_keys($actual[$part])));
            sort($tables);
            foreach ($tables as $table) {
                $want = $expected[$part][$table] ?? null;
                $have = $actual[$part][$table] ?? null;
                if ($part === 'columns' && ($want === null || $have === null)) {
                    $problems[] = $want === null ? "Table {$table}: exists in DB but not in migrations" : "Table {$table}: MISSING";
                    continue;
                }
                // A table with no indexes or foreign keys at all has no entry on that side.
                $want ??= [];
                $have ??= [];
                foreach (array_unique(array_merge(array_keys($want), array_keys($have))) as $key) {
                    if (!isset($have[$key])) {
                        $problems[] = "{$label} {$table}.{$key}: MISSING (expected {$this->describe($want[$key])})";
                    } elseif (!isset($want[$key])) {
                        $problems[] = "{$label} {$table}.{$key}: not in migrations ({$this->describe($have[$key])})";
                    } elseif ($want[$key] !== $have[$key]) {
                        $problems[] = "{$label} {$table}.{$key}: expected {$this->describe($want[$key])}, found {$this->describe($have[$key])}";
                    }
                }
            }
        }

        $applied = (int) Yii::$app->db->createCommand('SELECT COUNT(*) FROM {{%migration}}')->queryScalar() - 1; // minus base row
        $this->stdout('Database: ' . $this->dbName() . " ({$applied} migrations applied, snapshot has {$expected['migrations']})\n");
        if ($applied !== $expected['migrations']) {
            $this->stdout("Note: migration count differs from the snapshot; run pending migrations or refresh the snapshot first.\n", Console::FG_YELLOW);
        }

        // Applied migrations whose file is gone (renamed/deleted later) explain most drift.
        $files = array_map(static fn(string $f): string => basename($f, '.php'), glob(Yii::getAlias('@console/migrations') . '/*.php') ?: []);
        $orphans = array_diff(Yii::$app->db->createCommand("SELECT version FROM {{%migration}} WHERE version <> 'm000000_000000_base'")->queryColumn(), $files);
        foreach ($orphans as $version) {
            $this->stdout("Note: applied migration {$version} has no file in console/migrations.\n", Console::FG_YELLOW);
        }

        if ($problems === []) {
            $this->stdout("No schema drift found.\n", Console::FG_GREEN);
            return ExitCode::OK;
        }
        foreach ($problems as $problem) {
            $this->stdout('  - ' . $problem . "\n", Console::FG_RED);
        }
        $this->stdout(count($problems) . " difference(s).\n", Console::FG_RED);
        return ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * @return array{migrations: int, columns: array, indexes: array, foreignKeys: array}
     */
    private function readSchema(): array
    {
        $db = Yii::$app->db;
        $schema = $this->dbName();

        $columns = [];
        foreach (
            $db->createCommand(
                'SELECT table_name t, column_name c, column_type type, is_nullable n, column_default d, extra e
                 FROM information_schema.columns WHERE table_schema = :s ORDER BY table_name, ordinal_position',
                [':s' => $schema]
            )->queryAll() as $r
        ) {
            $columns[$r['t']][$r['c']] = [
                // MySQL 8 drops int display widths; normalise older "int(11)" style too.
                'type' => preg_replace('/^(tinyint|smallint|mediumint|int|bigint)\(\d+\)/', '$1', $r['type']),
                'null' => $r['n'] === 'YES',
                'default' => $this->normaliseDefault($r['d']),
                'extra' => trim(str_replace('DEFAULT_GENERATED', '', $r['e'])),
            ];
        }

        $indexes = [];
        foreach (
            $db->createCommand(
                'SELECT table_name t, index_name i, non_unique nu, GROUP_CONCAT(column_name ORDER BY seq_in_index) cols
                 FROM information_schema.statistics WHERE table_schema = :s GROUP BY table_name, index_name, non_unique',
                [':s' => $schema]
            )->queryAll() as $r
        ) {
            // Keyed by columns, not name: MySQL reuses or auto-creates indexes behind
            // foreign keys under varying names, but the covered columns are what matter.
            if ($r['nu'] === '0' || $r['nu'] === 0) {
                $indexes[$r['t']][$r['cols']] = $r['i'] === 'PRIMARY' ? 'primary' : 'unique';
            } else {
                $indexes[$r['t']][$r['cols']] ??= 'index';
            }
        }

        $foreignKeys = [];
        foreach (
            $db->createCommand(
                'SELECT k.table_name t, GROUP_CONCAT(k.column_name ORDER BY k.ordinal_position) cols,
                        k.referenced_table_name rt, GROUP_CONCAT(k.referenced_column_name ORDER BY k.ordinal_position) rcols,
                        r.delete_rule dr, r.update_rule ur
                 FROM information_schema.key_column_usage k
                 JOIN information_schema.referential_constraints r
                   ON r.constraint_schema = k.constraint_schema AND r.constraint_name = k.constraint_name AND r.table_name = k.table_name
                 WHERE k.constraint_schema = :s AND k.referenced_table_name IS NOT NULL
                 GROUP BY k.table_name, k.constraint_name, k.referenced_table_name, r.delete_rule, r.update_rule',
                [':s' => $schema]
            )->queryAll() as $r
        ) {
            // InnoDB treats NO ACTION exactly like RESTRICT; only the spelling differs between migrations.
            $rule = static fn(string $r): string => $r === 'NO ACTION' ? 'RESTRICT' : $r;
            $foreignKeys[$r['t']][$r['cols']] = "{$r['rt']}({$r['rcols']}) ON DELETE {$rule($r['dr'])} ON UPDATE {$rule($r['ur'])}";
        }

        foreach ([&$columns, &$indexes, &$foreignKeys] as &$part) {
            ksort($part);
            foreach ($part as &$entries) {
                ksort($entries);
            }
        }
        unset($part, $entries);

        // The migration table itself differs by history (applied times), so leave it out.
        unset($columns['migration'], $indexes['migration']);

        return [
            'migrations' => (int) $db->createCommand('SELECT COUNT(*) FROM {{%migration}}')->queryScalar() - 1,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreignKeys' => $foreignKeys,
        ];
    }

    /**
     * MariaDB reports defaults as SQL literals ('NULL', quoted strings,
     * current_timestamp()); MySQL reports raw values. Compare them the same way.
     */
    private function normaliseDefault(?string $default): ?string
    {
        if ($default === null || strtoupper($default) === 'NULL') {
            return null;
        }
        if (preg_match("/^'(.*)'$/s", $default, $m)) {
            return str_replace("''", "'", $m[1]);
        }
        return preg_match('/^current_timestamp(\(\))?$/i', $default) ? 'CURRENT_TIMESTAMP' : $default;
    }

    private function dbName(): string
    {
        return (string) Yii::$app->db->createCommand('SELECT DATABASE()')->queryScalar();
    }

    private function describe(mixed $value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES) : (string) $value;
    }
}
