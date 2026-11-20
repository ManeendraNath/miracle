#!/usr/bin/env bash
# Builds the billing portal package we sell (Store product "billing-portal", kind software):
#   ./dist.sh 1.0.0   ->  dist/billing-portal-1.0.0.zip
# Composer runs as COMPOSER_CMD (default: composer), in dist/.build/billing-portal. With the dev container:
#   COMPOSER_CMD='docker exec -u 1000 -e COMPOSER_HOME=/tmp/composer -w /app/dist/.build/billing-portal miracle-backend-1 composer' ./dist.sh 1.0.0
# From the last commit only. The copy runs in portal-only mode: sign-in, customer portal and admin billing, none of
# our website, blog, store or catalogue. The buyer unzips it on their hosting and runs php install.php (INSTALL.md).
set -euo pipefail
cd "$(dirname "$0")"
repo=$PWD
version=${1:?Give the version, e.g. ./dist.sh 1.0.0}
command -v zip >/dev/null || { echo 'zip is needed (apt install zip).' >&2; exit 1; }
[[ $version =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Version like 1.0.0, please."; exit 1; }
[[ -z $(git status --porcelain) ]] || { echo "Commit your changes first: the package is built from the last commit."; exit 1; }

# Inside the repo (git-ignored), so a container that mounts it can run composer there.
work=$repo/dist/.build
rm -rf "$work"
trap 'rm -rf "$work"' EXIT
dir=$work/billing-portal
mkdir -p "$dir"
git archive HEAD | tar -x -C "$dir"
cd "$dir"

# Ours, or for development only.
rm -rf deploy.sh dist.sh docker docker-compose.yml Vagrantfile vagrant .github DESIGN.md README.md docs \
    codeception.yml c3.php phpcs.xml.dist phpstan.neon phpstan-bootstrap.php requirements.php init init.bat yii.bat \
    backend/tests frontend/tests common/tests console/migrations/blog-covers \
    frontend/web/images/projects
# What `php init` would set up, then its templates: the settings files Yii loads (no secrets: those are in .env)
# and the console script (git-ignored here, so not in the archive).
for app in common frontend backend console; do
    cp "environments/prod/$app/config/params-local.php" "$app/config/params-local.php"
done
cp environments/prod/yii yii
chmod 755 yii
rm -rf environments
# Nothing of a copy is for search engines.
printf 'User-agent: *\nDisallow: /\n' | tee frontend/web/robots.txt > backend/web/robots.txt
# Both web roots, for any domain (ours name our admin host; deploy.sh writes our portal's): PHP's error log refused,
# and http sent to https, as COOKIE_SECURE cookies are never kept over http. SSL renewal stays reachable.
for web in frontend/web backend/web; do
    cat > "$web/.htaccess" <<'HT'
RewriteEngine On

<Files "error_log">
    Require all denied
</Files>

RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteCond %{REQUEST_URI} !^/\.well-known/
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php [L]
HT
done
# Only frontend/web and backend/web are for the web (INSTALL.md keeps the folder out of public_html). In case it is
# served anyway: the root files are refused (a blanket deny here would shut the portal too, as Apache reads .htaccess
# files above a document root), and so is every folder that is not a web root. Plain rules LiteSpeed reads too.
cat > .htaccess <<'HT'
# Point the portal address at frontend/web and the admin address at backend/web (INSTALL.md).
<FilesMatch "^(\.env.*|yii|install\.php|composer\.(json|lock)|VERSION|INSTALL\.md|LICENSE.*|error_log)$">
    Require all denied
</FilesMatch>
HT
# A blank browser icon (browsers ask for /favicon.ico anyway): not ours, and no 404 through the app. A copy's own
# goes in frontend/web/brand/favicon.ico (CustomYii::faviconUrl), which updates keep.
echo 'AAABAAEAAQEAAAEAIAAwAAAAFgAAACgAAAABAAAAAgAAAAEAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==' | base64 -d | tee frontend/web/favicon.ico > backend/web/favicon.ico
# The example settings: portal-only, and none of our name or addresses for a buyer who copies it by hand.
sed -i -e 's/^PORTAL_ONLY=false$/PORTAL_ONLY=true/' -e 's/^SENDER_NAME=.*/SENDER_NAME=/' \
    -e 's/^FRONTEND_BASE_URL=.*/FRONTEND_BASE_URL=/' -e 's/^BACKEND_BASE_URL=.*/BACKEND_BASE_URL=/' .env.example
# The portal is licensed by subscription, not open source: our terms at the root. The app template's BSD notice
# (code we built on) is kept beside it, as that licence requires; the libraries keep theirs in vendor/.
mv LICENSE.md LICENSE-yii2-app-template.md
cat > LICENSE.txt <<'TXT'
Billing Portal (c) Miracle Web Technologies. All rights reserved.

This software is licensed, not sold. Your subscription allows one installation, at the web address it was bought
for, while it is paid. You may not resell, share or publish it. The full terms:
https://miraclewebtechnologies.com/licence

Parts of it are built on open-source software under its own licences: the Yii 2 application template
(LICENSE-yii2-app-template.md) and the libraries in vendor/, each with its licence in its own folder.
TXT
echo "$version" > VERSION
# The cron jobs log here (INSTALL.md): there from the start, not only once install.php has run.
mkdir -p runtime/logs

${COMPOSER_CMD:-composer} install --no-dev --optimize-autoloader --no-interaction --quiet
# Parts of libraries nothing loads (their demos and docs, icon packages for JavaScript builds): about a third of the size.
rm -rf vendor/almasaeed2010/adminlte/{docs,pages,build,index.html,index2.html,index3.html,starter.html,iframe.html,iframe-dark.html,package.json,package-lock.json} \
    vendor/fortawesome/font-awesome/{js-packages,metadata}
# Every folder that is not a web root refuses the web, in case the package is served anyway (see .htaccess above).
for d in vendor common console runtime $(ls -d frontend/*/ backend/*/ | grep -v '/web/$'); do
    printf 'Require all denied\n' > "$d/.htaccess"
done

mkdir -p "$repo/dist"
out=$repo/dist/billing-portal-$version.zip
rm -f "$out"
(cd "$work" && zip -qr "$out" billing-portal)
echo "Built $out ($(du -h "$out" | cut -f1)). Upload it to the product in Store > Products."
