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

        $db = Yii::$app->db;
        $dsn = $db->dsn;
        $host = preg_match('/host=([^;]+)/', $dsn, $m) ? $m[1] : 'localhost';
        $port = preg_match('/port=([^;]+)/', $dsn, $m) ? $m[1] : '3306';
        $dbName = preg_match('/dbname=([^;]+)/', $dsn, $m) ? $m[1] : '';

        $sqlFile = $backupDir . '/db_backup_' . date('Ymd_His') . '.sql';

        // Password via MYSQL_PWD, not argv, so it doesn't show up in `ps` on shared hosting.
        putenv('MYSQL_PWD=' . $db->password);
        $oldUmask = umask(0077);
        $command = 'mysqldump --no-tablespaces --single-transaction'
                . ' -h ' . escapeshellarg($host)
                . ' -P ' . escapeshellarg($port)
                . ' -u ' . escapeshellarg((string) $db->username)
                . ' ' . escapeshellarg($dbName)
                . ' > ' . escapeshellarg($sqlFile);
        system($command, $dumpStatus);
        putenv('MYSQL_PWD');

        if ($dumpStatus !== 0) {
            umask($oldUmask);
            @unlink($sqlFile);
            $this->stderr("Database dump failed (exit {$dumpStatus}).\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        system('gzip -f ' . escapeshellarg($sqlFile), $gzipStatus);
        umask($oldUmask);
        if ($gzipStatus !== 0) {
            $this->stderr("Compression failed; uncompressed dump kept at {$sqlFile}.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("✓ Database backup written: {$sqlFile}.gz\n", Console::FG_GREEN);

        $offsiteStatus = $this->copyOffsite($sqlFile . '.gz');

        foreach (glob($backupDir . '/db_backup_*.sql.gz') ?: [] as $old) {
            if (filemtime($old) < time() - self::KEEP_DAYS * 86400) {
                unlink($old);
            }
        }

        return $offsiteStatus;
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
