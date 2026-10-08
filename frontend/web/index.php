<?php

// 1. Load the composer autoloader matrix components
require __DIR__ . '/../../vendor/autoload.php';

// 2. Safely parse environment variables out of your root .env file
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->safeLoad();

// 3. ENTIRELY DYNAMIC CONFIGURATION (No hardcoding)
defined('YII_DEBUG') or define('YII_DEBUG', isset($_ENV['YII_DEBUG']) ? ($_ENV['YII_DEBUG'] === 'true') : false);
defined('YII_ENV') or define('YII_ENV', $_ENV['YII_ENV'] ?? 'prod');

// 4. Boot up standard framework core engines
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/components/CustomYii.php'; 
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../config/main.php',
    require __DIR__ . '/../config/main-local.php'
);

(new yii\web\Application($config))->run();
