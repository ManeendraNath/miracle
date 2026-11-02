<?php

declare(strict_types=1);

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class BackupController extends Controller
{
    private const KEEP_DAYS = 14;

    /**
     * Dumps the database to backups/db_backup_<timestamp>.sql.gz and prunes
     * dumps older than KEEP_DAYS.
     * Command execution: php yii backup/run
     */
    public function actionRun(): int
    {
        $backupDir = dirname(Yii::getAlias('@common')) . '/backups';
        if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true) && !is_dir($backupDir)) {
            $this->stderr("Cannot create {$backupDir}.\n", Console::FG_RED);
            return ExitCode::CANTCREAT;
        }

        $file = $backupDir . '/db_backup_' . date('Ymd_His') . '.sql.gz';
        $oldUmask = umask(0077);
        try {
            $this->dump($file);
        } catch (\Throwable $e) {
            @unlink($file);
            $this->stderr('Database dump failed: ' . $e->getMessage() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            umask($oldUmask);
        }
        $this->stdout("✓ Database backup written: {$file}\n", Console::FG_GREEN);

        $offsiteStatus = $this->copyOffsite($file);

        foreach (glob($backupDir . '/db_backup_*.sql.gz') ?: [] as $old) {
            if (filemtime($old) < time() - self::KEEP_DAYS * 86400) {
                unlink($old);
            }
        }

        return $offsiteStatus;
    }

    /**
     * Writes a gzipped SQL dump (tables + rows) through the app's DB connection.
     * Pure PHP: shared hosting disables system()/exec(), so mysqldump can't be used.
     * Restore: gunzip -c <file> | mysql <database>
     * ponytail: tables only (the schema has no views/triggers/routines); each table
     * is read in one buffered query, switch to unbuffered reads if a table outgrows memory.
     */
    private function dump(string $file): void
    {
        $db = Yii::$app->db;
        $gz = gzopen($file, 'wb6');
        if ($gz === false) {
            throw new \RuntimeException("cannot write {$file}");
        }
        try {
            gzwrite($gz, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
            // One snapshot for all tables, so rows written meanwhile can't half-appear.
            $db->createCommand('START TRANSACTION WITH CONSISTENT SNAPSHOT')->execute();
            foreach ($db->createCommand("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->queryColumn() as $table) {
                $name = $db->quoteTableName($table);
                gzwrite($gz, "DROP TABLE IF EXISTS {$name};\n" . $db->createCommand("SHOW CREATE TABLE {$name}")->queryOne()['Create Table'] . ";\n");
                $rows = [];
                foreach ($db->createCommand("SELECT * FROM {$name}")->queryAll() as $row) {
                    $rows[] = '(' . implode(',', array_map(static fn($v): string => $v === null ? 'NULL' : $db->quoteValue((string) $v), $row)) . ')';
                    if (count($rows) === 200) {
                        gzwrite($gz, "INSERT INTO {$name} VALUES\n" . implode(",\n", $rows) . ";\n");
                        $rows = [];
                    }
                }
                if ($rows !== []) {
                    gzwrite($gz, "INSERT INTO {$name} VALUES\n" . implode(",\n", $rows) . ";\n");
                }
                gzwrite($gz, "\n");
            }
            $db->createCommand('COMMIT')->execute();
            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            if (!gzclose($gz)) {
                throw new \RuntimeException("cannot finish writing {$file}");
            }
        }
    }

    /**
     * Runs BACKUP_OFFSITE_COMMAND from .env with {file} replaced by the dump path,
     * e.g. `rclone copy {file} gdrive:miracle-backups` or `scp -q {file} backup@host:/backups/`.
     * Not set = local copy only. A failed upload exits non-zero so cron reports it;
     * the local dump is kept either way.
     */
    private function copyOffsite(string $file): int
    {
        $template = trim((string) (Yii::$app->params['backupOffsiteCommand'] ?? ''));
        if ($template === '') {
            $this->stdout("Off-site copy not configured (BACKUP_OFFSITE_COMMAND); backup is local only.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }
        if (!str_contains($template, '{file}')) {
            $this->stderr("BACKUP_OFFSITE_COMMAND must contain {file}.\n", Console::FG_RED);
            return ExitCode::CONFIG;
        }
        if (!function_exists('exec')) {
            $this->stderr("BACKUP_OFFSITE_COMMAND is set but exec() is disabled on this server; backup is local only.\n", Console::FG_RED);
            return ExitCode::CONFIG;
        }

        exec(str_replace('{file}', escapeshellarg($file), $template) . ' 2>&1', $output, $status);
        if ($status !== 0) {
            Yii::error(['message' => 'Off-site backup copy failed.', 'file' => $file, 'exit' => $status, 'output' => implode("\n", $output)], __METHOD__);
            $this->stderr("Off-site backup copy failed (exit {$status}): " . implode(' ', $output) . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("✓ Off-site copy done.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
