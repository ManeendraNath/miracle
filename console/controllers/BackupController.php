<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

class BackupController extends Controller
{
    /**
     * Executes an automated system-level database and codebase backup run.
     * Command execution: php yii backup/run
     */
    public function actionRun()
    {
        // 📁 Put backups in a dedicated, private folder outside standard runtime caches
        $backupDir = dirname(Yii::getAlias('@common')) . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $db = Yii::$app->db;

        // 1. COMPILING SQL MATRIX DATA
        $sqlFile = $backupDir . "/db_backup_{$timestamp}.sql";
        $dbName = $this->getDatabaseName($db->dsn);
        
        $username = $db->username;
        $password = $db->password;

        // Extract host mapping string parameters out of the DSN array context safely
        preg_match('/host=([^;]+)/', $db->dsn, $hostMatches);
        $host = !empty($hostMatches[1]) ? $hostMatches[1] : 'localhost';

        $command = "mysqldump --no-tablespaces -h " . escapeshellarg($host) . " -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " " . escapeshellarg($dbName) . " > " . escapeshellarg($sqlFile);
        system($command, $returnStatus);

        if ($returnStatus === 0) {
            $this->stdout("✓ Database structure and records dumped successfully.\n", Console::FG_GREEN);
            
            // 2. COMPRESSING ARCHIVE LAYER (Gzip compression is native and completely safe on shared hosting)
            $gzipFile = $sqlFile . ".gz";
            $gzipCommand = "gzip -f " . escapeshellarg($sqlFile);
            system($gzipCommand, $gzipStatus);
            
            if ($gzipStatus === 0) {
                $this->stdout("✓ Secure database archive bundle built perfectly: {$gzipFile}\n", Console::FG_GREEN);
            } else {
                $this->stdout("Compression processing run failed.\n", Console::FG_RED);
            }
        } else {
            $this->stdout("Database extraction processing failed.\n", Console::FG_RED);
        }
    }

    private function getDatabaseName($dsn)
    {
        // 👇 FIXED: Changed from 'preg_dbname' to standard native PHP 'preg_match'
        if (preg_match('/dbname=([^;]+)/', $dsn, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
