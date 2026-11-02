<?php

// Tests run against a separate database so they can never touch dev data.
// TEST_DB_DSN in .env overrides; default is DB_DSN with "_test" appended to dbname.
$testDsn = $_ENV['TEST_DB_DSN'] ?? preg_replace('/dbname=([^;]+)/', 'dbname=$1_test', $_ENV['DB_DSN'] ?? '');

return [
    'components' => [
        'db' => [
            'dsn' => $testDsn,
        ],
    ],
];
