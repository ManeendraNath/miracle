<?php
/*
 * Sets up the billing portal on this server: php install.php (INSTALL.md).
 * Written so that any PHP can read it: an old PHP must get this message, not a syntax error from the steps.
 */
// 8.4.1: what the libraries in vendor/ need (composer.json "php").
if (version_compare(PHP_VERSION, '8.4.1', '<')) {
    fwrite(STDERR, 'PHP 8.4.1 or newer is needed; this command line runs PHP ' . PHP_VERSION . '. On cPanel, run it as'
        . " /usr/local/bin/ea-php84 install.php (or the PHP 8.4.1+ path your host gives), or choose a newer PHP version.\n");
    exit(1);
}
require __DIR__ . '/console/install-steps.php';
