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
# 👇 FIXED: Uses rsync to safely skip index.php so your production paths are NEVER overwritten
echo "🌐 Syncing public web roots..."
rsync -av --exclude='index.php' /home/rpocncwk/repositories/miracle/frontend/web/ /home/rpocncwk/public_html/
rsync -av --exclude='index.php' /home/rpocncwk/repositories/miracle/backend/web/ /home/rpocncwk/public_html/admin/

# 5. 👇 ADDED FOR FUTURE: Explicitly ensure your custom assets are mirrored cleanly
echo "🎨 Refreshing layout design folders..."
cp -r /home/rpocncwk/miracle/frontend/web/css /home/rpocncwk/public_html/
cp -r /home/rpocncwk/miracle/frontend/web/js /home/rpocncwk/public_html/
if [ -d "/home/rpocncwk/miracle/frontend/web/fonts" ]; then
    cp -r /home/rpocncwk/miracle/frontend/web/fonts /home/rpocncwk/public_html/
fi

# 6. Automatically run database schema migrations (For new tables/columns)
echo "🗄️ Running database migrations..."
cd /home/rpocncwk/miracle || exit
php yii migrate/up --interactive=0

# 7. Flush production layout and routing memory cache maps immediately
echo "🧹 Flushing application layout caches..."
rm -rf /home/rpocncwk/miracle/frontend/runtime/cache/*
rm -rf /home/rpocncwk/miracle/backend/runtime/cache/*

echo "✅ Deployment completed successfully! Your live site is up to date."

