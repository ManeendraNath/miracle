#!/bin/bash

set -Eeuo pipefail

# =====================================================================
# MIRACLE PRODUCTION DEPLOYMENT
#
# Repository:
#   /home/rpocncwk/repositories/miracle
#
# Application:
#   /home/rpocncwk/miracle
#
# Frontend:
#   https://miraclewebtechnologies.com
#   DocumentRoot: /home/rpocncwk/public_html
#
# Backend:
#   https://admin.miraclewebtechnologies.com
#   DocumentRoot: /home/rpocncwk/miracle/backend/web
# =====================================================================

REPO="/home/rpocncwk/repositories/miracle"
APP="/home/rpocncwk/miracle"
PUBLIC="/home/rpocncwk/public_html"

BACKUP_DIR="/tmp/miracle-deploy-backup"

FRONTEND_URL="https://miraclewebtechnologies.com/"
BACKEND_URL="https://admin.miraclewebtechnologies.com/"
BACKEND_LOGIN_URL="https://admin.miraclewebtechnologies.com/auth/login"


# =====================================================================
# ERROR HANDLING
# =====================================================================

cleanup()
{
    rm -rf "$BACKUP_DIR"
}

on_error()
{
    EXIT_CODE=$?

    echo ""
    echo "=========================================="
    echo "DEPLOYMENT FAILED"
    echo "Line       : $1"
    echo "Command    : $2"
    echo "Exit code  : $EXIT_CODE"
    echo "=========================================="

    exit "$EXIT_CODE"
}

trap 'on_error "$LINENO" "$BASH_COMMAND"' ERR
trap cleanup EXIT


echo "=========================================="
echo "Starting Miracle deployment..."
echo "=========================================="


# =====================================================================
# 1. PREFLIGHT CHECKS
# =====================================================================

echo "[1/12] Running preflight checks..."

for CMD in git php composer curl cp rm mkdir ln readlink
do
    if ! command -v "$CMD" >/dev/null 2>&1; then
        echo "ERROR: Required command not found: $CMD"
        exit 1
    fi
done


if [ ! -d "$REPO/.git" ]; then
    echo "ERROR: Deployment repository is not valid:"
    echo "$REPO"
    exit 1
fi


if [ ! -d "$APP" ]; then
    echo "ERROR: Application directory does not exist:"
    echo "$APP"
    exit 1
fi


if [ ! -d "$PUBLIC" ]; then
    echo "ERROR: Frontend public directory does not exist:"
    echo "$PUBLIC"
    exit 1
fi


if [ ! -f "$APP/.env" ]; then
    echo "ERROR: Production .env is missing:"
    echo "$APP/.env"
    exit 1
fi


# =====================================================================
# 2. UPDATE DEPLOYMENT REPOSITORY
# =====================================================================

echo "[2/12] Fetching latest code from GitHub..."

cd "$REPO"

git fetch origin
git reset --hard origin/main

COMMIT="$(git rev-parse --short HEAD)"

echo "Deploying commit: $COMMIT"


# =====================================================================
# 3. VALIDATE REQUIRED TRACKED CONFIG
# =====================================================================

echo "[3/12] Validating tracked configuration..."

for FILE in \
    common/config/main-local.php \
    frontend/config/main-local.php \
    backend/config/main-local.php \
    console/config/main-local.php
do
    if [ ! -f "$REPO/$FILE" ]; then
        echo "ERROR: Required tracked configuration missing:"
        echo "$FILE"
        exit 1
    fi

    php -l "$REPO/$FILE" >/dev/null
done


# =====================================================================
# 4. BACKUP PRODUCTION-ONLY CONFIG
# =====================================================================

echo "[4/12] Preserving production configuration..."

rm -rf "$BACKUP_DIR"

mkdir -p "$BACKUP_DIR/common/config"
mkdir -p "$BACKUP_DIR/frontend/config"
mkdir -p "$BACKUP_DIR/backend/config"
mkdir -p "$BACKUP_DIR/console/config"


preserve_params()
{
    RELATIVE_PATH="$1"

    if [ -f "$APP/$RELATIVE_PATH" ]; then

        TARGET_DIR="$BACKUP_DIR/$(dirname "$RELATIVE_PATH")"

        mkdir -p "$TARGET_DIR"

        cp -a \
            "$APP/$RELATIVE_PATH" \
            "$TARGET_DIR/"
    fi
}


preserve_params "common/config/params-local.php"
preserve_params "frontend/config/params-local.php"
preserve_params "backend/config/params-local.php"
preserve_params "console/config/params-local.php"


# =====================================================================
# 5. DEPLOY COMPLETE APPLICATION CODE
#
# Important:
#
# We replace the application's source directories completely so:
#
#   - new directories are deployed
#   - deleted Git files disappear from production
#   - stale classes/controllers are not retained
#
# Runtime, generated assets and .env are handled separately.
# =====================================================================

echo "[5/12] Deploying application code..."


# ------------------------------------------------------------
# COMMON
# ------------------------------------------------------------

rm -rf "$APP/common"
cp -a "$REPO/common" "$APP/common"


# ------------------------------------------------------------
# CONSOLE
# ------------------------------------------------------------

rm -rf "$APP/console"
cp -a "$REPO/console" "$APP/console"


# ------------------------------------------------------------
# FRONTEND APPLICATION
#
# Preserve web + runtime separately.
# ------------------------------------------------------------

mkdir -p "$APP/frontend"

find "$APP/frontend" \
    -mindepth 1 \
    -maxdepth 1 \
    ! -name "web" \
    ! -name "runtime" \
    -exec rm -rf {} +


for ITEM in "$REPO/frontend/"*
do
    [ -e "$ITEM" ] || continue

    NAME="$(basename "$ITEM")"

    case "$NAME" in
        web|runtime)
            continue
            ;;
    esac

    cp -a "$ITEM" "$APP/frontend/"
done


# ------------------------------------------------------------
# BACKEND APPLICATION
#
# Preserve web + runtime separately.
# ------------------------------------------------------------

mkdir -p "$APP/backend"

find "$APP/backend" \
    -mindepth 1 \
    -maxdepth 1 \
    ! -name "web" \
    ! -name "runtime" \
    -exec rm -rf {} +


for ITEM in "$REPO/backend/"*
do
    [ -e "$ITEM" ] || continue

    NAME="$(basename "$ITEM")"

    case "$NAME" in
        web|runtime)
            continue
            ;;
    esac

    cp -a "$ITEM" "$APP/backend/"
done


# =====================================================================
# 6. DEPLOY WEB DIRECTORIES
#
# Generated Yii assets are preserved.
# =====================================================================

echo "[6/12] Deploying web application files..."

mkdir -p "$APP/frontend/web/assets"
mkdir -p "$APP/backend/web/assets"


deploy_web()
{
    SOURCE="$1"
    DESTINATION="$2"

    mkdir -p "$DESTINATION"

    # Remove existing web content except generated Yii assets.

    find "$DESTINATION" \
        -mindepth 1 \
        -maxdepth 1 \
        ! -name "assets" \
        -exec rm -rf {} +


    # Deploy everything tracked under web except assets.

    for ITEM in "$SOURCE/"* "$SOURCE/".[!.]*
    do
        [ -e "$ITEM" ] || continue

        NAME="$(basename "$ITEM")"

        if [ "$NAME" = "assets" ]; then
            continue
        fi

        cp -a "$ITEM" "$DESTINATION/"
    done
}


deploy_web \
    "$REPO/frontend/web" \
    "$APP/frontend/web"


deploy_web \
    "$REPO/backend/web" \
    "$APP/backend/web"


# =====================================================================
# 7. RESTORE PARAMS-LOCAL
# =====================================================================

echo "[7/12] Restoring production configuration..."


restore_params()
{
    RELATIVE_PATH="$1"
    PRODUCTION_TEMPLATE="$2"

    BACKUP_FILE="$BACKUP_DIR/$RELATIVE_PATH"

    if [ -f "$BACKUP_FILE" ]; then

        cp -a \
            "$BACKUP_FILE" \
            "$APP/$RELATIVE_PATH"

    elif [ -f "$REPO/$PRODUCTION_TEMPLATE" ]; then

        cp -a \
            "$REPO/$PRODUCTION_TEMPLATE" \
            "$APP/$RELATIVE_PATH"

    else

        echo "ERROR: Unable to create:"
        echo "$RELATIVE_PATH"

        exit 1
    fi
}


restore_params \
    "common/config/params-local.php" \
    "environments/prod/common/config/params-local.php"


restore_params \
    "frontend/config/params-local.php" \
    "environments/prod/frontend/config/params-local.php"


restore_params \
    "backend/config/params-local.php" \
    "environments/prod/backend/config/params-local.php"


restore_params \
    "console/config/params-local.php" \
    "environments/prod/console/config/params-local.php"


# Verify production configuration exists.

for FILE in \
    common/config/main-local.php \
    common/config/params-local.php \
    frontend/config/main-local.php \
    frontend/config/params-local.php \
    backend/config/main-local.php \
    backend/config/params-local.php \
    console/config/main-local.php \
    console/config/params-local.php
do
    if [ ! -f "$APP/$FILE" ]; then

        echo "ERROR: Required configuration missing:"
        echo "$FILE"

        exit 1
    fi

    php -l "$APP/$FILE" >/dev/null
done


# Production .env must still exist.

if [ ! -f "$APP/.env" ]; then
    echo "ERROR: Production .env disappeared during deployment."
    exit 1
fi


# =====================================================================
# 8. DEPLOY ROOT APPLICATION FILES
# =====================================================================

echo "[8/12] Deploying application root files..."

for FILE in \
    yii \
    yii.bat \
    composer.json \
    composer.lock
do

    if [ -f "$REPO/$FILE" ]; then
        cp -a "$REPO/$FILE" "$APP/$FILE"
    fi

done


# =====================================================================
# 9. COMPOSER
# =====================================================================

echo "[9/12] Installing Composer dependencies..."

cd "$APP"

#
# Keep require-dev for NOW because the current production application
# has been verified with these dependencies installed.
#
# Once production dependencies have been audited, this can become:
#
# composer install --no-dev ...
#

composer install \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader


# =====================================================================
# 10. FRONTEND PUBLIC DOCUMENT ROOT
# =====================================================================

echo "[10/12] Updating frontend public root..."


# ------------------------------------------------------------
# CSS
# ------------------------------------------------------------

if [ -d "$APP/frontend/web/css" ]; then

    rm -rf "$PUBLIC/css"

    cp -a \
        "$APP/frontend/web/css" \
        "$PUBLIC/css"

fi


# ------------------------------------------------------------
# IMAGES
# ------------------------------------------------------------

if [ -d "$APP/frontend/web/images" ]; then

    rm -rf "$PUBLIC/images"

    cp -a \
        "$APP/frontend/web/images" \
        "$PUBLIC/images"

fi


# ------------------------------------------------------------
# JS
# ------------------------------------------------------------

if [ -d "$APP/frontend/web/js" ]; then

    rm -rf "$PUBLIC/js"

    cp -a \
        "$APP/frontend/web/js" \
        "$PUBLIC/js"

fi


# ------------------------------------------------------------
# ROBOTS.TXT
# ------------------------------------------------------------

if [ -f "$APP/frontend/web/robots.txt" ]; then

    cp -a \
        "$APP/frontend/web/robots.txt" \
        "$PUBLIC/robots.txt"

fi


# ------------------------------------------------------------
# ASSET SYMLINK
#
# public_html/assets
#     ->
# ../miracle/frontend/web/assets
# ------------------------------------------------------------

EXPECTED_ASSET_LINK="../miracle/frontend/web/assets"


if [ ! -L "$PUBLIC/assets" ] ||
   [ "$(readlink "$PUBLIC/assets" 2>/dev/null || true)" != "$EXPECTED_ASSET_LINK" ]; then

    echo "Recreating frontend assets symlink..."

    rm -rf "$PUBLIC/assets"

    ln -s \
        "$EXPECTED_ASSET_LINK" \
        "$PUBLIC/assets"

fi


# ------------------------------------------------------------
# FAVICON SYMLINK
# ------------------------------------------------------------

EXPECTED_FAVICON_LINK="../miracle/frontend/web/favicon.ico"


if [ ! -L "$PUBLIC/favicon.ico" ] ||
   [ "$(readlink "$PUBLIC/favicon.ico" 2>/dev/null || true)" != "$EXPECTED_FAVICON_LINK" ]; then

    echo "Recreating favicon symlink..."

    rm -f "$PUBLIC/favicon.ico"

    ln -s \
        "$EXPECTED_FAVICON_LINK" \
        "$PUBLIC/favicon.ico"

fi


# ------------------------------------------------------------
# FRONTEND PRODUCTION INDEX
# ------------------------------------------------------------

cat > "$PUBLIC/index.php" <<'PHP'
<?php

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


# ------------------------------------------------------------
# FRONTEND HTACCESS
# ------------------------------------------------------------

cat > "$PUBLIC/.htaccess" <<'HTACCESS'
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule . index.php [L]
HTACCESS


# =====================================================================
# 11. MIGRATIONS + PERMISSIONS
# =====================================================================
echo "[11/12] Running database migrations..."

cd "$APP"

php yii migrate/up --interactive=0


echo "Preparing writable directories..."
mkdir -p "$APP/frontend/runtime"
mkdir -p "$APP/backend/runtime"
mkdir -p "$APP/frontend/web/assets"
mkdir -p "$APP/backend/web/assets"

#
# Do NOT delete published assets here.
#
# Yii can continue using existing hashed assets and publish new assets
# when required.
#
chmod -R 775 "$APP/frontend/runtime"
chmod -R 775 "$APP/backend/runtime"
chmod -R 775 "$APP/frontend/web/assets"
chmod -R 775 "$APP/backend/web/assets"


# =====================================================================
# 12. FINAL VALIDATION + HEALTH CHECK
# =====================================================================
echo "[12/12] Running final validation..."

# ------------------------------------------------------------
# PHP SYNTAX
# ------------------------------------------------------------

php -l "$APP/frontend/config/main.php"
php -l "$APP/frontend/config/main-local.php"

php -l "$APP/backend/config/main.php"
php -l "$APP/backend/config/main-local.php"

php -l "$APP/common/config/main.php"
php -l "$APP/common/config/main-local.php"

php -l "$APP/console/config/main.php"
php -l "$APP/console/config/main-local.php"

php -l "$APP/yii"

php -l "$PUBLIC/index.php"


# ------------------------------------------------------------
# FRONTEND
# ------------------------------------------------------------
echo "Checking frontend..."

FRONTEND_STATUS="$(
    curl \
        --silent \
        --show-error \
        --max-time 20 \
        --output /dev/null \
        --write-out "%{http_code}" \
        "$FRONTEND_URL"
)"

echo "Frontend HTTP status: $FRONTEND_STATUS"

if [ "$FRONTEND_STATUS" != "200" ]; then

    echo "ERROR: Frontend health check failed."
    echo "Expected: 200"
    echo "Received: $FRONTEND_STATUS"

    exit 1

fi


# ------------------------------------------------------------
# BACKEND ROOT
# ------------------------------------------------------------
echo "Checking backend..."

BACKEND_STATUS="$(
    curl \
        --silent \
        --show-error \
        --max-time 20 \
        --output /dev/null \
        --write-out "%{http_code}" \
        "$BACKEND_URL"
)"

echo "Backend HTTP status: $BACKEND_STATUS"

#
# Backend currently redirects unauthenticated requests to /auth/login.
#
# We permit either:
#
# 200 = already served
# 302 = authentication redirect
#

if [ "$BACKEND_STATUS" != "200" ] &&
   [ "$BACKEND_STATUS" != "302" ]; then

    echo "ERROR: Backend health check failed."
    echo "Expected: 200 or 302"
    echo "Received: $BACKEND_STATUS"

    exit 1

fi


# ------------------------------------------------------------
# BACKEND LOGIN
# ------------------------------------------------------------

echo "Checking backend login..."

BACKEND_LOGIN_STATUS="$(
    curl \
        --silent \
        --show-error \
        --max-time 20 \
        --output /dev/null \
        --write-out "%{http_code}" \
        "$BACKEND_LOGIN_URL"
)"

echo "Backend login HTTP status: $BACKEND_LOGIN_STATUS"

if [ "$BACKEND_LOGIN_STATUS" != "200" ]; then

    echo "ERROR: Backend login page health check failed."
    echo "Expected: 200"
    echo "Received: $BACKEND_LOGIN_STATUS"

    exit 1

fi


# =====================================================================
# SUCCESS
# =====================================================================
echo ""
echo "=========================================="
echo "Deployment completed successfully."
echo "Commit   : $COMMIT"
echo "Frontend : $FRONTEND_URL"
echo "Backend  : $BACKEND_URL"
echo "=========================================="