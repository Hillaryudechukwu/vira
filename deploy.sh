#!/usr/bin/env bash

# VIRA Laravel application deployment script
# Deploys the complete app, including its legal pages, to vira.synteric.co.uk.

set -Eeuo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
SERVER_USER="${SERVER_USER:-tripuwtd}"
SERVER_HOST="${SERVER_HOST:-vira.synteric.co.uk}"
SERVER_PATH="${SERVER_PATH:-/home/tripuwtd/vira.synteric.co.uk}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_ed25519}"
SSH_PORT="${SSH_PORT:-21098}"
PUBLIC_URL="${PUBLIC_URL:-https://vira.synteric.co.uk}"
REMOTE_PHP="${REMOTE_PHP:-php}"
REMOTE_COMPOSER="${REMOTE_COMPOSER:-composer}"
DRY_RUN="${DRY_RUN:-false}"
FRESH_DATABASE="${FRESH_DATABASE:-false}"

SSH_OPTIONS=(
    -i "$SSH_KEY"
    -p "$SSH_PORT"
    -o BatchMode=yes
    -o StrictHostKeyChecking=accept-new
)

on_error() {
    printf "\n%bDeployment failed on line %s.%b\n" "$RED" "$1" "$NC" >&2
}
trap 'on_error $LINENO' ERR

printf "%bStarting VIRA application deployment...%b\n\n" "$GREEN" "$NC"
printf "%bConfiguration:%b\n" "$YELLOW" "$NC"
printf "Source:       %s\n" "$SCRIPT_DIR"
printf "Server:       %s@%s:%s\n" "$SERVER_USER" "$SERVER_HOST" "$SSH_PORT"
printf "Application:  %s\n" "$SERVER_PATH"
printf "Document root:%s/public\n" "$SERVER_PATH"
printf "URL:          %s\n" "$PUBLIC_URL"
printf "SSH key:      %s\n" "$SSH_KEY"
printf "Dry run:      %s\n\n" "$DRY_RUN"
if [[ "$FRESH_DATABASE" == "true" ]]; then
    printf "%bDatabase:     DROP ALL TABLES and migrate from scratch%b\n\n" "$RED" "$NC"
fi

for command in ssh rsync; do
    command -v "$command" >/dev/null || {
        printf "%b%s is required.%b\n" "$RED" "$command" "$NC" >&2
        exit 1
    }
done

for required_file in artisan composer.json composer.lock .htaccess public/index.php public/.htaccess resources/legal-site/index.html; do
    if [[ ! -f "$SCRIPT_DIR/$required_file" ]]; then
        printf "%bRequired application file is missing: %s%b\n" "$RED" "$required_file" "$NC" >&2
        exit 1
    fi
done

if [[ ! -f "$SSH_KEY" ]]; then
    printf "%bSSH key does not exist: %s%b\n" "$RED" "$SSH_KEY" "$NC" >&2
    exit 1
fi

if [[ "$DRY_RUN" != "true" ]]; then
    if [[ "$FRESH_DATABASE" == "true" ]]; then
        read -r -p "FRESH_DATABASE will erase every table in the configured database. Type ERASE to continue: " destructive_reply
        if [[ "$destructive_reply" != "ERASE" ]]; then
            printf "%bDestructive migration cancelled.%b\n" "$RED" "$NC"
            exit 1
        fi
    fi
    read -r -p "Deploy the complete VIRA application to $SERVER_HOST? (y/N) " reply
    if [[ ! "$reply" =~ ^[Yy]$ ]]; then
        printf "%bDeployment cancelled.%b\n" "$RED" "$NC"
        exit 1
    fi
fi

printf "%bChecking SSH and the application directory...%b\n" "$YELLOW" "$NC"
if [[ "$DRY_RUN" == "true" ]]; then
    ssh "${SSH_OPTIONS[@]}" "$SERVER_USER@$SERVER_HOST" "test -d '$SERVER_PATH'"
else
    ssh "${SSH_OPTIONS[@]}" "$SERVER_USER@$SERVER_HOST" "mkdir -p -- '$SERVER_PATH'"
fi

REMOTE_HAS_COMPOSER=false
if ssh "${SSH_OPTIONS[@]}" "$SERVER_USER@$SERVER_HOST" "command -v '$REMOTE_COMPOSER' >/dev/null 2>&1"; then
    REMOTE_HAS_COMPOSER=true
    printf "Remote Composer detected; dependencies will be installed on the server.\n"
else
    printf "%bRemote Composer is unavailable; the local vendor directory will be uploaded.%b\n" "$YELLOW" "$NC"
    if [[ ! -f "$SCRIPT_DIR/vendor/autoload.php" ]]; then
        printf "%bRemote Composer is unavailable and local dependencies are missing.%b\n" "$RED" "$NC" >&2
        printf "Run 'composer install' locally, then deploy again.\n" >&2
        exit 1
    fi
fi

RSYNC_OPTIONS=(
    --archive
    --compress
    --checksum
    --human-readable
    --itemize-changes
    --exclude=.git/
    --exclude=.env
    --exclude=.phpunit.result.cache
    --exclude=node_modules/
    --exclude=storage/logs/
    --exclude=storage/framework/cache/
    --exclude=storage/framework/sessions/
    --exclude=storage/framework/views/
    --exclude=.DS_Store
)

if [[ "$REMOTE_HAS_COMPOSER" == "true" ]]; then
    RSYNC_OPTIONS+=(--exclude=vendor/)
fi

if [[ "$DRY_RUN" == "true" ]]; then
    RSYNC_OPTIONS+=(--dry-run)
fi

printf "%bSynchronising application files...%b\n" "$YELLOW" "$NC"
rsync "${RSYNC_OPTIONS[@]}" \
    -e "ssh -i $SSH_KEY -p $SSH_PORT -o BatchMode=yes -o StrictHostKeyChecking=accept-new" \
    "$SCRIPT_DIR/" \
    "$SERVER_USER@$SERVER_HOST:$SERVER_PATH/"

if [[ "$DRY_RUN" == "true" ]]; then
    printf "\n%bDry run complete; no application files were changed.%b\n" "$GREEN" "$NC"
    exit 0
fi

printf "%bInstalling dependencies and releasing the application...%b\n" "$YELLOW" "$NC"
ssh "${SSH_OPTIONS[@]}" "$SERVER_USER@$SERVER_HOST" \
    "bash -s -- '$SERVER_PATH' '$REMOTE_PHP' '$REMOTE_COMPOSER' '$FRESH_DATABASE'" <<'REMOTE_SCRIPT'
set -Eeuo pipefail

app_path="$1"
php_bin="$2"
composer_bin="$3"
fresh_database="$4"

cd "$app_path"

if [[ ! -f .env ]]; then
    echo "Missing $app_path/.env; create the production environment file before deploying." >&2
    exit 1
fi

mkdir -p resources/views public/media storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX public/media storage bootstrap/cache

if command -v "$composer_bin" >/dev/null 2>&1; then
    "$composer_bin" install --no-dev --classmap-authoritative --no-interaction --no-progress --prefer-dist
elif [[ -f vendor/autoload.php ]]; then
    echo "Composer is unavailable; using the vendor dependencies uploaded by the deployment client."
else
    echo "Composer is unavailable and vendor/autoload.php was not uploaded." >&2
    exit 1
fi

db_connection=$(sed -n 's/^[[:space:]]*DB_CONNECTION[[:space:]]*=[[:space:]]*//p' .env | tail -n 1 | tr -d "'\"\r")
if [[ -z "$db_connection" ]]; then
    echo "DB_CONNECTION is missing from $app_path/.env." >&2
    exit 1
fi

case "$db_connection" in
    pgsql|mysql|sqlite|sqlsrv) pdo_driver="$db_connection" ;;
    *)
        echo "Cannot verify PDO support for DB_CONNECTION=$db_connection." >&2
        exit 1
        ;;
esac

available_drivers=$("$php_bin" -r 'echo implode(", ", PDO::getAvailableDrivers());')
if ! "$php_bin" -r 'exit(in_array($argv[1], PDO::getAvailableDrivers(), true) ? 0 : 1);' "$pdo_driver"; then
    echo "PHP database driver '$pdo_driver' is unavailable." >&2
    echo "Available PDO drivers: ${available_drivers:-none}" >&2
    echo "Install/enable pdo_$pdo_driver, select a PHP CLI binary with that extension via REMOTE_PHP, or configure .env for a supported database." >&2
    exit 1
fi

echo "Database preflight passed: DB_CONNECTION=$db_connection; PDO drivers=$available_drivers"

read_env_value() {
    local key="$1"
    local fallback="$2"
    local value
    value=$(sed -n "s/^[[:space:]]*${key}[[:space:]]*=[[:space:]]*//p" .env | tail -n 1 | tr -d "'\"\r")
    printf '%s' "${value:-$fallback}"
}

cache_store=$(read_env_value CACHE_STORE database)
queue_connection=$(read_env_value QUEUE_CONNECTION database)
session_driver=$(read_env_value SESSION_DRIVER database)

if [[ "$cache_store" == "redis" || "$queue_connection" == "redis" || "$session_driver" == "redis" ]]; then
    if ! "$php_bin" -r 'exit(extension_loaded("redis") ? 0 : 1);'; then
        echo "Redis is configured but the remote PHP CLI has no Redis extension." >&2
        echo "Set CACHE_STORE=database, QUEUE_CONNECTION=database, and SESSION_DRIVER=database in $app_path/.env." >&2
        exit 1
    fi
fi

echo "Runtime preflight passed: cache=$cache_store; queue=$queue_connection; session=$session_driver"

# Remove generated bootstrap caches directly before invoking Artisan. This is
# intentionally filesystem-only so a stale Redis-backed config cannot prevent
# Laravel from reading the current production .env.
find bootstrap/cache -maxdepth 1 -type f -name '*.php' -delete

"$php_bin" artisan down --retry=30 || true
bring_up() { "$php_bin" artisan up >/dev/null 2>&1 || true; }
trap bring_up EXIT

if [[ "$fresh_database" == "true" ]]; then
    "$php_bin" artisan migrate:fresh --force
else
    "$php_bin" artisan migrate --force
fi
"$php_bin" artisan optimize:clear
"$php_bin" artisan config:cache
"$php_bin" artisan event:cache
"$php_bin" artisan route:cache
"$php_bin" artisan view:cache
"$php_bin" artisan queue:restart

bring_up
trap - EXIT
REMOTE_SCRIPT

printf "%bChecking deployed application endpoints...%b\n" "$YELLOW" "$NC"
if command -v curl >/dev/null; then
    for page in / /privacy-policy /terms /data-deletion /api/v1/health; do
        curl --fail --silent --show-error --location --max-time 20 \
            --output /dev/null "${PUBLIC_URL%/}$page"
        printf "Verified %s%s\n" "${PUBLIC_URL%/}" "$page"
    done
else
    printf "%bcurl is unavailable; skipping HTTP verification.%b\n" "$YELLOW" "$NC"
fi

printf "\n%bVIRA deployment complete: %s/%b\n" "$GREEN" "${PUBLIC_URL%/}" "$NC"
printf "%bRecommended document root: %s/public. A root .htaccess fallback is active for shared hosting.%b\n" "$YELLOW" "$SERVER_PATH" "$NC"
