<?php

declare(strict_types=1);

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class BackupController extends Controller
{
    use \console\traits\LogLinePrefixTrait;

    private const KEEP_DAYS = 14;

    /** Only write the database dump (deploy.sh, before migrating): no proofs, off-site copy or pruning. */
    public bool $dbOnly = false;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['dbOnly']);
    }

    /**
     * Dumps the database to backups/db_backup_<timestamp>.sql.gz, archives customers' payment proofs to
     * backups/receipts_<timestamp>.tar, and prunes both older than KEEP_DAYS. Failures alert admins.
     * Command execution: php yii backup/run
     */
    public function actionRun(): int
    {
        $backupDir = dirname(Yii::getAlias('@common')) . '/backups';
        if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true) && !is_dir($backupDir)) {
            return $this->fail("Cannot create {$backupDir}.", ExitCode::CANTCREAT);
        }

        $file = $backupDir . '/db_backup_' . date('Ymd_His') . '.sql.gz';
        $oldUmask = umask(0077);
        try {
            $this->dump($file);
        } catch (\Throwable $e) {
            @unlink($file);
            return $this->fail('Database dump failed: ' . $e->getMessage(), ExitCode::UNSPECIFIED_ERROR);
        } finally {
            umask($oldUmask);
        }
        $this->stdout("✓ Database backup written: {$file}\n", Console::FG_GREEN);
        if ($this->dbOnly) {
            return ExitCode::OK;
        }

        $status = $this->copyOffsite($file);
        $receiptsSafe = false;
        try {
            $receipts = $this->archiveReceipts($backupDir);
            if ($receipts !== null) {
                $this->stdout("✓ Payment proofs archived: {$receipts}\n", Console::FG_GREEN);
                $status = $this->copyOffsite($receipts) ?: $status;
            }
            $receiptsSafe = true;
        } catch (\Throwable $e) {
            $status = $this->fail('Payment proof archive failed: ' . $e->getMessage(), ExitCode::UNSPECIFIED_ERROR);
        }

        // Old proofs stay while new archives fail: otherwise two weeks of failures would leave none.
        foreach ([...(glob($backupDir . '/db_backup_*.sql.gz') ?: []), ...($receiptsSafe ? glob($backupDir . '/receipts_*.tar') ?: [] : [])] as $old) {
            if (filemtime($old) < time() - self::KEEP_DAYS * 86400) {
                unlink($old);
            }
        }
        // Leftovers of dumps killed by a fatal error (e.g. out of memory) can't delete themselves.
        foreach ([...(glob($backupDir . '/db_backup_*.sql.gz.part') ?: []), ...(glob($backupDir . '/receipts_*.part.tar') ?: [])] as $stale) {
            if (filemtime($stale) < time() - 86400) {
                unlink($stale);
            }
        }

        return $status;
    }

    private function fail(string $message, int $code): int
    {
        Yii::error($message, __METHOD__);
        $this->stderr($message . "\n", Console::FG_RED);
        try {
            \common\services\admin\AdminAlertService::backupFailed($message);
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
        }
        return $code;
    }

    /**
     * Proofs live only on disk, so a restored database would point at nothing without this.
     * ponytail: a full copy each run, kept KEEP_DAYS; keep fewer or go incremental if the folder gets big.
     */
    private function archiveReceipts(string $backupDir): ?string
    {
        $source = dirname(Yii::getAlias('@common')) . '/storage/payment-receipts';
        if (!is_dir($source) || (new \FilesystemIterator($source))->valid() === false) {
            return null;
        }
        $file = $backupDir . '/receipts_' . date('Ymd_His') . '.tar';
        $part = $backupDir . '/receipts_' . date('Ymd_His') . '.part.tar';
        $oldUmask = umask(0077);
        try {
            (new \PharData($part))->buildFromDirectory($source);
            if (!rename($part, $file)) {
                throw new \RuntimeException("cannot rename {$part}");
            }
        } catch (\Throwable $e) {
            @unlink($part);
            throw $e;
        } finally {
            umask($oldUmask);
        }
        return $file;
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
        // Written under a temporary name and renamed at the end, so a dump cut short (error, fatal
        // out-of-memory, full disk) never sits in the backup folder looking like a good one.
        $part = $file . '.part';
        $gz = gzopen($part, 'wb6');
        if ($gz === false) {
            throw new \RuntimeException("cannot write {$part}");
        }
        // gzwrite() reports a full disk only through its return value; gzclose() always returns true.
        $written = 0;
        $write = static function (string $data) use ($gz, $part, &$written): void {
            if ($data !== '' && gzwrite($gz, $data) !== strlen($data)) {
                throw new \RuntimeException("cannot write {$part} (disk full?)");
            }
            $written += strlen($data);
        };
        try {
            $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
            // One snapshot for all tables, so rows written meanwhile can't half-appear.
            // The connection defaults to READ COMMITTED (common/config/main.php); a consistent
            // snapshot needs REPEATABLE READ for this one transaction.
            $db->createCommand('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ')->execute();
            $db->createCommand('START TRANSACTION WITH CONSISTENT SNAPSHOT')->execute();
            foreach ($db->createCommand("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->queryColumn() as $table) {
                $name = $db->quoteTableName($table);
                $write("DROP TABLE IF EXISTS {$name};\n" . $db->createCommand("SHOW CREATE TABLE {$name}")->queryOne()['Create Table'] . ";\n");
                // Generated columns (e.g. billing_event.live_slot) are left out: MySQL rejects
                // explicit values for them, which made restores stop at that table.
                $columns = $db->createCommand(
                    "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND EXTRA NOT IN ('VIRTUAL GENERATED', 'STORED GENERATED') ORDER BY ORDINAL_POSITION",
                    [':t' => $table],
                )->queryColumn();
                $columnList = implode(', ', array_map([$db, 'quoteColumnName'], $columns));
                $insert = "INSERT INTO {$name} ({$columnList}) VALUES\n";
                $rows = [];
                foreach ($db->createCommand("SELECT {$columnList} FROM {$name}")->queryAll() as $row) {
                    $rows[] = '(' . implode(',', array_map(static fn($v): string => $v === null ? 'NULL' : $db->quoteValue((string) $v), $row)) . ')';
                    if (count($rows) === 200) {
                        $write($insert . implode(",\n", $rows) . ";\n");
                        $rows = [];
                    }
                }
                if ($rows !== []) {
                    $write($insert . implode(",\n", $rows) . ";\n");
                }
                $write("\n");
            }
            $db->createCommand('COMMIT')->execute();
            $write("SET FOREIGN_KEY_CHECKS=1;\n");
        } catch (\Throwable $e) {
            // Left open, the failure alert written next would sit in this transaction and vanish.
            try {
                $db->createCommand('ROLLBACK')->execute();
            } catch (\Throwable) {
                // The connection itself may be what failed.
            }
            gzclose($gz);
            @unlink($part);
            throw $e;
        }
        gzclose($gz);
        // gzclose() writes the last block and the trailer but can't report failure; the trailer's
        // last 4 bytes hold the uncompressed size, so a complete file must end with ours.
        $handle = fopen($part, 'rb');
        $tail = $handle !== false && fseek($handle, -4, SEEK_END) === 0 ? fread($handle, 4) : false;
        if ($handle !== false) {
            fclose($handle);
        }
        if (!is_string($tail) || strlen($tail) !== 4 || unpack('V', $tail)[1] !== ($written & 0xFFFFFFFF)) {
            @unlink($part);
            throw new \RuntimeException("{$part} is incomplete (disk full?)");
        }
        if (!rename($part, $file)) {
            @unlink($part);
            throw new \RuntimeException("cannot rename {$part}");
        }
    }

    /**
     * Runs BACKUP_OFFSITE_COMMAND from .env with {file} replaced by the dump path,
     * e.g. `rclone copy {file} gdrive:miracle-backups` or `scp -q {file} backup@host:/backups/` (quote it in .env).
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
            return $this->fail('BACKUP_OFFSITE_COMMAND must contain {file}.', ExitCode::CONFIG);
        }
        if (!function_exists('exec')) {
            return $this->fail('BACKUP_OFFSITE_COMMAND is set but exec() is disabled on this server; backup is local only.', ExitCode::CONFIG);
        }

        exec(str_replace('{file}', escapeshellarg($file), $template) . ' 2>&1', $output, $status);
        if ($status !== 0) {
            Yii::error(['message' => 'Off-site backup copy failed.', 'file' => $file, 'exit' => $status, 'output' => implode("\n", $output)], __METHOD__);
            // Exit status only outside the app log: the command's output may name credentials or paths.
            return $this->fail("Off-site backup copy failed (exit {$status}).", ExitCode::UNSPECIFIED_ERROR);
        }
        $this->stdout("✓ Off-site copy done.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
