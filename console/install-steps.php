<?php

/**
 * The steps of php install.php (which checks the PHP version first, in syntax any PHP can read): sets up a copy of
 * the billing portal on a new server. It checks the server, asks for the database, web addresses, email and business
 * details, writes .env, creates the tables and puts the business's details in place of ours. Safe to run again: an
 * existing .env is kept (delete it to start over) and finished steps are skipped.
 *
 * Permissions assume PHP runs as the account that owns the files, as on cPanel hosting.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run this from a terminal: php install.php\n");
}
chdir(dirname(__DIR__));

function say(string $text): void
{
    fwrite(STDOUT, $text . "\n");
}

/** Typing shown again: after a password, and when the installer ends (an error, no more input, or Ctrl-C with pcntl). */
function showTyping(): void
{
    if (!empty($GLOBALS['typingHidden'])) {
        system('stty echo');
        $GLOBALS['typingHidden'] = false;
    }
}

register_shutdown_function('showTyping');
if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGINT, static function (): void {
        showTyping();
        exit(130);
    });
}

function ask(string $question, string $default = '', bool $required = true, ?callable $valid = null, bool $secret = false): string
{
    // A password is not shown as it is typed, where the terminal allows. Typing shows again after it, and when the
    // installer ends; only Ctrl-C without pcntl cannot, so the prompt says what to do then.
    $hide = $secret && function_exists('posix_isatty') && posix_isatty(STDIN) && function_exists('system');
    if ($hide && !function_exists('pcntl_signal')) {
        $question .= ' (not shown; if you press Ctrl-C here, type stty echo afterwards)';
    }
    while (true) {
        fwrite(STDOUT, $question . ($default !== '' ? " [{$default}]" : '') . ': ');
        if ($hide) {
            system('stty -echo');
            $GLOBALS['typingHidden'] = true;
        }
        $line = fgets(STDIN);
        if ($line === false) {
            // No more input (the terminal closed, or nothing piped in): stop instead of asking forever.
            say("\nNo answer: stopped. Run php install.php again in a terminal.");
            exit(1);
        }
        // A password is kept exactly as typed (spaces too); other answers are trimmed.
        $answer = $secret ? rtrim($line, "\r\n") : trim($line);
        if ($hide) {
            showTyping();
            fwrite(STDOUT, "\n");
        }
        $answer = $answer === '' ? $default : $answer;
        if ($answer === '' && $required) {
            say('  Please give a value.');
            continue;
        }
        if ($answer !== '' && $valid !== null && ($error = $valid($answer)) !== null) {
            say('  ' . $error);
            continue;
        }

        return $answer;
    }
}

function run(string $command): void
{
    say("\n> {$command}");
    passthru($command, $code);
    if ($code !== 0) {
        say("\nThat step failed (exit code {$code}). Fix the problem above and run php install.php again.");
        exit(1);
    }
}

/** A value as .env needs it: quoted when it has spaces or symbols that would break the line. */
function envValue(string $value): string
{
    return preg_match('/^[A-Za-z0-9_.:\/@+-]*$/', $value) === 1 ? $value : '"' . addcslashes($value, '"\\$') . '"';
}

// https only: sign-in cookies are sent over SSL only (COOKIE_SECURE), so set up the certificate first.
$url = static fn(string $v): ?string => preg_match('#^https://[a-z0-9.-]+\.[a-z0-9-]{2,}(:\d+)?/?$#i', $v) === 1 ? null : 'Give just the https address, no folder after it, e.g. https://billing.example.com (set up its SSL certificate first)';
$email = static fn(string $v): ?string => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Give an email address.';

say('Billing portal setup');
say('====================');

// 1. The server.
$problems = [];
// The installer runs the console commands (migrations) with passthru(); some hosts switch it off. Then it still
// writes .env, and says which two commands to run by hand.
$canRun = function_exists('passthru');
foreach (['pdo_mysql', 'mbstring', 'curl', 'openssl', 'intl', 'gd', 'zip', 'fileinfo'] as $extension) {
    if (!extension_loaded($extension)) {
        $problems[] = "The PHP extension {$extension} is missing. Turn it on in cPanel (Select PHP Version > Extensions).";
    }
}
foreach (['vendor/autoload.php', 'common/config/params-local.php', 'yii'] as $part) {
    if (!is_file($part)) {
        $problems[] = "{$part} is missing: upload the whole package as it came.";
    }
}
if ($problems !== []) {
    say("\nThe server needs these first:\n- " . implode("\n- ", $problems));
    exit(1);
}
say('Server: OK (PHP ' . PHP_VERSION . ').');

// 2. .env, unless one is already there.
if (is_file('.env')) {
    say("\n.env already exists: keeping it. (Delete it to answer the questions again.)");
} else {
    say("\nAnswer these; press Enter to take the value in [brackets].\n");
    say('-- Database (create it, and its user, in cPanel > MySQL Databases first) --');
    $dbHost = ask('Database host', 'localhost', true, static fn(string $v): ?string => preg_match('/^[A-Za-z0-9.-]+(:\d{1,5})?$/', $v) === 1 ? null : 'A host name, with :port if not 3306, e.g. localhost');
    // host:port as PDO wants it: host=...;port=...
    [$dbHostName, $dbPort] = array_pad(explode(':', $dbHost, 2), 2, '');
    // localhost means MySQL's socket to PDO, which ignores a port: with one, go by TCP instead.
    $dbServer = 'host=' . ($dbPort !== '' && strtolower($dbHostName) === 'localhost' ? '127.0.0.1' : $dbHostName) . ($dbPort !== '' ? ';port=' . $dbPort : '');
    // Plain names only: they go into the connection string, where ; or = would start new settings.
    $dbNameRule = static fn(string $v): ?string => preg_match('/^[A-Za-z0-9_]{1,64}$/', $v) === 1 ? null : 'Letters, digits and _ only, as cPanel shows it.';
    $dbName = ask('Database name', '', true, $dbNameRule);
    $dbUser = ask('Database user', '', true, $dbNameRule);
    $dbPass = ask('Database password', '', false, null, true);
    try {
        new PDO("mysql:{$dbServer};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
    } catch (PDOException $e) {
        say('Could not connect to the database: ' . $e->getMessage());
        exit(1);
    }

    say("\n-- Web addresses --");
    $portal = rtrim(ask('Customer portal address', '', true, $url), '/');
    // billing.example.com -> admin.example.com (the guide's pair); otherwise admin. in front (acme.co.in ->
    // admin.acme.co.in): a domain ending such as .co.in cannot be told from a subdomain by counting dots.
    $portalHost = (string) parse_url($portal, PHP_URL_HOST);
    $portalHost = (string) preg_replace('/^www\./', '', $portalHost);
    $adminHost = str_starts_with($portalHost, 'billing.') ? 'admin.' . substr($portalHost, 8) : 'admin.' . $portalHost;
    $admin = rtrim(ask('Admin panel address', 'https://' . $adminHost, true, $url), '/');

    say("\n-- Email (your mailbox's outgoing server, from cPanel > Email Accounts > Connect Devices) --");
    $smtpUser = ask('Email address to send from (its full address, also the SMTP user)', '', true, $email);
    // cPanel's mail server is mail.<the mailbox's domain>.
    $smtpHost = ask('SMTP server', 'mail.' . substr((string) strrchr($smtpUser, '@'), 1));
    $smtpPort = ask('SMTP port', '465', true, static fn(string $v): ?string => preg_match('/^\d{1,5}$/', $v) === 1 && (int) $v >= 1 && (int) $v <= 65535 ? null : 'A port number, usually 465 or 587.');
    $smtpPass = ask('SMTP password', '', true, null, true);
    $sender = ask('Send emails from', $smtpUser, true, $email);
    $support = ask('Support email customers write to', $sender, true, $email);
    $adminEmail = ask('Your email (staff alerts)', $sender, true, $email);

    say("\n-- Your business --");
    $business = ask('Business name');
    $website = ask('Your own website, linked from the portal and emails (Enter if none)', '', false, static fn(string $v): ?string => preg_match('#^https?://[^\s/]+\.[^\s]+$#', $v) === 1 ? null : 'A full address, e.g. https://example.com');
    $state = ask('State where you are registered for GST (e.g. Delhi)');
    $stateCode = ask('Its two-digit GST state code (e.g. 07 for Delhi)', '', true, static fn(string $v): ?string => preg_match('/^\d{2}$/', $v) === 1 ? null : 'Two digits, e.g. 07.');
    $gstin = strtoupper(ask('Your GSTIN (Enter if not registered)', '', false, static fn(string $v): ?string => preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', strtoupper($v)) !== 1
        ? 'A GSTIN has 15 characters, e.g. 07ABCDE1234F1Z5.'
        : (substr($v, 0, 2) !== $stateCode ? 'Its first two digits are the state code: ' . $stateCode . ' here.' : null)));
    // Registered: GST on invoices at the rate for IT services (18%: 9 + 9 within the state, 18 across states).
    $gstRate = $gstin === '' ? '' : ask('GST rate in % on your services', '18', true, static fn(string $v): ?string => preg_match('/^\d{1,2}(\.\d{1,2})?$/', $v) === 1 && (float) $v > 0 ? null : 'A rate above 0, such as 18.');
    // Invoice numbers read <prefix><financial year>-<serial>, e.g. ACM2627-00001: at most 16 characters for GST.
    $words = preg_split('/[^A-Za-z0-9]+/', $business, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = substr(strtoupper(implode('', array_map(static fn(string $word): string => $word[0], $words))), 0, 5) ?: 'INV';
    $prefix = ask('Invoice number prefix, 1 to 5 letters or digits (e.g. ACM gives ACM2627-00001)', $initials, true, static fn(string $v): ?string => preg_match('/^[A-Za-z0-9]{1,5}$/', $v) === 1 ? null : '1 to 5 letters or digits.');

    say("\n-- Licence (on My Purchases where you bought it) --");
    $licence = ask('Licence key', '', false);

    say("\n-- First admin account --");
    $adminPassword = ask('Admin password (at least 12 characters)', '', true, static fn(string $v): ?string => strlen($v) >= 12 ? null : 'At least 12 characters.', true);

    $values = [
        'YII_ENV' => 'prod', 'YII_DEBUG' => 'false', 'COOKIE_SECURE' => 'true', 'PORTAL_ONLY' => 'true',
        'DB_DSN' => "mysql:{$dbServer};dbname={$dbName}", 'DB_USERNAME' => $dbUser, 'DB_PASSWORD' => $dbPass, 'DB_CHARSET' => 'utf8mb4',
        'FRONTEND_BASE_URL' => $portal, 'BACKEND_BASE_URL' => $admin,
        'SMTP_HOST' => $smtpHost, 'SMTP_PORT' => $smtpPort, 'SMTP_SCHEME' => $smtpPort === '465' ? 'smtps' : 'smtp', 'SMTP_USERNAME' => $smtpUser, 'SMTP_PASSWORD' => $smtpPass,
        'SENDER_EMAIL' => $sender, 'SENDER_NAME' => $business, 'SUPPORT_EMAIL' => $support, 'ADMIN_EMAIL' => $adminEmail, 'ADMIN_INITIAL_PASSWORD' => $adminPassword,
        'MAIL_USE_FILE_TRANSPORT' => 'false',
        'FRONTEND_COOKIE_VALIDATION_KEY' => bin2hex(random_bytes(32)), 'BACKEND_COOKIE_VALIDATION_KEY' => bin2hex(random_bytes(32)), 'TWO_FACTOR_KEY' => bin2hex(random_bytes(32)),
        'BILLING_SUPPLIER_STATE' => $state, 'BILLING_SUPPLIER_STATE_CODE' => $stateCode, 'BILLING_GSTIN' => $gstin,
        'BILLING_GST_CGST' => $gstRate === '' ? '' : (string) ((float) $gstRate / 2), 'BILLING_GST_SGST' => $gstRate === '' ? '' : (string) ((float) $gstRate / 2), 'BILLING_GST_IGST' => $gstRate,
        'LICENCE_KEY' => $licence, 'INVOICE_PREFIX' => strtoupper($prefix),
        // Read once by install/brand below, for Site Settings; changed there afterwards.
        'INSTALL_WEBSITE' => $website,
    ];
    $lines = ['# Written by install.php on ' . date('Y-m-d') . '. Never share this file: it holds your passwords and keys.'];
    foreach ($values as $key => $value) {
        $lines[] = $key . '=' . envValue($value);
    }
    $lines[] = '# More settings (Razorpay, PayPal, WhatsApp, bank details): see .env.example.';
    // Created 0640 from the start, never readable by other accounts for a moment.
    $umask = umask(0027);
    $written = file_put_contents('.env', implode("\n", $lines) . "\n");
    umask($umask);
    if ($written === false) {
        say('Could not write .env in ' . getcwd() . ': give that folder write permission and run php install.php again.');
        exit(1);
    }
    chmod('.env', 0640);
    say("\n.env written.");
}

// 3. Folders the portal writes to (logs, cache, payment proofs, files), then the tables and this business's details.
// The web server reads the web folders (assets, the logo), so those stay readable by all: 755.
$folders = ['runtime/logs' => 0750, 'frontend/runtime' => 0750, 'backend/runtime' => 0750, 'console/runtime' => 0750, 'common/runtime' => 0750,
    'storage/payment-receipts' => 0750, 'storage/store-files' => 0750, 'frontend/web/assets' => 0755, 'backend/web/assets' => 0755, 'frontend/web/brand' => 0755];
$folders += ['backups' => 0750];
foreach ($folders as $folder => $mode) {
    if (!is_dir($folder) && !mkdir($folder, $mode, true) || !is_writable($folder)) {
        say("Cannot write to {$folder}: give it write permission (cPanel > File Manager > Permissions, " . decoct($mode) . ') and run php install.php again.');
        exit(1);
    }
}
// Never for the web, even if the folder ends up served: payment proofs, files, logs, backups.
foreach (['storage', 'backups', 'runtime'] as $private) {
    if (!is_file($private . '/.htaccess')) {
        file_put_contents($private . '/.htaccess', "Require all denied\n");
    }
}
// An update (INSTALL.md): the domain's PHP version, as cPanel wrote it into the old web roots' .htaccess, carried
// over to the new ones, or the domain falls back to the server's default PHP.
$old = dirname(getcwd()) . '/' . basename(getcwd()) . '-old';
foreach (['frontend/web/.htaccess', 'backend/web/.htaccess'] as $htaccess) {
    $previous = is_file($old . '/' . $htaccess) ? (string) file_get_contents($old . '/' . $htaccess) : '';
    $current = (string) @file_get_contents($htaccess);
    if (preg_match_all('/^# (php -- )?BEGIN cPanel-generated.*?^# (php -- )?END cPanel-generated[^\n]*\n?/ms', $previous, $blocks) > 0 && !str_contains($current, 'cPanel-generated')) {
        file_put_contents($htaccess, implode("\n", $blocks[0]) . "\n" . $current);
        say("Kept your PHP version settings in {$htaccess}.");
    }
}
if (!$canRun) {
    say("\nThis host does not let PHP run commands (passthru is disabled), so run these two yourself, then follow the steps below:");
    // With the PHP running this installer: plain php on cPanel is often older.
    say('  ' . escapeshellarg(PHP_BINARY ?: 'php') . ' yii migrate --interactive=0');
    say('  ' . escapeshellarg(PHP_BINARY ?: 'php') . ' yii install/brand');
} else {
    run(escapeshellarg(PHP_BINARY) . ' yii migrate --interactive=0');
    run(escapeshellarg(PHP_BINARY) . ' yii install/brand');
}

say("\nDone. Next:");
say('1. Point the portal address at frontend/web and the admin address at backend/web (cPanel > Domains).');
say('2. Add the cron jobs from the setup guide (INSTALL.md).');
say('3. Sign in to the admin panel as "superadmin" with the password you chose, turn on two-factor sign-in, and fill in');
say('   Site Settings (it needs your phone number before it can be saved).');
say('4. Put your logo at frontend/web/brand/logo.png (a PNG about 400 pixels wide), and a light version for dark');
say('   backgrounds (admin sidebar, email header) as logo-white.png in the same folder.');
say('5. Remove ADMIN_INITIAL_PASSWORD from .env.');
