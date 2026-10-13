#!/usr/bin/env bash

set -Eeuo pipefail


# =====================================================================
# MIRACLE PRODUCTION DEPLOYMENT
# =====================================================================

readonly REPO="/home/rpocncwk/repositories/miracle"
readonly APP="/home/rpocncwk/miracle"
readonly PUBLIC="/home/rpocncwk/public_html"

readonly FRONTEND_URL="https://miraclewebtechnologies.com/"
readonly BACKEND_URL="https://admin.miraclewebtechnologies.com/"
readonly BACKEND_LOGIN_URL="https://admin.miraclewebtechnologies.com/auth/login"

readonly LOCK_FILE="/home/rpocncwk/.miracle-deploy.lock"

readonly EXPECTED_ASSET_LINK="../miracle/frontend/web/assets"
readonly EXPECTED_FAVICON_LINK="../miracle/frontend/web/favicon.ico"

DEPLOY_TMP=""
ROLLBACK_DIR=""

DEPLOY_STARTED=0
ROLLBACK_AVAILABLE=0
ROLLBACK_IN_PROGRESS=0
KEEP_ROLLBACK=0
DATABASE_MAY_HAVE_CHANGED=0


# =====================================================================
# LOGGING
# =====================================================================

log()
{
    printf '%s\n' "$*"
}


# =====================================================================
# PATH SAFETY
# =====================================================================

assert_paths()
{
    [ "$REPO" = "/home/rpocncwk/repositories/miracle" ] || {
        log "ERROR: Unexpected repository path: $REPO"
        return 1
    }

    [ "$APP" = "/home/rpocncwk/miracle" ] || {
        log "ERROR: Unexpected application path: $APP"
        return 1
    }

    [ "$PUBLIC" = "/home/rpocncwk/public_html" ] || {
        log "ERROR: Unexpected public path: $PUBLIC"
        return 1
    }
}


require_directory()
{
    local DIR="$1"

    if [ ! -d "$DIR" ]; then
        log "ERROR: Required directory missing:"
        log "$DIR"
        return 1
    fi
}


# =====================================================================
# REQUIRED COMMANDS
# =====================================================================

require_commands()
{
    local CMD

    for CMD in \
        git \
        php \
        composer \
        curl \
        cp \
        rm \
        mkdir \
        ln \
        readlink \
        find \
        sort \
        mktemp \
        flock \
        dirname \
        basename
    do

        if ! command -v "$CMD" >/dev/null 2>&1; then
            log "ERROR: Required command not found: $CMD"
            return 1
        fi

    done
}


# =====================================================================
# DEPLOYMENT LOCK
# =====================================================================

acquire_lock()
{
    exec 9>"$LOCK_FILE"

    if ! flock -n 9; then
        log "ERROR: Another Miracle deployment is already running."
        return 1
    fi
}


# =====================================================================
# TEMP WORKSPACE
# =====================================================================

create_workspace()
{
    DEPLOY_TMP="$(mktemp -d /home/rpocncwk/.miracle-deploy.XXXXXX)"
    ROLLBACK_DIR="$DEPLOY_TMP/rollback"

    mkdir -p "$ROLLBACK_DIR"
}


# =====================================================================
# CLEANUP
# =====================================================================

cleanup()
{
    local EXIT_CODE=$?

    trap - EXIT

    if [ "${KEEP_ROLLBACK:-0}" -eq 1 ]; then

        log ""
        log "Recovery workspace preserved:"
        log "${DEPLOY_TMP:-unknown}"

    elif [ -n "${DEPLOY_TMP:-}" ] &&
         [ -d "$DEPLOY_TMP" ]; then

        rm -rf -- "$DEPLOY_TMP"

    fi

    exit "$EXIT_CODE"
}


# =====================================================================
# HTTP STATUS
# =====================================================================

http_status()
{
    local URL="$1"
    local STATUS=""

    if STATUS="$(
        curl \
            --silent \
            --show-error \
            --retry 2 \
            --retry-connrefused \
            --retry-delay 1 \
            --connect-timeout 5 \
            --max-time 20 \
            --output /dev/null \
            --write-out "%{http_code}" \
            "$URL"
    )"; then

        printf '%s' "$STATUS"

    else

        printf '000'

    fi
}


# =====================================================================
# SNAPSHOT APPLICATION
#
# Used for:
#
#   frontend
#   backend
#   console
#
# Preserved rather than snapshotted:
#
#   runtime/
#   web/assets/
#
# Therefore console/runtime survives deployment and rollback.
# =====================================================================

snapshot_application()
{
    local SOURCE="$1"
    local DEST="$2"
    local ITEM
    local NAME

    require_directory "$SOURCE"

    mkdir -p "$DEST"


    while IFS= read -r -d '' ITEM
    do

        NAME="$(basename "$ITEM")"

        case "$NAME" in

            runtime|web)
                continue
                ;;

        esac


        cp -a -- "$ITEM" "$DEST/"

    done < <(
        find "$SOURCE" \
            -mindepth 1 \
            -maxdepth 1 \
            -print0
    )


    # web/ is optional for console.

    if [ -d "$SOURCE/web" ]; then

        mkdir -p "$DEST/web"

        while IFS= read -r -d '' ITEM
        do

            NAME="$(basename "$ITEM")"

            if [ "$NAME" = "assets" ]; then
                continue
            fi


            cp -a -- "$ITEM" "$DEST/web/"

        done < <(
            find "$SOURCE/web" \
                -mindepth 1 \
                -maxdepth 1 \
                -print0
        )

    fi
}


# =====================================================================
# DEPLOY APPLICATION
#
# Deploy all source directories automatically.
#
# Preserve:
#
#   runtime/
#   web/
#
# This means future directories such as:
#
#   services/
#   components/
#   validators/
#   modules/
#   controllers/
#
# require no deploy-script change.
# =====================================================================

deploy_application()
{
    local SOURCE="$1"
    local DEST="$2"
    local ITEM
    local NAME

    require_directory "$SOURCE"

    mkdir -p "$DEST"


    find "$DEST" \
        -mindepth 1 \
        -maxdepth 1 \
        ! -name "runtime" \
        ! -name "web" \
        -exec rm -rf -- {} +


    while IFS= read -r -d '' ITEM
    do

        NAME="$(basename "$ITEM")"


        case "$NAME" in

            runtime|web)
                continue
                ;;

        esac


        cp -a -- "$ITEM" "$DEST/"

    done < <(
        find "$SOURCE" \
            -mindepth 1 \
            -maxdepth 1 \
            -print0
    )
}


# =====================================================================
# DEPLOY WEB
#
# Preserve generated Yii web/assets.
# =====================================================================

deploy_web()
{
    local SOURCE="$1"
    local DEST="$2"
    local ITEM
    local NAME

    require_directory "$SOURCE"

    mkdir -p "$DEST/assets"


    find "$DEST" \
        -mindepth 1 \
        -maxdepth 1 \
        ! -name "assets" \
        -exec rm -rf -- {} +


    while IFS= read -r -d '' ITEM
    do

        NAME="$(basename "$ITEM")"


        if [ "$NAME" = "assets" ]; then
            continue
        fi


        cp -a -- "$ITEM" "$DEST/"

    done < <(
        find "$SOURCE" \
            -mindepth 1 \
            -maxdepth 1 \
            -print0
    )
}


# =====================================================================
# PUBLIC MANIFEST
# =====================================================================

build_public_manifest()
{
    local SOURCE="$1"
    local MANIFEST="$2"
    local ITEM
    local NAME

    require_directory "$SOURCE"

    : > "$MANIFEST"


    while IFS= read -r -d '' ITEM
    do

        NAME="$(basename "$ITEM")"


        case "$NAME" in

            index.php|.htaccess|assets|favicon.ico)
                continue
                ;;

        esac


        case "$NAME" in

            *$'\n'*)

                log "ERROR: Unsupported newline in frontend/web filename:"

                printf '%q\n' "$NAME"

                return 1
                ;;

        esac


        printf '%s\n' "$NAME" >> "$MANIFEST"

    done < <(
        find "$SOURCE" \
            -mindepth 1 \
            -maxdepth 1 \
            -print0
    )


    sort -u -o "$MANIFEST" "$MANIFEST"
}


# =====================================================================
# PARAMS-LOCAL PRESERVATION
# =====================================================================

preserve_params()
{
    local RELATIVE="$1"
    local SOURCE="$APP/$RELATIVE"
    local DEST="$DEPLOY_TMP/params/$(dirname "$RELATIVE")"


    if [ -f "$SOURCE" ]; then

        mkdir -p "$DEST"

        cp -a -- "$SOURCE" "$DEST/"

    fi
}


restore_params()
{
    local RELATIVE="$1"
    local TEMPLATE="$2"
    local SAVED="$DEPLOY_TMP/params/$RELATIVE"


    if [ -f "$SAVED" ]; then

        mkdir -p "$(dirname "$APP/$RELATIVE")"

        cp -a -- "$SAVED" "$APP/$RELATIVE"

        return 0

    fi


    if [ -f "$REPO/$TEMPLATE" ]; then

        mkdir -p "$(dirname "$APP/$RELATIVE")"

        cp -a -- \
            "$REPO/$TEMPLATE" \
            "$APP/$RELATIVE"

        return 0

    fi


    log "ERROR: Cannot restore required configuration:"
    log "$RELATIVE"

    return 1
}


# =====================================================================
# APPLICATION HEALTH CHECK
# =====================================================================

verify_application()
{
    local FRONTEND_STATUS
    local BACKEND_STATUS
    local BACKEND_LOGIN_STATUS


    FRONTEND_STATUS="$(http_status "$FRONTEND_URL")"

    BACKEND_STATUS="$(http_status "$BACKEND_URL")"

    BACKEND_LOGIN_STATUS="$(http_status "$BACKEND_LOGIN_URL")"


    log "Frontend HTTP status     : $FRONTEND_STATUS"
    log "Backend HTTP status      : $BACKEND_STATUS"
    log "Backend login HTTP status: $BACKEND_LOGIN_STATUS"


    if [ "$FRONTEND_STATUS" != "200" ]; then
        return 1
    fi


    if [ "$BACKEND_STATUS" != "200" ] &&
       [ "$BACKEND_STATUS" != "302" ]; then

        return 1

    fi


    if [ "$BACKEND_LOGIN_STATUS" != "200" ]; then
        return 1
    fi


    return 0
}


# =====================================================================
# ROLLBACK
# =====================================================================

rollback()
{
    local ROLLBACK_OK=1
    local FILE
    local ITEM
    local NAME


    if [ "$ROLLBACK_IN_PROGRESS" -eq 1 ]; then

        log "ERROR: Rollback already in progress."

        KEEP_ROLLBACK=1

        return 1

    fi


    if [ "$ROLLBACK_AVAILABLE" -ne 1 ]; then

        log "ERROR: No rollback snapshot available."

        return 1

    fi


    ROLLBACK_IN_PROGRESS=1


    trap - ERR INT TERM HUP

    set +e


    log ""
    log "=========================================="
    log "ROLLING BACK APPLICATION"
    log "=========================================="


    # -----------------------------------------------------------------
    # COMMON
    # -----------------------------------------------------------------

    if [ -d "$ROLLBACK_DIR/common" ]; then

        rm -rf -- "$APP/common"


        if ! cp -a -- \
            "$ROLLBACK_DIR/common" \
            "$APP/common"; then

            log "ERROR: Failed restoring common."

            ROLLBACK_OK=0

        fi

    else

        log "ERROR: common rollback snapshot missing."

        ROLLBACK_OK=0

    fi


    # -----------------------------------------------------------------
    # FRONTEND
    #
    # runtime and generated assets remain untouched.
    # -----------------------------------------------------------------

    if [ -d "$ROLLBACK_DIR/frontend" ]; then

        if ! deploy_application \
            "$ROLLBACK_DIR/frontend" \
            "$APP/frontend"; then

            ROLLBACK_OK=0

        fi


        if [ -d "$ROLLBACK_DIR/frontend/web" ]; then

            if ! deploy_web \
                "$ROLLBACK_DIR/frontend/web" \
                "$APP/frontend/web"; then

                ROLLBACK_OK=0

            fi

        fi

    else

        log "ERROR: frontend rollback snapshot missing."

        ROLLBACK_OK=0

    fi


    # -----------------------------------------------------------------
    # BACKEND
    #
    # runtime and generated assets remain untouched.
    # -----------------------------------------------------------------

    if [ -d "$ROLLBACK_DIR/backend" ]; then

        if ! deploy_application \
            "$ROLLBACK_DIR/backend" \
            "$APP/backend"; then

            ROLLBACK_OK=0

        fi


        if [ -d "$ROLLBACK_DIR/backend/web" ]; then

            if ! deploy_web \
                "$ROLLBACK_DIR/backend/web" \
                "$APP/backend/web"; then

                ROLLBACK_OK=0

            fi

        fi

    else

        log "ERROR: backend rollback snapshot missing."

        ROLLBACK_OK=0

    fi


    # -----------------------------------------------------------------
    # CONSOLE
    #
    # IMPORTANT:
    #
    # Uses deploy_application instead of rm -rf console.
    #
    # Therefore:
    #
    #   console/controllers  restored
    #   console/models       restored
    #   console/migrations   restored
    #   console/components   restored
    #   console/config       restored
    #
    # while:
    #
    #   console/runtime      preserved
    # -----------------------------------------------------------------

    if [ -d "$ROLLBACK_DIR/console" ]; then

        if ! deploy_application \
            "$ROLLBACK_DIR/console" \
            "$APP/console"; then

            log "ERROR: Failed restoring console."

            ROLLBACK_OK=0

        fi


        if [ -d "$ROLLBACK_DIR/console/web" ]; then

            if ! deploy_web \
                "$ROLLBACK_DIR/console/web" \
                "$APP/console/web"; then

                ROLLBACK_OK=0

            fi

        fi

    else

        log "ERROR: console rollback snapshot missing."

        ROLLBACK_OK=0

    fi


    # -----------------------------------------------------------------
    # ROOT FILES
    # -----------------------------------------------------------------

    for FILE in \
        yii \
        composer.json \
        composer.lock
    do

        if [ -f "$ROLLBACK_DIR/root/$FILE" ]; then

            if ! cp -a -- \
                "$ROLLBACK_DIR/root/$FILE" \
                "$APP/$FILE"; then

                log "ERROR: Failed restoring $FILE."

                ROLLBACK_OK=0

            fi

        fi

    done


    # -----------------------------------------------------------------
    # PUBLIC FILES
    # -----------------------------------------------------------------

    if [ -f "$DEPLOY_TMP/public-union.manifest" ]; then

        while IFS= read -r NAME
        do

            [ -n "$NAME" ] || continue

            rm -rf -- "$PUBLIC/$NAME"

        done < "$DEPLOY_TMP/public-union.manifest"

    fi


    if [ -d "$ROLLBACK_DIR/public-managed" ]; then

        while IFS= read -r -d '' ITEM
        do

            NAME="$(basename "$ITEM")"


            if ! cp -a -- \
                "$ITEM" \
                "$PUBLIC/$NAME"; then

                ROLLBACK_OK=0

            fi

        done < <(
            find "$ROLLBACK_DIR/public-managed" \
                -mindepth 1 \
                -maxdepth 1 \
                -print0
        )

    fi


    # -----------------------------------------------------------------
    # SPECIAL PUBLIC FILES
    # -----------------------------------------------------------------

    for ITEM in \
        index.php \
        .htaccess \
        assets \
        favicon.ico
    do

        rm -rf -- "$PUBLIC/$ITEM"


        if [ -e "$ROLLBACK_DIR/public-special/$ITEM" ] ||
           [ -L "$ROLLBACK_DIR/public-special/$ITEM" ]; then

            if ! cp -a -- \
                "$ROLLBACK_DIR/public-special/$ITEM" \
                "$PUBLIC/$ITEM"; then

                ROLLBACK_OK=0

            fi

        fi

    done


    # -----------------------------------------------------------------
    # COMPOSER
    # -----------------------------------------------------------------

    if [ -f "$APP/composer.lock" ]; then

        log "Restoring previous Composer dependencies..."


        if ! (
            cd "$APP"

            composer install \
                --prefer-dist \
                --no-interaction \
                --optimize-autoloader
        ); then

            log "ERROR: Composer rollback failed."

            ROLLBACK_OK=0

        fi

    else

        log "ERROR: Previous composer.lock unavailable."

        ROLLBACK_OK=0

    fi


    # -----------------------------------------------------------------
    # RUNTIME + ASSETS
    # -----------------------------------------------------------------

    mkdir -p "$APP/frontend/runtime"
    mkdir -p "$APP/backend/runtime"
    mkdir -p "$APP/console/runtime"

    mkdir -p "$APP/frontend/web/assets"
    mkdir -p "$APP/backend/web/assets"


    chmod -R 775 "$APP/frontend/runtime" ||
        ROLLBACK_OK=0

    chmod -R 775 "$APP/backend/runtime" ||
        ROLLBACK_OK=0

    chmod -R 775 "$APP/console/runtime" ||
        ROLLBACK_OK=0

    chmod -R 775 "$APP/frontend/web/assets" ||
        ROLLBACK_OK=0

    chmod -R 775 "$APP/backend/web/assets" ||
        ROLLBACK_OK=0


    # -----------------------------------------------------------------
    # DATABASE WARNING
    # -----------------------------------------------------------------

    if [ "$DATABASE_MAY_HAVE_CHANGED" -eq 1 ]; then

        log ""
        log "WARNING:"
        log "Database migration processing had started."
        log "Database migrations were NOT automatically rolled back."
        log "Review migration state manually."

    fi


    # -----------------------------------------------------------------
    # VERIFY CONSOLE AFTER ROLLBACK
    # -----------------------------------------------------------------

    if ! (
        cd "$APP"
        php yii help >/dev/null
    ); then

        log "ERROR: Yii console failed after rollback."

        ROLLBACK_OK=0

    fi


    # -----------------------------------------------------------------
    # HEALTH CHECK
    # -----------------------------------------------------------------

    log ""
    log "Checking restored application..."


    if ! verify_application; then

        log "ERROR: Restored application failed health checks."

        ROLLBACK_OK=0

    fi


    ROLLBACK_IN_PROGRESS=0


    if [ "$ROLLBACK_OK" -eq 1 ]; then

        KEEP_ROLLBACK=0

        log ""
        log "=========================================="
        log "APPLICATION ROLLBACK SUCCESSFUL"
        log "=========================================="

        return 0

    fi


    KEEP_ROLLBACK=1


    log ""
    log "=========================================="
    log "ROLLBACK COMPLETED WITH ERRORS"
    log "=========================================="

    log ""
    log "Recovery workspace preserved:"
    log "$DEPLOY_TMP"


    return 1
}


# =====================================================================
# ERROR HANDLER
# =====================================================================

on_error()
{
    local EXIT_CODE=$?
    local LINE="$1"
    local COMMAND="$2"


    trap - ERR INT TERM HUP

    set +e


    log ""
    log "=========================================="
    log "DEPLOYMENT FAILED"
    log "=========================================="

    log "Line     : $LINE"
    log "Command  : $COMMAND"
    log "Exit code: $EXIT_CODE"


    if [ "$DEPLOY_STARTED" -eq 1 ] &&
       [ "$ROLLBACK_AVAILABLE" -eq 1 ]; then

        if ! rollback; then
            KEEP_ROLLBACK=1
        fi

    fi


    exit "$EXIT_CODE"
}


# =====================================================================
# SIGNAL HANDLER
# =====================================================================

on_signal()
{
    local SIGNAL="$1"


    trap - ERR INT TERM HUP

    set +e


    log ""
    log "=========================================="
    log "DEPLOYMENT INTERRUPTED"
    log "=========================================="

    log "Signal: $SIGNAL"


    if [ "$DEPLOY_STARTED" -eq 1 ] &&
       [ "$ROLLBACK_AVAILABLE" -eq 1 ]; then

        if ! rollback; then
            KEEP_ROLLBACK=1
        fi

    fi


    case "$SIGNAL" in

        SIGINT)
            exit 130
            ;;

        SIGTERM)
            exit 143
            ;;

        SIGHUP)
            exit 129
            ;;

        *)
            exit 1
            ;;

    esac
}


trap 'on_error "$LINENO" "$BASH_COMMAND"' ERR

trap 'on_signal SIGINT' INT
trap 'on_signal SIGTERM' TERM
trap 'on_signal SIGHUP' HUP

trap cleanup EXIT


# =====================================================================
# START
# =====================================================================

log "=========================================="
log "Starting Miracle deployment..."
log "=========================================="


# =====================================================================
# 1. PREFLIGHT
# =====================================================================

log "[1/15] Running preflight checks..."


require_commands

assert_paths

acquire_lock

create_workspace


require_directory "$REPO"
require_directory "$APP"
require_directory "$PUBLIC"


if [ ! -d "$REPO/.git" ]; then

    log "ERROR: Invalid Git deployment repository."

    exit 1

fi


if [ ! -f "$APP/.env" ]; then

    log "ERROR: Production .env is missing."

    exit 1

fi


if [ ! -r "$APP/.env" ]; then

    log "ERROR: Production .env is unreadable."

    exit 1

fi


# =====================================================================
# 2. FETCH RELEASE
# =====================================================================

log "[2/15] Fetching latest release..."


cd "$REPO"


git fetch origin

git reset --hard origin/main


COMMIT="$(git rev-parse --short HEAD)"


log "Deploying commit: $COMMIT"


# =====================================================================
# 3. VERIFY GIT SECURITY
# =====================================================================

log "[3/15] Verifying Git security rules..."


if git ls-files --error-unmatch .env >/dev/null 2>&1; then

    log "ERROR: .env is tracked by Git."

    exit 1

fi


for FILE in \
    common/config/params-local.php \
    frontend/config/params-local.php \
    backend/config/params-local.php \
    console/config/params-local.php
do

    if git ls-files \
        --error-unmatch \
        "$FILE" \
        >/dev/null 2>&1; then

        log "ERROR: Production-only configuration tracked:"
        log "$FILE"

        exit 1

    fi

done


# =====================================================================
# 4. VALIDATE RELEASE STRUCTURE
# =====================================================================

log "[4/15] Validating release structure..."


REQUIRED_FILES=(

    "common/config/main.php"
    "common/config/main-local.php"

    "frontend/config/main.php"
    "frontend/config/main-local.php"
    "frontend/web/index.php"

    "backend/config/main.php"
    "backend/config/main-local.php"
    "backend/web/index.php"

    "console/config/main.php"
    "console/config/main-local.php"

    "composer.json"
    "composer.lock"

    "yii"

)


for FILE in "${REQUIRED_FILES[@]}"
do

    if [ ! -f "$REPO/$FILE" ]; then

        log "ERROR: Required release file missing:"
        log "$FILE"

        exit 1

    fi

done


# =====================================================================
# 5. PHP SYNTAX
# =====================================================================

log "[5/15] Checking PHP syntax..."


PHP_SYNTAX_FAILED=0


while IFS= read -r -d '' FILE
do

    if ! php -l "$FILE" >/dev/null; then

        log "ERROR: PHP syntax error:"
        log "$FILE"

        PHP_SYNTAX_FAILED=1

    fi

done < <(
    find \
        "$REPO/common" \
        "$REPO/frontend" \
        "$REPO/backend" \
        "$REPO/console" \
        -type f \
        -name "*.php" \
        ! -path "*/runtime/*" \
        ! -path "*/web/assets/*" \
        -print0
)


if [ "$PHP_SYNTAX_FAILED" -ne 0 ]; then

    log "ERROR: Release contains PHP syntax errors."

    exit 1

fi


php -l "$REPO/yii" >/dev/null


# =====================================================================
# 6. PUBLIC MANIFESTS
# =====================================================================

log "[6/15] Building public manifests..."


build_public_manifest \
    "$APP/frontend/web" \
    "$DEPLOY_TMP/public-old.manifest"


build_public_manifest \
    "$REPO/frontend/web" \
    "$DEPLOY_TMP/public-new.manifest"


cat \
    "$DEPLOY_TMP/public-old.manifest" \
    "$DEPLOY_TMP/public-new.manifest" |
    sort -u \
    > "$DEPLOY_TMP/public-union.manifest"


# =====================================================================
# 7. ROLLBACK SNAPSHOT
# =====================================================================

log "[7/15] Creating rollback snapshot..."


mkdir -p "$ROLLBACK_DIR/root"

mkdir -p "$ROLLBACK_DIR/public-managed"

mkdir -p "$ROLLBACK_DIR/public-special"


cp -a -- \
    "$APP/common" \
    "$ROLLBACK_DIR/common"


# ---------------------------------------------------------------------
# FRONTEND SNAPSHOT
# ---------------------------------------------------------------------

snapshot_application \
    "$APP/frontend" \
    "$ROLLBACK_DIR/frontend"


# ---------------------------------------------------------------------
# BACKEND SNAPSHOT
# ---------------------------------------------------------------------

snapshot_application \
    "$APP/backend" \
    "$ROLLBACK_DIR/backend"


# ---------------------------------------------------------------------
# CONSOLE SNAPSHOT
#
# console/runtime is intentionally NOT copied.
#
# It remains untouched throughout deployment.
# ---------------------------------------------------------------------

snapshot_application \
    "$APP/console" \
    "$ROLLBACK_DIR/console"


# ---------------------------------------------------------------------
# ROOT FILES
# ---------------------------------------------------------------------

for FILE in \
    yii \
    composer.json \
    composer.lock
do

    if [ -f "$APP/$FILE" ]; then

        cp -a -- \
            "$APP/$FILE" \
            "$ROLLBACK_DIR/root/$FILE"

    fi

done


# ---------------------------------------------------------------------
# MANAGED PUBLIC FILES
# ---------------------------------------------------------------------

while IFS= read -r NAME
do

    [ -n "$NAME" ] || continue


    if [ -e "$PUBLIC/$NAME" ] ||
       [ -L "$PUBLIC/$NAME" ]; then

        cp -a -- \
            "$PUBLIC/$NAME" \
            "$ROLLBACK_DIR/public-managed/$NAME"

    fi

done < "$DEPLOY_TMP/public-union.manifest"


# ---------------------------------------------------------------------
# SPECIAL PUBLIC FILES
# ---------------------------------------------------------------------

for ITEM in \
    index.php \
    .htaccess \
    assets \
    favicon.ico
do

    if [ -e "$PUBLIC/$ITEM" ] ||
       [ -L "$PUBLIC/$ITEM" ]; then

        cp -a -- \
            "$PUBLIC/$ITEM" \
            "$ROLLBACK_DIR/public-special/$ITEM"

    fi

done


ROLLBACK_AVAILABLE=1


# =====================================================================
# 8. PRESERVE PARAMS
# =====================================================================

log "[8/15] Preserving production configuration..."


mkdir -p "$DEPLOY_TMP/params/common/config"

mkdir -p "$DEPLOY_TMP/params/frontend/config"

mkdir -p "$DEPLOY_TMP/params/backend/config"

mkdir -p "$DEPLOY_TMP/params/console/config"


preserve_params \
    "common/config/params-local.php"


preserve_params \
    "frontend/config/params-local.php"


preserve_params \
    "backend/config/params-local.php"


preserve_params \
    "console/config/params-local.php"


DEPLOY_STARTED=1


# =====================================================================
# 9. DEPLOY APPLICATION SOURCE
# =====================================================================

log "[9/15] Deploying application source..."


# ---------------------------------------------------------------------
# COMMON
# ---------------------------------------------------------------------

rm -rf -- "$APP/common"

cp -a -- \
    "$REPO/common" \
    "$APP/common"


# ---------------------------------------------------------------------
# FRONTEND
# ---------------------------------------------------------------------

deploy_application \
    "$REPO/frontend" \
    "$APP/frontend"


deploy_web \
    "$REPO/frontend/web" \
    "$APP/frontend/web"


# ---------------------------------------------------------------------
# BACKEND
# ---------------------------------------------------------------------

deploy_application \
    "$REPO/backend" \
    "$APP/backend"


deploy_web \
    "$REPO/backend/web" \
    "$APP/backend/web"


# ---------------------------------------------------------------------
# CONSOLE
#
# This deploys:
#
#   controllers/
#   migrations/
#   models/
#   components/
#   config/
#   commands/
#   modules/
#   etc.
#
# while preserving:
#
#   console/runtime/
# ---------------------------------------------------------------------

deploy_application \
    "$REPO/console" \
    "$APP/console"


# Console normally has no web directory.
# If your repository has one, deploy it safely.

if [ -d "$REPO/console/web" ]; then

    deploy_web \
        "$REPO/console/web" \
        "$APP/console/web"

fi


# =====================================================================
# 10. RESTORE PRODUCTION PARAMETERS
# =====================================================================

log "[10/15] Restoring production configuration..."


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


# =====================================================================
# 11. VERIFY CONFIGURATION
# =====================================================================

log "[11/15] Verifying deployed configuration..."


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

        log "ERROR: Required configuration missing:"
        log "$FILE"

        exit 1

    fi


    php -l "$APP/$FILE" >/dev/null

done


if [ ! -f "$APP/.env" ] ||
   [ ! -r "$APP/.env" ]; then

    log "ERROR: Production .env missing or unreadable."

    exit 1

fi


# =====================================================================
# 12. ROOT FILES + COMPOSER
# =====================================================================

log "[12/15] Installing dependencies..."


for FILE in \
    yii \
    composer.json \
    composer.lock
do

    cp -a -- \
        "$REPO/$FILE" \
        "$APP/$FILE"

done


cd "$APP"


composer install \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader


# =====================================================================
# 13. CONSOLE BOOTSTRAP
# =====================================================================

log "[13/15] Verifying Yii console..."


mkdir -p "$APP/console/runtime"

chmod -R 775 "$APP/console/runtime"


php yii help >/dev/null


# =====================================================================
# 14. PUBLIC ROOT + MIGRATIONS
# =====================================================================

log "[14/15] Updating frontend public root..."


while IFS= read -r NAME
do

    [ -n "$NAME" ] || continue

    rm -rf -- "$PUBLIC/$NAME"

done < "$DEPLOY_TMP/public-union.manifest"


while IFS= read -r NAME
do

    [ -n "$NAME" ] || continue


    cp -a -- \
        "$APP/frontend/web/$NAME" \
        "$PUBLIC/$NAME"

done < "$DEPLOY_TMP/public-new.manifest"


# ---------------------------------------------------------------------
# ASSETS SYMLINK
# ---------------------------------------------------------------------

CURRENT_ASSET_LINK=""


if [ -L "$PUBLIC/assets" ]; then

    CURRENT_ASSET_LINK="$(
        readlink "$PUBLIC/assets" || true
    )"

fi


if [ ! -L "$PUBLIC/assets" ] ||
   [ "$CURRENT_ASSET_LINK" != "$EXPECTED_ASSET_LINK" ]; then

    rm -rf -- "$PUBLIC/assets"


    ln -s \
        "$EXPECTED_ASSET_LINK" \
        "$PUBLIC/assets"

fi


# ---------------------------------------------------------------------
# FAVICON
# ---------------------------------------------------------------------

CURRENT_FAVICON_LINK=""


if [ -L "$PUBLIC/favicon.ico" ]; then

    CURRENT_FAVICON_LINK="$(
        readlink "$PUBLIC/favicon.ico" || true
    )"

fi


if [ ! -L "$PUBLIC/favicon.ico" ] ||
   [ "$CURRENT_FAVICON_LINK" != "$EXPECTED_FAVICON_LINK" ]; then

    rm -rf -- "$PUBLIC/favicon.ico"


    ln -s \
        "$EXPECTED_FAVICON_LINK" \
        "$PUBLIC/favicon.ico"

fi


# ---------------------------------------------------------------------
# PRODUCTION INDEX
# ---------------------------------------------------------------------

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


# ---------------------------------------------------------------------
# HTACCESS
# ---------------------------------------------------------------------

cat > "$PUBLIC/.htaccess" <<'HTACCESS'
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule . index.php [L]
HTACCESS


php -l "$PUBLIC/index.php" >/dev/null


# ---------------------------------------------------------------------
# DATABASE MIGRATION
# ---------------------------------------------------------------------

log "Running database migrations..."


cd "$APP"


DATABASE_MAY_HAVE_CHANGED=1


php yii migrate/up --interactive=0


# ---------------------------------------------------------------------
# WRITABLE DIRECTORIES
# ---------------------------------------------------------------------

log "Preparing writable directories..."


mkdir -p "$APP/frontend/runtime"

mkdir -p "$APP/backend/runtime"

mkdir -p "$APP/console/runtime"

mkdir -p "$APP/frontend/web/assets"

mkdir -p "$APP/backend/web/assets"


chmod -R 775 "$APP/frontend/runtime"

chmod -R 775 "$APP/backend/runtime"

chmod -R 775 "$APP/console/runtime"

chmod -R 775 "$APP/frontend/web/assets"

chmod -R 775 "$APP/backend/web/assets"


# =====================================================================
# 15. HEALTH CHECK
# =====================================================================

log "[15/15] Running production health checks..."


if ! verify_application; then

    log "ERROR: Production health check failed."

    exit 1

fi


# =====================================================================
# FINAL CONSOLE CHECK
# =====================================================================

log "Verifying console after deployment..."


php yii help >/dev/null


# =====================================================================
# SUCCESS
# =====================================================================

DEPLOY_STARTED=0

KEEP_ROLLBACK=0


log ""
log "=========================================="
log "Deployment completed successfully."
log "=========================================="

log "Commit   : $COMMIT"
log "Frontend : $FRONTEND_URL"
log "Backend  : $BACKEND_URL"