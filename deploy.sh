#!/bin/bash

set -e

REPO="/home/rpocncwk/repositories/miracle"
APP="/home/rpocncwk/miracle"
PUBLIC="/home/rpocncwk/public_html"

echo "=========================================="
echo "Starting Miracle deployment..."
echo "=========================================="


# ============================================================
# 1. UPDATE DEPLOYMENT REPOSITORY
# ============================================================

echo "[1/9] Fetching latest code from GitHub..."

cd "$REPO"

git fetch origin
git reset --hard origin/main


# ============================================================
# 2. ENSURE APPLICATION DIRECTORIES EXIST
# ============================================================

echo "[2/9] Preparing application directories..."

mkdir -p "$APP/frontend"
mkdir -p "$APP/backend"
mkdir -p "$APP/common"
mkdir -p "$APP/console"

mkdir -p "$APP/frontend/runtime"
mkdir -p "$APP/backend/runtime"

mkdir -p "$APP/frontend/web/assets"
mkdir -p "$APP/backend/web/assets"


# ============================================================
# 3. DEPLOY FRONTEND APPLICATION CODE
#
# Do NOT delete:
#   frontend/runtime
#   frontend/web/assets
#
# These are generated/writable directories.
# ============================================================

echo "[3/9] Deploying frontend application..."

for DIR in \
    assets \
    config \
    controllers \
    models \
    views \
    widgets
do
    if [ -d "$REPO/frontend/$DIR" ]; then

        rm -rf "$APP/frontend/$DIR"
        cp -a "$REPO/frontend/$DIR" "$APP/frontend/"

    fi
done


# ============================================================
# 4. DEPLOY FRONTEND WEB SOURCE FILES
#
# Preserve:
#   frontend/web/assets
#
# because Yii generates published assets there.
# ============================================================

echo "[4/9] Deploying frontend web files..."

find "$APP/frontend/web" \
    -mindepth 1 \
    -maxdepth 1 \
    ! -name "assets" \
    -exec rm -rf {} +

for ITEM in "$REPO/frontend/web/"* "$REPO/frontend/web/".[!.]*; do

    [ -e "$ITEM" ] || continue

    NAME="$(basename "$ITEM")"

    if [ "$NAME" = "assets" ]; then
        continue
    fi

    cp -a "$ITEM" "$APP/frontend/web/"

done


# ============================================================
# 5. DEPLOY BACKEND APPLICATION CODE
#
# Do NOT delete:
#   backend/runtime
#   backend/web/assets
# ============================================================

echo "[5/9] Deploying backend application..."

for DIR in \
    assets \
    config \
    controllers \
    models \
    views
do
    if [ -d "$REPO/backend/$DIR" ]; then

        rm -rf "$APP/backend/$DIR"
        cp -a "$REPO/backend/$DIR" "$APP/backend/"

    fi
done


echo "Deploying backend web files..."

find "$APP/backend/web" \
    -mindepth 1 \
    -maxdepth 1 \
    ! -name "assets" \
    -exec rm -rf {} +

for ITEM in "$REPO/backend/web/"* "$REPO/backend/web/".[!.]*; do

    [ -e "$ITEM" ] || continue

    NAME="$(basename "$ITEM")"

    if [ "$NAME" = "assets" ]; then
        continue
    fi

    cp -a "$ITEM" "$APP/backend/web/"

done


# ============================================================
# 6. DEPLOY COMMON + CONSOLE
# ============================================================

echo "[6/9] Deploying common and console code..."

rm -rf "$APP/common"
cp -a "$REPO/common" "$APP/common"

rm -rf "$APP/console"
cp -a "$REPO/console" "$APP/console"


# ============================================================
# COPY ROOT APPLICATION FILES
#
# IMPORTANT:
# Do NOT copy .env.
# Production .env stays only in:
#   /home/rpocncwk/miracle/.env
# ============================================================

echo "Deploying root application files..."

if [ -f "$REPO/yii" ]; then
    cp -a "$REPO/yii" "$APP/yii"
fi

if [ -f "$REPO/yii.bat" ]; then
    cp -a "$REPO/yii.bat" "$APP/yii.bat"
fi

if [ -f "$REPO/composer.json" ]; then
    cp -a "$REPO/composer.json" "$APP/composer.json"
fi

if [ -f "$REPO/composer.lock" ]; then
    cp -a "$REPO/composer.lock" "$APP/composer.lock"
fi


# ============================================================
# 7. INSTALL COMPOSER DEPENDENCIES
# ============================================================

echo "[7/9] Installing Composer dependencies..."

cd "$APP"

if command -v composer >/dev/null 2>&1; then

    composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --optimize-autoloader

else

    echo "WARNING: composer command not found."
    echo "Existing vendor directory will be used."

fi


# ============================================================
# 8. DEPLOY FRONTEND PUBLIC FILES
#
# Main cPanel domain:
#
# miraclewebtechnologies.com
#     -> /home/rpocncwk/public_html
#
# Backend is NOT deployed here anymore.
#
# admin.miraclewebtechnologies.com
#     -> /home/rpocncwk/miracle/backend/web
# ============================================================

echo "[8/9] Deploying frontend public files..."

mkdir -p "$PUBLIC/assets"


# ------------------------------------------------------------
# CSS
# ------------------------------------------------------------

if [ -d "$APP/frontend/web/css" ]; then

    rm -rf "$PUBLIC/css"
    cp -a "$APP/frontend/web/css" "$PUBLIC/css"

fi


# ------------------------------------------------------------
# Images
# ------------------------------------------------------------

if [ -d "$APP/frontend/web/images" ]; then

    rm -rf "$PUBLIC/images"
    cp -a "$APP/frontend/web/images" "$PUBLIC/images"

fi


# ------------------------------------------------------------
# JavaScript
# ------------------------------------------------------------

if [ -d "$APP/frontend/web/js" ]; then

    rm -rf "$PUBLIC/js"
    cp -a "$APP/frontend/web/js" "$PUBLIC/js"

fi


# ------------------------------------------------------------
# Favicon
# ------------------------------------------------------------

if [ -f "$APP/frontend/web/favicon.ico" ]; then
    cp -a "$APP/frontend/web/favicon.ico" "$PUBLIC/favicon.ico"
fi


# ------------------------------------------------------------
# robots.txt
# ------------------------------------------------------------

if [ -f "$APP/frontend/web/robots.txt" ]; then
    cp -a "$APP/frontend/web/robots.txt" "$PUBLIC/robots.txt"
fi


# ============================================================
# FRONTEND PRODUCTION ENTRY POINT
# ============================================================

echo "Creating production frontend index.php..."

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


# ============================================================
# FRONTEND .HTACCESS
# ============================================================

echo "Creating production frontend .htaccess..."

cat > "$PUBLIC/.htaccess" <<'HTACCESS'
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule . index.php [L]
HTACCESS


# ============================================================
# 9. MIGRATIONS + CACHE + PERMISSIONS
# ============================================================

echo "[9/9] Running database migrations..."

cd "$APP"

php yii migrate/up --interactive=0


echo "Clearing Yii runtime caches..."

rm -rf "$APP/frontend/runtime/cache/"*
rm -rf "$APP/backend/runtime/cache/"*


# ------------------------------------------------------------
# BACKEND ASSETS
#
# Safe to clear because admin subdomain points directly to:
#   miracle/backend/web
# ------------------------------------------------------------

echo "Clearing backend published assets..."

rm -rf "$APP/backend/web/assets/"*


# ------------------------------------------------------------
# Writable directory permissions
# ------------------------------------------------------------

echo "Updating writable directory permissions..."

chmod -R 775 "$APP/frontend/runtime"
chmod -R 775 "$APP/backend/runtime"
chmod -R 775 "$APP/frontend/web/assets"
chmod -R 775 "$APP/backend/web/assets"
chmod -R 775 "$PUBLIC/assets"


echo "=========================================="
echo "Deployment completed successfully."
echo ""
echo "Frontend:"
echo "https://miraclewebtechnologies.com/"
echo ""
echo "Backend:"
echo "https://admin.miraclewebtechnologies.com/"
echo "=========================================="