#!/usr/bin/env bash
# Miracle production deploy:
#
#   cd /home/rpocncwk/repositories/miracle && bash deploy.sh
#
# Resets the checkout to origin/main (restarting itself if that changed deploy.sh).
# Copies common/frontend/backend/console into $APP, keeping each one's runtime/,
# web/assets/ and config/params-local.php. Publishes frontend/web into public_html
# without touching anything else there. Runs composer, backs up the database
# when migrations are pending, migrates, then health-checks the site.
# Any failure after the code is touched puts the previous code back.
# Migrations are not reversed: restore the printed backup if needed.

# Braces: bash reads the whole file before running it, so editing or resetting
# the checkout mid-deploy cannot change the running script.
{
set -Eeuo pipefail

REPO=/home/rpocncwk/repositories/miracle
APP=/home/rpocncwk/miracle
PUBLIC=/home/rpocncwk/public_html
HEALTH_URLS=(https://miraclewebtechnologies.com/ https://admin.miraclewebtechnologies.com/auth/login)
MAINTENANCE_FLAG=$APP/runtime/maintenance.flag
DIRS=(common frontend backend console)
# Excluded from copy and from --delete: these live only on the server.
KEEP=(--exclude=/runtime/ --exclude=/web/assets/ --exclude=/config/params-local.php --exclude=error_log)
COMPOSER=(composer install --prefer-dist --no-dev --no-interaction --optimize-autoloader)

STAGE=prep          # prep: nothing changed yet; code: $APP is being changed
MIGRATED=0
BACKUP=""
SNAP=""

die() { echo "ERROR: $*" >&2; exit 1; }

# Mirrors $1/{common,frontend,backend,console} and composer files into $APP.
sync_code()
{
    local d
    for d in "${DIRS[@]}"; do
        rsync -a --delete "${KEEP[@]}" "$1/$d/" "$APP/$d/"
    done
    cp -a "$1/composer.json" "$1/composer.lock" "$APP/"
}

# Copies frontend/web into public_html. The entry point, .htaccess and the
# assets/favicon symlinks are written here; other public_html content is left alone.
# ponytail: a top-level file or folder deleted from frontend/web stays in
# public_html; remove it by hand (nested files are cleaned by --delete).
publish_public()
{
    local item
    for item in "$APP/frontend/web"/*; do
        case "${item##*/}" in index.php|assets|favicon.ico|error_log) continue ;; esac
        rsync -a --delete "$item" "$PUBLIC/"
    done

    [ -L "$PUBLIC/assets" ] || rm -rf "${PUBLIC:?}/assets"
    [ -L "$PUBLIC/favicon.ico" ] || rm -f "$PUBLIC/favicon.ico"
    ln -sfn ../miracle/frontend/web/assets "$PUBLIC/assets"
    ln -sfn ../miracle/frontend/web/favicon.ico "$PUBLIC/favicon.ico"

    cat > "$PUBLIC/index.php" <<'PHP'
<?php

// Deploy in progress (deploy.sh creates this flag): answer 503 instead of
// loading half-copied code.
if (is_file(__DIR__ . '/../miracle' . '/runtime/maintenance.flag')) {
    http_response_code(503);
    header('Retry-After: 120');
    echo '<!DOCTYPE html><title>Maintenance</title><div style="font-family:sans-serif;text-align:center;margin-top:15vh"><h1>We&rsquo;ll be right back</h1><p>The site is being updated. Please try again in a few minutes.</p></div>';
    exit;
}

defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

require __DIR__ . '/../miracle/vendor/autoload.php';
require __DIR__ . '/../miracle/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../miracle/common/config/bootstrap.php';
require __DIR__ . '/../miracle/frontend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../miracle/common/config/main.php',
    require __DIR__ . '/../miracle/common/config/main-local.php',
    require __DIR__ . '/../miracle/frontend/config/main.php',
    require __DIR__ . '/../miracle/frontend/config/main-local.php'
);

(new yii\web\Application($config))->run();
PHP

    cat > "$PUBLIC/.htaccess" <<'HTACCESS'
RewriteEngine On

# Secure cookies don't work over http, so every form would fail there. SSL renewal must stay reachable.
RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteCond %{REQUEST_URI} !^/\.well-known/
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule . index.php [L]
HTACCESS
    php -l "$PUBLIC/index.php" >/dev/null
}

health_check()
{
    local url status ok=0
    for url in "${HEALTH_URLS[@]}"; do
        status=$(curl -s -o /dev/null -w '%{http_code}' --retry 2 --retry-connrefused --connect-timeout 5 --max-time 20 "$url" || true)
        echo "  $status $url"
        [ "$status" = 200 ] || ok=1
    done
    return $ok
}

on_fail()
{
    local code=$?
    trap - ERR INT TERM HUP
    set +e
    echo
    echo "DEPLOYMENT FAILED (exit $code, line $1)"

    if [ "$STAGE" = code ]; then
        echo "Restoring the previous code..."
        # A failed health check comes after the flag was removed: put it back before swapping code under traffic.
        touch "$MAINTENANCE_FLAG"
        if sync_code "$SNAP" && (cd "$APP" && "${COMPOSER[@]}") && publish_public \
            && (cd "$APP" && php yii help >/dev/null) && rm -f "$MAINTENANCE_FLAG" && health_check; then
            echo "Previous code restored."
            rm -rf "${SNAP:?}"
        else
            touch "$MAINTENANCE_FLAG"
            echo "ROLLBACK HAD ERRORS. Previous code is kept in: $SNAP"
        fi
        if [ "$MIGRATED" = 1 ]; then
            echo "WARNING: migrations had started and were NOT reversed. Check: php yii migrate/history 5"
            [ -n "$BACKUP" ] && echo "Restore if needed: gunzip -c $BACKUP | mysql <database>"
        fi
    elif [ -n "$SNAP" ]; then
        rm -rf "${SNAP:?}"
    fi

    # A failed restore may have left half-copied code: stay in maintenance until someone has looked.
    # Also true of a flag left by an earlier failed deploy when this one stops before touching anything.
    if [ -e "$MAINTENANCE_FLAG" ]; then
        echo "Maintenance mode is still on. Once the site is sound: rm -f $MAINTENANCE_FLAG"
    fi
    exit $(( code ? code : 1 ))
}
trap 'on_fail $LINENO' ERR INT TERM HUP


echo "== Preflight"
for cmd in php composer rsync curl flock; do
    command -v "$cmd" >/dev/null || die "command not found: $cmd"
done
exec 9>/home/rpocncwk/.miracle-deploy.lock
flock -n 9 || die "another deployment is running"
[ -r "$APP/.env" ] || die "$APP/.env is missing"
[ -r "$APP/yii" ] || die "$APP/yii is missing"

cd "$REPO"
if [ -z "${DEPLOY_RESTARTED:-}" ]; then
    BEFORE=$(git hash-object deploy.sh)
    git fetch origin
    git reset --hard origin/main
    if [ "$(git hash-object deploy.sh)" != "$BEFORE" ]; then
        echo "deploy.sh changed: restarting with the new version."
        exec 9>&-
        DEPLOY_RESTARTED=1 exec bash deploy.sh
    fi
fi
# Email links (password reset, verification, invoices) are built from it; without it they would follow
# the request's Host header or lose their buttons.
# After the restart above, so a fix to this check can't be blocked by the old one.
grep -Eq '^[[:space:]]*(export[[:space:]]+)?FRONTEND_BASE_URL[[:space:]]*=[[:space:]]*["'"'"']?https?://[^[:space:]"'"'"'/]' "$APP/.env" || die "FRONTEND_BASE_URL (e.g. https://miraclewebtechnologies.com) is not set in $APP/.env"
echo "Deploying commit $(git rev-parse --short HEAD)"
TRACKED=$(git ls-files .env yii {common,frontend,backend,console}/config/params-local.php)
[ -z "$TRACKED" ] || die "production-only files are tracked by Git: $TRACKED"

# The locked Symfony packages need this; composer would otherwise fail only once the site is in maintenance.
php -r 'exit(version_compare(PHP_VERSION, "8.4.1", ">=") ? 0 : 1);' || die "PHP 8.4.1 or newer is required (found $(php -r 'echo PHP_VERSION;'))"

echo "== Checking PHP syntax"
if ! LINT=$(find "${DIRS[@]}" -name '*.php' -not -path '*/runtime/*' -not -path '*/web/assets/*' -print0 \
    | xargs -0 -n1 -P4 php -l 2>&1); then
    echo "$LINT" | grep -v '^No syntax errors'
    die "the release has PHP syntax errors"
fi

echo "== Snapshot of the current code"
SNAP=$(mktemp -d /home/rpocncwk/.miracle-deploy.XXXXXX)
for d in "${DIRS[@]}"; do
    rsync -a "${KEEP[@]}" "$APP/$d/" "$SNAP/$d/"
done
cp -a "$APP/composer.json" "$APP/composer.lock" "$SNAP/"


echo "== Deploying (maintenance mode on)"
STAGE=code
# Web entry points answer 503 and cron jobs skip while this exists.
# ponytail: a cron run already in progress is not waited for; add a wait here if runs get long.
# runtime/logs: crontab output (billing-cron.log, read by the admin Cron Log page).
mkdir -p "$APP/runtime/logs"
touch "$MAINTENANCE_FLAG"

sync_code "$REPO"
for d in "${DIRS[@]}"; do
    [ -f "$APP/$d/config/params-local.php" ] || cp "$REPO/environments/prod/$d/config/params-local.php" "$APP/$d/config/"
done

cd "$APP"
"${COMPOSER[@]}"
php yii help >/dev/null
publish_public

echo "== Migrations"
# MySQL DDL is not transactional and rollback does not reverse migrations,
# so take a restorable dump first. A failed backup stops before the schema changes.
if php yii migrate/new --interactive=0 | grep -q "No new migrations found"; then
    echo "No pending migrations."
else
    php yii backup/run
    BACKUP=$(ls -1t "$APP"/backups/db_backup_*.sql.gz | head -n 1)
    echo "Pre-migration backup: $BACKUP"
fi
MIGRATED=1
php yii migrate/up --interactive=0

# Logs and customers' payment proofs: not readable by other accounts on the server.
for d in runtime frontend/runtime backend/runtime console/runtime common/runtime storage/payment-receipts; do
    mkdir -p "$APP/$d"
    chmod -R u=rwX,g=rwX,o= "$APP/$d"
done
chmod 640 "$APP/.env"
# Served by the web server.
for d in frontend/web/assets backend/web/assets; do
    mkdir -p "$APP/$d"
    chmod -R 775 "$APP/$d"
done
rm -f "$MAINTENANCE_FLAG"

echo "== Health check"
health_check

trap - ERR INT TERM HUP
rm -rf "${SNAP:?}"
echo "Deployment completed successfully."
exit 0
}
