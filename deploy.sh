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
    if [ "$REPO" != "/home/rpocncwk/repositories/miracle" ]; then
        log "ERROR: Unexpected repository path: $REPO"
        return 1
    fi

    if [ "$APP" != "/home/rpocncwk/miracle" ]; then
        log "ERROR: Unexpected application path: $APP"
        return 1
    fi

    if [ "$PUBLIC" != "/home/rpocncwk/public_html" ]; then
        log "ERROR: Unexpected public path: $PUBLIC"
        return 1
    fi
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


assert_safe_destination()
{
    local DEST="$1"

    if [ -z "${DEST:-}" ] || [ "$DEST" = "/" ]; then
        log "ERROR: Unsafe destination: ${DEST:-<empty>}"
        return 1
    fi

    case "$DEST" in
        "$APP"|"$APP/"*|"$PUBLIC"|"$PUBLIC/"*)
            return 0
            ;;
        *)
            log "ERROR: Destination is outside approved paths:"
            log "$DEST"
            return 1
            ;;
    esac
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

        rm -rf -- "${DEPLOY_TMP:?}"
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
# APPLICATION SNAPSHOT
#
# Preserves runtime by intentionally excluding it from snapshot.
#
# web/assets is also not snapshotted because it is generated runtime
# content and remains in place during deployment and rollback.
# =====================================================================

snapshot_application()
{
    local SOURCE="$1"
    local DEST="$2"
    local LIST_FILE
    local ITEM
    local NAME

    require_directory "$SOURCE"

    mkdir -p "$DEST"

    LIST_FILE="$(mktemp "${DEPLOY_TMP:?}/snapshot.XXXXXX")"

    if ! find "$SOURCE" \
        -mindepth 1 \
        -maxdepth 1 \
        -print0 > "$LIST_FILE"; then

        log "ERROR: Failed listing application source:"
        log "$SOURCE"
        return 1
    fi

    while IFS= read -r -d '' ITEM
    do
        NAME="$(basename "$ITEM")"

        case "$NAME" in
            runtime|web)
                continue
                ;;
        esac

        cp -a -- "$ITEM" "$DEST/"

    done < "$LIST_FILE"

    if [ -d "$SOURCE/web" ]; then
        mkdir -p "$DEST/web"

        : > "$LIST_FILE"

        if ! find "$SOURCE/web" \
            -mindepth 1 \
            -maxdepth 1 \
            -print0 > "$LIST_FILE"; then

            log "ERROR: Failed listing web directory:"
            log "$SOURCE/web"
            return 1
        fi

        while IFS= read -r -d '' ITEM
        do
            NAME="$(basename "$ITEM")"

            if [ "$NAME" = "assets" ]; then
                continue
            fi

            cp -a -- "$ITEM" "$DEST/web/"

        done < "$LIST_FILE"
    fi

    rm -f -- "$LIST_FILE"
}


# =====================================================================
# APPLICATION DEPLOYMENT
#
# Used for:
#   frontend
#   backend
#   console
#
# Preserves:
#   runtime/
#   web/
#
# Web directories are handled separately.
# =====================================================================

deploy_application()
{
    local SOURCE="$1"
    local DEST="$2"
    local LIST_FILE
    local ITEM
    local NAME

    require_directory "$SOURCE"
    assert_safe_destination "$DEST"

    mkdir -p "$DEST"

    find "$DEST" \
        -mindepth 1 \
        -maxdepth 1 \
        ! -name "runtime" \
        ! -name "web" \
        -exec rm -rf -- {} +

    LIST_FILE="$(mktemp "${DEPLOY_TMP:?}/deploy-app.XXXXXX")"

    if ! find "$SOURCE" \
        -mindepth 1 \
        -maxdepth 1 \
        -print0 > "$LIST_FILE"; then

        log "ERROR: Failed listing deployment source:"
        log "$SOURCE"
        return 1
    fi

    while IFS= read -r -d '' ITEM
    do
        NAME="$(basename "$ITEM")"

        case "$NAME" in
            runtime|web)
                continue
                ;;
        esac

        cp -a -- "$ITEM" "$DEST/"

    done < "$LIST_FILE"

    rm -f -- "$LIST_FILE"
}


# =====================================================================
# WEB DEPLOYMENT
#
# Generated Yii assets are preserved.
# =====================================================================

deploy_web()
{
    local SOURCE="$1"
    local DEST="$2"
    local LIST_FILE
    local ITEM
    local NAME

    require_directory "$SOURCE"
    assert_safe_destination "$DEST"

    mkdir -p "$DEST/assets"

    find "$DEST" \
        -mindepth 1 \
        -maxdepth 1 \
        ! -name "assets" \
        -exec rm -rf -- {} +

    LIST_FILE="$(mktemp "${DEPLOY_TMP:?}/deploy-web.XXXXXX")"

    if ! find "$SOURCE" \
        -mindepth 1 \
        -maxdepth 1 \
        -print0 > "$LIST_FILE"; then

        log "ERROR: Failed listing web source:"
        log "$SOURCE"
        return 1
    fi

    while IFS= read -r -d '' ITEM
    do
        NAME="$(basename "$ITEM")"

        if [ "$NAME" = "assets" ]; then
            continue
        fi

        cp -a -- "$ITEM" "$DEST/"

    done < "$LIST_FILE"

    rm -f -- "$LIST_FILE"
}


# =====================================================================
# PUBLIC MANIFEST
# =====================================================================

build_public_manifest()
{
    local SOURCE="$1"
    local MANIFEST="$2"
    local LIST_FILE
    local ITEM
    local NAME

    require_directory "$SOURCE"

    : > "$MANIFEST"

    LIST_FILE="$(mktemp "${DEPLOY_TMP:?}/public-source.XXXXXX")"

    if ! find "$SOURCE" \
        -mindepth 1 \
        -maxdepth 1 \
        -print0 > "$LIST_FILE"; then

        log "ERROR: Failed building public source list:"
        log "$SOURCE"
        return 1
    fi

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

    done < "$LIST_FILE"

    rm -f -- "$LIST_FILE"

    sort -u -o "$MANIFEST" "$MANIFEST"
}


# =====================================================================
# PARAMS-LOCAL PRESERVATION
# =====================================================================

preserve_params()
{
    local RELATIVE="$1"
    local SOURCE
    local DEST

    SOURCE="$APP/$RELATIVE"
    DEST="$DEPLOY_TMP/params/$(dirname "$RELATIVE")"

    if [ -f "$SOURCE" ]; then
        mkdir -p "$DEST"
        cp -a -- "$SOURCE" "$DEST/"
    fi
}


restore_params()
{
    local RELATIVE="$1"
    local TEMPLATE="$2"
    local SAVED

    SAVED="$DEPLOY_TMP/params/$RELATIVE"

    if [ -f "$SAVED" ]; then
        mkdir -p "$(dirname "$APP/$RELATIVE")"
        cp -a -- "$SAVED" "$APP/$RELATIVE"
        return 0
    fi

    if [ -f "$REPO/$TEMPLATE" ]; then
        mkdir -p "$(dirname "$APP/$RELATIVE")"
        cp -a -- "$REPO/$TEMPLATE" "$APP/$RELATIVE"
        return 0
    fi

    log "ERROR: Cannot restore required production configuration:"
    log "$RELATIVE"

    return 1
}


# =====================================================================
# HEALTH CHECK
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
    local ROLLBACK_PUBLIC_LIST

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
        rm -rf -- "${APP:?}/common"

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
    # console/runtime remains untouched.
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
    #
    # Production yii is intentionally not modified.
    # -----------------------------------------------------------------

    for FILE in \
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
    # REMOVE MANAGED PUBLIC FILES
    # -----------------------------------------------------------------

    if [ -f "$DEPLOY_TMP/public-union.manifest" ]; then

        while IFS= read -r NAME
        do
            [ -n "$NAME" ] || continue

            rm -rf -- "${PUBLIC:?}/${NAME:?}"

        done < "$DEPLOY_TMP/public-union.manifest"
    fi


    # -----------------------------------------------------------------
    # RESTORE MANAGED PUBLIC FILES
    # -----------------------------------------------------------------

    if [ -d "$ROLLBACK_DIR/public-managed" ]; then

        ROLLBACK_PUBLIC_LIST="${DEPLOY_TMP:?}/rollback-public.list"

        if ! find "$ROLLBACK_DIR/public-managed" \
            -mindepth 1 \
            -maxdepth 1 \
            -print0 > "$ROLLBACK_PUBLIC_LIST"; then

            log "ERROR: Failed listing rollback public files."
            ROLLBACK_OK=0

        else

            while IFS= read -r -d '' ITEM
            do
                NAME="$(basename "$ITEM")"

                if ! cp -a -- \
                    "$ITEM" \
                    "$PUBLIC/$NAME"; then

                    ROLLBACK_OK=0
                fi

            done < "$ROLLBACK_PUBLIC_LIST"
        fi
    fi


    # -----------------------------------------------------------------
    # RESTORE SPECIAL PUBLIC FILES
    # -----------------------------------------------------------------

    for ITEM in \
        index.php \
        .htaccess \
        assets \
        favicon.ico
    do
        rm -rf -- "${PUBLIC:?}/${ITEM:?}"

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
    # COMPOSER ROLLBACK
    # -----------------------------------------------------------------

    if [ -f "$APP/composer.lock" ]; then

        log "Restoring previous Composer dependencies..."

        if ! (
            cd "$APP"

            composer install \
                --prefer-dist \
                --no-dev \
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
    # VERIFY CONSOLE
    # -----------------------------------------------------------------

    if ! (
        cd "$APP"
        php yii help >/dev/null
    ); then

        log "ERROR: Yii console failed after rollback."
        ROLLBACK_OK=0
    fi


    # -----------------------------------------------------------------
    # VERIFY WEB APPLICATION
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
    log "ERROR: Invalid Git deployment repository:"
    log "$REPO"
    exit 1
fi

if [ ! -f "$APP/.env" ]; then
    log "ERROR: Production .env is missing:"
    log "$APP/.env"
    exit 1
fi

if [ ! -r "$APP/.env" ]; then
    log "ERROR: Production .env is unreadable:"
    log "$APP/.env"
    exit 1
fi

# ---------------------------------------------------------------------
# Production yii is deliberately not Git-managed.
# ---------------------------------------------------------------------

if [ ! -f "$APP/yii" ]; then
    log "ERROR: Production Yii console entry script is missing:"
    log "$APP/yii"
    exit 1
fi

if [ ! -r "$APP/yii" ]; then
    log "ERROR: Production Yii console entry script is unreadable:"
    log "$APP/yii"
    exit 1
fi

php -l "$APP/yii" >/dev/null


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

if git ls-files --error-unmatch yii >/dev/null 2>&1; then
    log "ERROR: Production Yii entry script must not be tracked by Git."
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

        log "ERROR: Production-only configuration is tracked:"
        log "$FILE"
        exit 1
    fi
done


# =====================================================================
# 4. VALIDATE RELEASE
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
# 5. PHP SYNTAX CHECK
#
# Uses an explicit temporary list instead of process substitution.
# =====================================================================

log "[5/15] Checking PHP syntax..."

PHP_SYNTAX_FAILED=0
PHP_LIST="${DEPLOY_TMP:?}/php-files.list"

if ! find \
    "$REPO/common" \
    "$REPO/frontend" \
    "$REPO/backend" \
    "$REPO/console" \
    -type f \
    -name "*.php" \
    ! -path "*/runtime/*" \
    ! -path "*/web/assets/*" \
    -print0 > "$PHP_LIST"; then

    log "ERROR: Failed to build PHP syntax-check file list."
    exit 1
fi

while IFS= read -r -d '' FILE
do
    if ! php -l "$FILE" >/dev/null; then
        log "ERROR: PHP syntax error:"
        log "$FILE"

        PHP_SYNTAX_FAILED=1
    fi

done < "$PHP_LIST"

if [ "$PHP_SYNTAX_FAILED" -ne 0 ]; then
    log "ERROR: Release contains PHP syntax errors."
    exit 1
fi


# =====================================================================
# 6. BUILD PUBLIC MANIFESTS
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
# 7. CREATE ROLLBACK SNAPSHOT
# =====================================================================

log "[7/15] Creating rollback snapshot..."

mkdir -p "$ROLLBACK_DIR/root"
mkdir -p "$ROLLBACK_DIR/public-managed"
mkdir -p "$ROLLBACK_DIR/public-special"

cp -a -- \
    "$APP/common" \
    "$ROLLBACK_DIR/common"


# Frontend: runtime and web/assets excluded.

snapshot_application \
    "$APP/frontend" \
    "$ROLLBACK_DIR/frontend"


# Backend: runtime and web/assets excluded.

snapshot_application \
    "$APP/backend" \
    "$ROLLBACK_DIR/backend"


# Console:
#
# controllers/config/models/migrations/etc are snapshotted.
# console/runtime is deliberately excluded.

snapshot_application \
    "$APP/console" \
    "$ROLLBACK_DIR/console"


# ---------------------------------------------------------------------
# ROOT FILES
#
# Production yii is intentionally not snapshotted because this deploy
# never changes it.
# ---------------------------------------------------------------------

for FILE in \
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
# 8. PRESERVE PRODUCTION CONFIGURATION
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


# From this point onward, failures trigger application rollback.

DEPLOY_STARTED=1


# =====================================================================
# 9. DEPLOY APPLICATION SOURCE
# =====================================================================

log "[9/15] Deploying application source..."


# ---------------------------------------------------------------------
# COMMON
# ---------------------------------------------------------------------

rm -rf -- "${APP:?}/common"

cp -a -- \
    "$REPO/common" \
    "$APP/common"


# ---------------------------------------------------------------------
# FRONTEND
#
# frontend/runtime preserved.
# frontend/web/assets preserved.
# ---------------------------------------------------------------------

deploy_application \
    "$REPO/frontend" \
    "$APP/frontend"

deploy_web \
    "$REPO/frontend/web" \
    "$APP/frontend/web"


# ---------------------------------------------------------------------
# BACKEND
#
# backend/runtime preserved.
# backend/web/assets preserved.
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
# Deploys:
#
#   controllers
#   migrations
#   config
#   models
#   services
#   components
#   modules
#   commands
#   etc.
#
# Preserves:
#
#   console/runtime
# ---------------------------------------------------------------------

deploy_application \
    "$REPO/console" \
    "$APP/console"

if [ -d "$REPO/console/web" ]; then

    deploy_web \
        "$REPO/console/web" \
        "$APP/console/web"
fi


# =====================================================================
# 10. RESTORE PRODUCTION CONFIGURATION
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

if [ ! -f "$APP/yii" ] ||
   [ ! -r "$APP/yii" ]; then

    log "ERROR: Production Yii entry script missing or unreadable."
    exit 1
fi

php -l "$APP/yii" >/dev/null


# =====================================================================
# 12. COMPOSER
# =====================================================================

log "[12/15] Installing dependencies..."

for FILE in \
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
    --no-dev \
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
# 14. PUBLIC ROOT + DATABASE MIGRATIONS
# =====================================================================

log "[14/15] Updating frontend public root..."


# ---------------------------------------------------------------------
# Remove only files managed by old/new frontend releases.
#
# Unrelated public_html content remains untouched.
# ---------------------------------------------------------------------

while IFS= read -r NAME
do
    [ -n "$NAME" ] || continue

    rm -rf -- "${PUBLIC:?}/${NAME:?}"

done < "$DEPLOY_TMP/public-union.manifest"


# ---------------------------------------------------------------------
# Copy new managed frontend files.
# ---------------------------------------------------------------------

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
    CURRENT_ASSET_LINK="$(readlink "$PUBLIC/assets" || true)"
fi

if [ ! -L "$PUBLIC/assets" ] ||
   [ "$CURRENT_ASSET_LINK" != "$EXPECTED_ASSET_LINK" ]; then

    rm -rf -- "${PUBLIC:?}/assets"

    ln -s \
        "$EXPECTED_ASSET_LINK" \
        "$PUBLIC/assets"
fi


# ---------------------------------------------------------------------
# FAVICON SYMLINK
# ---------------------------------------------------------------------

CURRENT_FAVICON_LINK=""

if [ -L "$PUBLIC/favicon.ico" ]; then
    CURRENT_FAVICON_LINK="$(readlink "$PUBLIC/favicon.ico" || true)"
fi

if [ ! -L "$PUBLIC/favicon.ico" ] ||
   [ "$CURRENT_FAVICON_LINK" != "$EXPECTED_FAVICON_LINK" ]; then

    rm -rf -- "${PUBLIC:?}/favicon.ico"

    ln -s \
        "$EXPECTED_FAVICON_LINK" \
        "$PUBLIC/favicon.ico"
fi


# ---------------------------------------------------------------------
# PRODUCTION FRONTEND ENTRY POINT
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
# DATABASE MIGRATIONS
#
# Flag is set BEFORE migration execution because some migrations may
# succeed before a subsequent migration fails.
#
# Database migrations are deliberately NOT automatically reversed by
# application rollback.
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
# 15. FINAL HEALTH CHECK
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

cd "$APP"

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