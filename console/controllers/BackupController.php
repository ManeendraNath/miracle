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
        $backupDir = Yii::getAlias('@runtime/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $db = Yii::$app->db;

        // 1. COMPILING SQL MATRIX DATA
        $sqlFile = $backupDir . "/db_backup_{$timestamp}.sql";
        $dbName = $this->getDatabaseName($db->dsn);
        
        // Parse database configurations securely out of environmental arrays
        $username = $db->username;
        $password = $db->password;

        $command = "mysqldump --no-tablespaces -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " " . escapeshellarg($dbName) . " > " . escapeshellarg($sqlFile);
        system($command, $returnStatus);

        if ($returnStatus === 0) {
            $this->stdout("✓ Database structure and records dumped successfully.\n", Console::FG_GREEN);
            
            // 2. COMPRESSING ARCHIVE LAYER
            $zipFile = $backupDir . "/miracle_system_backup_{$timestamp}.zip";
            $rootPath = Yii::getAlias('@vendor/../'); // Evaluates safely to the workspace root folder
            
            $zipCommand = "zip -r " . escapeshellarg($zipFile) . " " . escapeshellarg($rootPath) . " -x '*/runtime/*' '*/vendor/*' '*/.git/*'";
            system($zipCommand, $zipStatus);
            
            if ($zipStatus === 0) {
                // Wipe the raw temporary sql dump after a successful zip run
                @unlink($sqlFile);
                $this->stdout("✓ Secure codebase archive bundle built perfectly: {$zipFile}\n", Console::FG_GREEN);
            } else {
                $this->stdout("Source package compilation run failed.\n", Console::FG_RED);
            }
        } else {
            $this->stdout("Database extraction processing failed.\n", Console::FG_RED);
        }
    }

    private function getDatabaseName($dsn)
    {
        if (preg_dbname('/dbname=([^;]+)/', $dsn, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
