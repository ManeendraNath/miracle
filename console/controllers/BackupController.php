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

    /** Off in the admin panel stops the scheduled backup only: deploy.sh must still get its dump before migrating. */
    public function beforeAction($action): bool
    {
        if (!$this->dbOnly && !\common\models\CronJob::isEnabled($action->getUniqueId())) {
            $this->stdout("Turned off in the admin panel: skipping {$action->id}.\n", Console::FG_YELLOW);
            return false;
        }
        // As the cron jobs: code and schema may be half changed. deploy.sh's own dump runs under this flag on purpose.
        if (!$this->dbOnly && is_file(dirname(Yii::getAlias('@common')) . '/runtime/maintenance.flag')) {
            $this->stdout("Deployment in progress: skipping {$action->id}.\n", Console::FG_YELLOW);
            return false;
        }
        return parent::beforeAction($action);
    }

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['dbOnly']);
    }

    /** Set when a run ends normally; a fatal error before that is reported at shutdown. */
    private bool $finished = false;

    /**
     * Dumps the database to backups/db_backup_<timestamp>.sql.gz, archives the files that live only on disk
     * (customers' payment proofs, blog images) to backups/<name>_<timestamp>.tar, and prunes all older than
     * KEEP_DAYS. Failures alert admins.
     * Command execution: php yii backup/run
     */
    public function actionRun(): int
    {
        $backupDir = dirname(Yii::getAlias('@common')) . '/backups';
        if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true) && !is_dir($backupDir)) {
            return $this->fail("Cannot create {$backupDir}.", ExitCode::CANTCREAT);
        }

        // A fatal error (out of memory, time limit) skips every catch below: still tell the admins.
        $this->finished = false;
        register_shutdown_function(function (): void {
            $error = error_get_last();
            if (!$this->finished && $error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->fail('Backup stopped by a PHP fatal error: ' . $error['message'], ExitCode::SOFTWARE);
            }
        });

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
        $prunable = [$backupDir . '/db_backup_*.sql.gz'];
        // Files a restored database points at, which live only on this disk.
        $folders = [
            'receipts' => ['Payment proofs', dirname(Yii::getAlias('@common')) . '/storage/payment-receipts'],
            'blog_images' => ['Blog images', Yii::getAlias('@frontend/web/' . \common\models\BlogPost::UPLOAD_DIR)],
            'product_images' => ['Store images', Yii::getAlias('@frontend/web/' . \common\models\Product::UPLOAD_DIR)],
            'store_files' => ['Store downloads', Yii::getAlias('@storage/' . \common\models\Product::FILES_DIR)],
        ];
        foreach ($folders as $name => [$label, $source]) {
            try {
                $archive = $this->archiveFolder($backupDir, $name, $source);
                // Old archives go only once a new one exists: two weeks of failures, or of an emptied folder,
                // must not leave none.
                if ($archive !== null) {
                    $this->stdout("✓ {$label} archived: {$archive}\n", Console::FG_GREEN);
                    $status = $this->copyOffsite($archive) ?: $status;
                    $prunable[] = $backupDir . "/{$name}_*.tar";
                }
            } catch (\Throwable $e) {
                $status = $this->fail("{$label} archive failed: " . $e->getMessage(), ExitCode::UNSPECIFIED_ERROR);
            }
        }

        foreach ($prunable as $pattern) {
            foreach (glob($pattern) ?: [] as $old) {
                if (!str_ends_with($old, '.part.tar') && filemtime($old) < time() - self::KEEP_DAYS * 86400) {
                    unlink($old);
                }
            }
        }
        // Leftovers of dumps killed by a fatal error (e.g. out of memory) can't delete themselves.
        foreach ([...(glob($backupDir . '/db_backup_*.sql.gz.part') ?: []), ...(glob($backupDir . '/*.part.tar') ?: [])] as $stale) {
            if (filemtime($stale) < time() - 86400) {
                unlink($stale);
            }
        }
        $this->finished = true;

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
     * These files live only on disk, so a restored database would point at nothing without this.
     * ponytail: a full copy each run, kept KEEP_DAYS; keep fewer or go incremental if a folder gets big.
     */
    private function archiveFolder(string $backupDir, string $name, string $source): ?string
    {
        if (!is_dir($source) || (new \FilesystemIterator($source))->valid() === false) {
            return null;
        }
        $file = $backupDir . "/{$name}_" . date('Ymd_His') . '.tar';
        $part = $backupDir . "/{$name}_" . date('Ymd_His') . '.part.tar';
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
     * is read row by row (unbuffered), so table size is limited by disk, not memory.
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
                // Row by row from the server (unbuffered): a big table (e.g. blog_post_view) never sits in memory whole.
                $pdo = $db->getMasterPdo();
                $mysql = $db->driverName === 'mysql';
                if ($mysql) {
                    $pdo->setAttribute(\Pdo\Mysql::ATTR_USE_BUFFERED_QUERY, false);
                }
                $reader = $db->createCommand("SELECT {$columnList} FROM {$name}")->query();
                try {
                    foreach ($reader as $row) {
                        $rows[] = '(' . implode(',', array_map(static fn($v): string => $v === null ? 'NULL' : $db->quoteValue((string) $v), $row)) . ')';
                        if (count($rows) === 200) {
                            $write($insert . implode(",\n", $rows) . ";\n");
                            $rows = [];
                        }
                    }
                } finally {
                    // Closed even when a write fails: an open unbuffered read would block the ROLLBACK below, and the
                    // failure alert would then be saved inside a transaction that never commits.
                    $reader->close();
                    if ($mysql) {
                        $pdo->setAttribute(\Pdo\Mysql::ATTR_USE_BUFFERED_QUERY, true);
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
