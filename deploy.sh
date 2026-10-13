#!/bin/bash

set -e

REPO="/home/rpocncwk/repositories/miracle"
APP="/home/rpocncwk/miracle"
PUBLIC="/home/rpocncwk/public_html"

echo "=========================================="
echo "Starting Miracle deployment..."
echo "=========================================="

# ============================================================
# 1. UPDATE GIT REPOSITORY
# ============================================================

echo "[1/10] Fetching latest code..."

cd "$REPO"

git fetch origin
git reset --hard origin/main


# ============================================================
# 2. PRESERVE PRODUCTION-ONLY FILES
# ============================================================

echo "[2/10] Preserving production configuration..."

BACKUP_DIR="/tmp/miracle-deploy-local"

rm -rf "$BACKUP_DIR"

mkdir -p "$BACKUP_DIR/common/config"
mkdir -p "$BACKUP_DIR/frontend/config"
mkdir -p "$BACKUP_DIR/backend/config"
mkdir -p "$BACKUP_DIR/console/config"

# Preserve params-local.php files.
cp -a "$APP/common/config/params-local.php" \
    "$BACKUP_DIR/common/config/" 2>/dev/null || true

cp -a "$APP/frontend/config/params-local.php" \
    "$BACKUP_DIR/frontend/config/" 2>/dev/null || true

cp -a "$APP/backend/config/params-local.php" \
    "$BACKUP_DIR/backend/config/" 2>/dev/null || true

cp -a "$APP/console/config/params-local.php" \
    "$BACKUP_DIR/console/config/" 2>/dev/null || true


# ============================================================
# 3. DEPLOY APPLICATION CODE
# ============================================================

echo "[3/10] Deploying application code..."

mkdir -p "$APP"

# ------------------------------------------------------------
# FRONTEND
# ------------------------------------------------------------

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


# ------------------------------------------------------------
# BACKEND
# ------------------------------------------------------------

for DIR in \
    assets \
    config \
    controllers \
    models \
    views \
    widgets
do
    if [ -d "$REPO/backend/$DIR" ]; then
        rm -rf "$APP/backend/$DIR"
        cp -a "$REPO/backend/$DIR" "$APP/backend/"
    fi
done


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


# ============================================================
# 4. RESTORE PRODUCTION PARAM FILES
# ============================================================

echo "[4/10] Restoring production configuration..."

# If production local parameter files existed before deployment,
# restore them.

if [ -f "$BACKUP_DIR/common/config/params-local.php" ]; then
    cp -a "$BACKUP_DIR/common/config/params-local.php" \
        "$APP/common/config/params-local.php"
else
    cp -a "$REPO/environments/prod/common/config/params-local.php" \
        "$APP/common/config/params-local.php"
fi

if [ -f "$BACKUP_DIR/frontend/config/params-local.php" ]; then
    cp -a "$BACKUP_DIR/frontend/config/params-local.php" \
        "$APP/frontend/config/params-local.php"
else
    cp -a "$REPO/environments/prod/frontend/config/params-local.php" \
        "$APP/frontend/config/params-local.php"
fi

if [ -f "$BACKUP_DIR/backend/config/params-local.php" ]; then
    cp -a "$BACKUP_DIR/backend/config/params-local.php" \
        "$APP/backend/config/params-local.php"
else
    cp -a "$REPO/environments/prod/backend/config/params-local.php" \
        "$APP/backend/config/params-local.php"
fi

if [ -f "$BACKUP_DIR/console/config/params-local.php" ]; then
    cp -a "$BACKUP_DIR/console/config/params-local.php" \
        "$APP/console/config/params-local.php"
else
    cp -a "$REPO/environments/prod/console/config/params-local.php" \
        "$APP/console/config/params-local.php"
fi


# ============================================================
# 5. DEPLOY WEB DIRECTORIES
# ============================================================

echo "[5/10] Deploying web files..."

mkdir -p "$APP/frontend/web"
mkdir -p "$APP/backend/web"

# Preserve generated Yii assets.
mkdir -p "$APP/frontend/web/assets"
mkdir -p "$APP/backend/web/assets"

# Frontend web files, excluding generated assets.
for ITEM in "$REPO/frontend/web/"* "$REPO/frontend/web/".[!.]*; do

    [ -e "$ITEM" ] || continue

    NAME="$(basename "$ITEM")"

    if [ "$NAME" = "assets" ]; then
        continue
    fi

    rm -rf "$APP/frontend/web/$NAME"
    cp -a "$ITEM" "$APP/frontend/web/$NAME"
done


# Backend web files, excluding generated assets.
for ITEM in "$REPO/backend/web/"* "$REPO/backend/web/".[!.]*; do

    [ -e "$ITEM" ] || continue

    NAME="$(basename "$ITEM")"

    if [ "$NAME" = "assets" ]; then
        continue
    fi

    rm -rf "$APP/backend/web/$NAME"
    cp -a "$ITEM" "$APP/backend/web/$NAME"
done


# ============================================================
# 6. ROOT APPLICATION FILES
# ============================================================

echo "[6/10] Deploying root application files..."

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


# ============================================================
# 7. COMPOSER
# ============================================================

echo "[7/10] Installing Composer dependencies..."

cd "$APP"

if command -v composer >/dev/null 2>&1; then

    composer install \
        --prefer-dist \
        --no-interaction \
        --optimize-autoloader

else

    echo "WARNING: Composer command not found."
    echo "Existing vendor directory will be used."

fi


# ============================================================
# 8. FRONTEND PUBLIC ROOT
# ============================================================

echo "[8/10] Updating frontend public root..."

#
# Main cPanel domain:
#
# miraclewebtechnologies.com
#   -> /home/rpocncwk/public_html
#
# Do NOT deploy backend here.
#

# Static directories that are NOT symlinked.

if [ -d "$APP/frontend/web/css" ]; then
    rm -rf "$PUBLIC/css"
    cp -a "$APP/frontend/web/css" "$PUBLIC/css"
fi

if [ -d "$APP/frontend/web/images" ]; then
    rm -rf "$PUBLIC/images"
    cp -a "$APP/frontend/web/images" "$PUBLIC/images"
fi

if [ -d "$APP/frontend/web/js" ]; then
    rm -rf "$PUBLIC/js"
    cp -a "$APP/frontend/web/js" "$PUBLIC/js"
fi


# robots.txt

if [ -f "$APP/frontend/web/robots.txt" ]; then
    cp -a "$APP/frontend/web/robots.txt" "$PUBLIC/robots.txt"
fi


# ------------------------------------------------------------
# Frontend assets
# ------------------------------------------------------------

# public_html/assets should point to:
#
# ../miracle/frontend/web/assets
#
# Recreate only if missing/wrong.

if [ ! -L "$PUBLIC/assets" ]; then
    rm -rf "$PUBLIC/assets"
    ln -s ../miracle/frontend/web/assets "$PUBLIC/assets"
fi


# ------------------------------------------------------------
# Favicon
# ------------------------------------------------------------

# public_html/favicon.ico should point to:
#
# ../miracle/frontend/web/favicon.ico

if [ ! -L "$PUBLIC/favicon.ico" ]; then
    rm -f "$PUBLIC/favicon.ico"
    ln -s ../miracle/frontend/web/favicon.ico "$PUBLIC/favicon.ico"
fi


# ============================================================
# FRONTEND PRODUCTION INDEX
# ============================================================

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
# FRONTEND HTACCESS
# ============================================================

cat > "$PUBLIC/.htaccess" <<'HTACCESS'
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule . index.php [L]
HTACCESS


# ============================================================
# 9. MIGRATIONS + CACHE + ASSETS
# ============================================================

echo "[9/10] Running migrations..."

cd "$APP"

php yii migrate/up --interactive=0


echo "Clearing runtime cache..."

rm -rf "$APP/frontend/runtime/cache/"* 2>/dev/null || true
rm -rf "$APP/backend/runtime/cache/"* 2>/dev/null || true


echo "Clearing Yii published assets..."

rm -rf "$APP/frontend/web/assets/"* 2>/dev/null || true
rm -rf "$APP/backend/web/assets/"* 2>/dev/null || true


# ============================================================
# 10. PERMISSIONS + HEALTH CHECK
# ============================================================

echo "[10/10] Updating writable directory permissions..."

mkdir -p "$APP/frontend/runtime"
mkdir -p "$APP/backend/runtime"
mkdir -p "$APP/frontend/web/assets"
mkdir -p "$APP/backend/web/assets"

chmod -R 775 "$APP/frontend/runtime"
chmod -R 775 "$APP/backend/runtime"
chmod -R 775 "$APP/frontend/web/assets"
chmod -R 775 "$APP/backend/web/assets"


echo "Running PHP syntax checks..."

php -l "$APP/frontend/config/main.php"
php -l "$APP/backend/config/main.php"
php -l "$APP/common/config/params-local.php"
php -l "$APP/frontend/config/params-local.php"
php -l "$APP/backend/config/params-local.php"
php -l "$APP/console/config/params-local.php"


echo "Checking frontend..."

FRONTEND_STATUS="$(
    curl -s -o /dev/null -w "%{http_code}" \
    https://miraclewebtechnologies.com/
)"

echo "Frontend HTTP status: $FRONTEND_STATUS"


echo "Checking backend..."

BACKEND_STATUS="$(
    curl -s -o /dev/null -w "%{http_code}" \
    https://admin.miraclewebtechnologies.com/
)"

echo "Backend HTTP status: $BACKEND_STATUS"


if [ "$FRONTEND_STATUS" -ge 500 ] || [ "$BACKEND_STATUS" -ge 500 ]; then
    echo "ERROR: Application health check failed."
    exit 1
fi


rm -rf "$BACKUP_DIR"


echo "=========================================="
echo "Deployment completed successfully."
echo ""
echo "Frontend: https://miraclewebtechnologies.com/"
echo "Backend : https://admin.miraclewebtechnologies.com/"
echo "=========================================="