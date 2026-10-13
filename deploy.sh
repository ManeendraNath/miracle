#!/bin/bash

set -e

REPO="/home/rpocncwk/repositories/miracle"
APP="/home/rpocncwk/miracle"
PUBLIC="/home/rpocncwk/public_html"

echo "Starting deployment..."

# ============================================================
# 1. UPDATE GIT REPOSITORY
# ============================================================

echo "Fetching latest code..."

cd "$REPO"

git fetch origin
git reset --hard origin/main


# ============================================================
# 2. SYNC APPLICATION CODE
# ============================================================

echo "Synchronising application..."

mkdir -p "$APP"

rsync -a --delete \
    --exclude='runtime/' \
    --exclude='web/assets/' \
    "$REPO/frontend/" "$APP/frontend/"

rsync -a --delete \
    --exclude='runtime/' \
    --exclude='web/assets/' \
    "$REPO/backend/" "$APP/backend/"

rsync -a --delete \
    "$REPO/common/" "$APP/common/"

rsync -a --delete \
    "$REPO/console/" "$APP/console/"


# ============================================================
# 3. SYNC PROJECT FILES REQUIRED AT APPLICATION ROOT
# ============================================================

echo "Synchronising root application files..."

cp "$REPO/yii" "$APP/yii"
cp "$REPO/yii.bat" "$APP/yii.bat" 2>/dev/null || true
cp "$REPO/composer.json" "$APP/composer.json"
cp "$REPO/composer.lock" "$APP/composer.lock"


# ============================================================
# 4. COMPOSER DEPENDENCIES
# ============================================================

echo "Installing Composer dependencies..."

cd "$APP"

composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader


# ============================================================
# 5. FRONTEND PUBLIC WEB ROOT
#
# Main cPanel domain is fixed to public_html, so only the
# frontend public files need to be exposed there.
# ============================================================

echo "Synchronising frontend public files..."

# Static/source web resources.
# Generated /assets and production index.php are handled
# separately below.

rsync -a --delete \
    --exclude='assets/' \
    --exclude='index.php' \
    --exclude='.htaccess' \
    "$APP/frontend/web/" "$PUBLIC/"


# ============================================================
# 6. FRONTEND ENTRY POINT
# ============================================================

echo "Installing frontend entry point..."

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
# 7. FRONTEND HTACCESS
# ============================================================

echo "Installing frontend .htaccess..."

cat > "$PUBLIC/.htaccess" <<'HTACCESS'
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule . index.php [L]
HTACCESS


# ============================================================
# 8. ENSURE REQUIRED RUNTIME/ASSET DIRECTORIES EXIST
# ============================================================

echo "Preparing runtime directories..."

mkdir -p \
    "$APP/frontend/runtime/cache" \
    "$APP/backend/runtime/cache" \
    "$APP/frontend/web/assets" \
    "$APP/backend/web/assets" \
    "$PUBLIC/assets"


# ============================================================
# 9. DATABASE MIGRATION
# ============================================================

echo "Running database migrations..."

cd "$APP"

php yii migrate/up --interactive=0


# ============================================================
# 10. CLEAR APPLICATION CACHE
# ============================================================

echo "Clearing runtime caches..."

rm -rf "$APP/frontend/runtime/cache/"*
rm -rf "$APP/backend/runtime/cache/"*


# ============================================================
# 11. CLEAR GENERATED ASSETS
# ============================================================

echo "Clearing generated assets..."

rm -rf "$APP/frontend/web/assets/"*
rm -rf "$APP/backend/web/assets/"*
rm -rf "$PUBLIC/assets/"*


# ============================================================
# 12. PERMISSIONS
# ============================================================

echo "Updating writable-directory permissions..."

chmod -R 775 \
    "$APP/frontend/runtime" \
    "$APP/backend/runtime" \
    "$APP/frontend/web/assets" \
    "$APP/backend/web/assets" \
    "$PUBLIC/assets"


echo "Deployment completed successfully."