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
BACKUP_NOTE=$APP/runtime/pre-migration-backup
DIRS=(common frontend backend console)
# Excluded from copy and from --delete: these live only on the server.
KEEP=(--exclude=/runtime/ --exclude=/web/assets/ --exclude=/config/params-local.php --exclude=error_log --exclude=.user.ini --exclude=php.ini)
COMPOSER=(composer install --prefer-dist --no-dev --no-interaction --optimize-autoloader)

STAGE=prep          # prep: nothing changed yet; code: $APP is being changed
MIGRATED=0
PRIOR_FLAG=0        # 1: already in maintenance before this deploy, e.g. after an earlier failed one
BACKUP=""
SNAP=""

die()
{
    echo "ERROR: $*" >&2
    if [ -e "$MAINTENANCE_FLAG" ]; then
        echo "Maintenance mode is still on, and every cron job and the nightly backup skip while it is. Once the site is sound: rm -f $MAINTENANCE_FLAG" >&2
    fi
    exit 1
}

# The site's PHP version and settings, as cPanel wrote them into an .htaccess we are about to replace.
cpanel_block()
{
    sed -n -e '/^# php -- BEGIN cPanel-generated/,/^# php -- END cPanel-generated/p' -e '/^# BEGIN cPanel-generated/,/^# END cPanel-generated/p' "$1" 2>/dev/null || true
}

# Mirrors $1/{common,frontend,backend,console} and composer files into $APP.
sync_code()
{
    local d cpanel admin_htaccess="$APP/backend/web/.htaccess"
    # The admin subdomain's document root: same cPanel block as public_html, and rsync would drop it.
    cpanel=$(cpanel_block "$admin_htaccess")
    for d in "${DIRS[@]}"; do
        rsync -a --delete "${KEEP[@]}" "$1/$d/" "$APP/$d/" || return 1
    done
    if [ -n "$cpanel" ] && ! grep -q 'cPanel-generated' "$admin_htaccess" 2>/dev/null; then
        printf '\n%s\n' "$cpanel" >> "$admin_htaccess" || return 1
    fi
    cp -a "$1/composer.json" "$1/composer.lock" "$APP/" || return 1
}

# Copies frontend/web into public_html. The entry point, .htaccess and the
# assets/favicon symlinks are written here; other public_html content is left alone.
# ponytail: a top-level file or folder deleted from frontend/web stays in
# public_html; remove it by hand (nested files are cleaned by --delete).
publish_public()
{
    local item cpanel
    for item in "$APP/frontend/web"/*; do
        case "${item##*/}" in index.php|assets|favicon.ico|error_log) continue ;; esac
        rsync -a --delete "$item" "$PUBLIC/" || return 1
    done

    [ -L "$PUBLIC/assets" ] || rm -rf "${PUBLIC:?}/assets"
    [ -L "$PUBLIC/favicon.ico" ] || rm -f "$PUBLIC/favicon.ico"
    ln -sfn ../miracle/frontend/web/assets "$PUBLIC/assets" || return 1
    ln -sfn ../miracle/frontend/web/favicon.ico "$PUBLIC/favicon.ico" || return 1

    cat > "$PUBLIC/index.php" <<'PHP' || return 1
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

    # cPanel writes the site's PHP version and settings into this file: they must survive it being rewritten.
    cpanel=$(cpanel_block "$PUBLIC/.htaccess")
    # A run that died halfway through writing it: the snapshot still has the block.
    if [ -z "$cpanel" ] && [ -n "$SNAP" ]; then
        cpanel=$(cpanel_block "$SNAP/public.htaccess")
    fi

    cat > "$PUBLIC/.htaccess" <<'HTACCESS' || return 1
RewriteEngine On

# PHP's error log sits next to the entry point and can name paths and customers.
<Files "error_log">
    Require all denied
</Files>

# Secure cookies don't work over http, so every form would fail there. SSL renewal must stay reachable.
RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteCond %{REQUEST_URI} !^/\.well-known/
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule . index.php [L]
HTACCESS
    if [ -n "$cpanel" ]; then
        printf '\n%s\n' "$cpanel" >> "$PUBLIC/.htaccess" || return 1
    fi
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
        if ! (sync_code "$SNAP" && (cd "$APP" && "${COMPOSER[@]}") && publish_public && cd "$APP" && php yii help >/dev/null); then
            touch "$MAINTENANCE_FLAG"
            echo "ROLLBACK HAD ERRORS. Previous code is kept in: $SNAP"
        elif [ "$MIGRATED" = 1 ] || [ "$PRIOR_FLAG" = 1 ]; then
            # The schema may be newer than the restored code, or the site was already down for another reason:
            # customers stay out until someone has looked.
            echo "Previous code restored."
            rm -rf "${SNAP:?}"
        elif rm -f "$MAINTENANCE_FLAG" && health_check; then
            echo "Previous code restored."
            rm -rf "${SNAP:?}"
        else
            touch "$MAINTENANCE_FLAG"
            echo "ROLLBACK HAD ERRORS. Previous code is kept in: $SNAP"
        fi
        if [ "$MIGRATED" = 1 ]; then
            echo "WARNING: migrations had started and were NOT reversed. Check: php yii migrate/history 5"
            if [ -n "$BACKUP" ]; then
                echo "To restore: tables added by the migrations must go too, so empty the database first:"
                echo "  mysql -N -e \"SELECT CONCAT('DROP TABLE IF EXISTS \\\`', table_name, '\\\`;') FROM information_schema.tables WHERE table_schema='<database>'\" | (echo 'SET FOREIGN_KEY_CHECKS=0;'; cat) | mysql <database>"
                echo "  gunzip -c $BACKUP | mysql <database>"
            fi
        fi
    elif [ -n "$SNAP" ]; then
        rm -rf "${SNAP:?}"
    fi

    # A failed restore may have left half-copied code: stay in maintenance until someone has looked.
    # Also true of a flag left by an earlier failed deploy when this one stops before touching anything.
    if [ -e "$MAINTENANCE_FLAG" ]; then
        echo "Maintenance mode is still on, and every cron job and the nightly backup skip while it is. Once the site is sound: rm -f $MAINTENANCE_FLAG"
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
# Backups archive the payment proofs with it; better to learn now than from a failed nightly run.
php -r 'exit(class_exists("PharData") ? 0 : 1);' || die "the PHP phar extension is required"

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
cp -a "$PUBLIC/.htaccess" "$SNAP/public.htaccess" 2>/dev/null || true


echo "== Deploying (maintenance mode on)"
if [ -e "$MAINTENANCE_FLAG" ]; then PRIOR_FLAG=1; fi
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
    # Database only: a slow or failing off-site copy must not stop the deploy.
    php yii backup/run --dbOnly=1
    BACKUP=$(ls -1t "$APP"/backups/db_backup_*.sql.gz | head -n 1)
    # A retry after a migration died partway: this dump is of the half-migrated database, so keep pointing at the clean one.
    if [ -s "$BACKUP_NOTE" ]; then
        BACKUP=$(cat "$BACKUP_NOTE")
    else
        echo "$BACKUP" > "$BACKUP_NOTE"
    fi
    echo "Pre-migration backup: $BACKUP"
    MIGRATED=1
    php yii migrate/up --interactive=0
fi

# Logs and customers' payment proofs: not readable by other accounts on the server.
for d in runtime frontend/runtime backend/runtime console/runtime common/runtime storage/payment-receipts; do
    # The code and schema are already live: a file the shell user doesn't own must not roll all that back.
    mkdir -p "$APP/$d" && chmod -R u=rwX,g=rwX,o= "$APP/$d" || echo "WARNING: could not set permissions on $APP/$d"
done
chmod 640 "$APP/.env" || echo "WARNING: could not set permissions on $APP/.env"
# Served by the web server.
for d in frontend/web/assets backend/web/assets; do
    mkdir -p "$APP/$d" && chmod -R 775 "$APP/$d" || echo "WARNING: could not set permissions on $APP/$d"
done
rm -f "$MAINTENANCE_FLAG"

echo "== Health check"
health_check

trap - ERR INT TERM HUP
rm -rf "${SNAP:?}"
rm -f "$BACKUP_NOTE"
# A job that never starts can't report itself, and a new job needs its line added by hand. Razorpay sync is optional.
for job in cron/run-billing cron/payment-reminders cron/send-reminders cron/profile-reminders cron/whatsapp-cleanup backup/run; do
    crontab -l 2>/dev/null | grep -q "$job" || echo "WARNING: no crontab line for $job. See docs/BILLING_MODULE.md section 14."
done
echo "Deployment completed successfully."
exit 0
}
