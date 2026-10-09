#!/bin/bash

echo "🚀 Starting deployment sequence..."

# 1. Enter the hidden git repository tracker folder
cd /home/rpocncwk/repositories/miracle || exit

# 2. Pull the absolute latest code updates down from GitHub
echo "📥 Fetching latest commits from GitHub..."
git fetch origin
git reset --hard origin/main

# 3. Safely sync application folders to the live execution workspace
echo "📂 Synchronizing project files..."
cp -r /home/rpocncwk/repositories/miracle/frontend /home/rpocncwk/miracle/
cp -r /home/rpocncwk/repositories/miracle/backend /home/rpocncwk/miracle/
cp -r /home/rpocncwk/repositories/miracle/common /home/rpocncwk/miracle/
cp -r /home/rpocncwk/repositories/miracle/console /home/rpocncwk/miracle/

# 4. Sync web public asset folders directly into your public entry domains
echo "🌐 Syncing public web roots..."
cp -r /home/rpocncwk/repositories/miracle/frontend/web/. /home/rpocncwk/public_html/
cp -r /home/rpocncwk/repositories/miracle/backend/web/. /home/rpocncwk/public_html/admin/

# 5. 🛠️ AUTOMATED PRODUCTION PATH PATCHING (Ensures index files point to core folder)
echo "🔧 Hardening production index file entry paths..."
sed -i "s|/../../vendor/|/../miracle/vendor/|g" /home/rpocncwk/public_html/index.php
sed -i "s|/../../common/|/../miracle/common/|g" /home/rpocncwk/public_html/index.php
sed -i "s|/../config/|/../miracle/frontend/config/|g" /home/rpocncwk/public_html/index.php

sed -i "s|/../../vendor/|/../../miracle/vendor/|g" /home/rpocncwk/public_html/admin/index.php
sed -i "s|/../../common/|/../../miracle/common/|g" /home/rpocncwk/public_html/admin/index.php
sed -i "s|/../config/|/../../miracle/backend/config/|g" /home/rpocncwk/public_html/admin/index.php

# 5.5 Run composer dependency sync block to install new calendar range pickers extensions automatically
echo "📦 Installing newly registered framework extensions..."
cd /home/rpocncwk/miracle || exit
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# 6. Explicitly ensure your custom assets are mirrored cleanly
echo "🎨 Refreshing layout design folders..."
if [ -d "/home/rpocncwk/miracle/frontend/web/css" ]; then
    cp -r /home/rpocncwk/miracle/frontend/web/css/. /home/rpocncwk/public_html/css/ 2>/dev/null || cp -r /home/rpocncwk/miracle/frontend/web/css /home/rpocncwk/public_html/
fi

# 7. Automatically run database schema migrations (For new tables/columns)
echo "🗄️ Running database migrations..."
cd /home/rpocncwk/miracle || exit
php yii migrate/up --interactive=0

# 8. 🧹 CLEAN OUT CORRUPTED METADATA AND REBUILD LIVE PACKAGES
echo "🧹 Flushing dynamic asset and runtime caches..."
rm -rf /home/rpocncwk/public_html/assets/*
rm -rf /home/rpocncwk/public_html/admin/assets/*
rm -rf /home/rpocncwk/miracle/frontend/runtime/cache/*
rm -rf /home/rpocncwk/miracle/backend/runtime/cache/*

# Ensure asset directories have strict, open write access for background scripts
chmod -R 777 /home/rpocncwk/public_html/assets
chmod -R 777 /home/rpocncwk/public_html/admin/assets

echo "✅ Deployment completed successfully! Your live site is up to date."
