#!/usr/bin/env bash
#
# One-time server bootstrap for atomic deploys.
#
# Run this ONCE on the server, during a maintenance window, after merging the
# atomic-deploy infrastructure to main and pulling it onto the server.
#
# What it does:
#   1. Stops the running Docker stack
#   2. Restructures /opt/projects/norman_database_system into:
#        ├── docker-compose.yml         (production compose, host-managed)
#        ├── current  ->  releases/initial
#        ├── releases/initial/          (the existing checkout, moved here)
#        └── shared/
#            ├── .env                   (your existing .env, moved here)
#            └── storage/               (your existing storage/, moved here)
#   3. Symlinks shared/.env and shared/storage into releases/initial
#   4. Installs the production docker-compose.yml at the host level
#   5. Restarts the stack pointing at the new layout
#
# After this runs, future deploys land in releases/<timestamp>/ and the
# `current` symlink swaps atomically. Containers never restart for code
# changes (only for queue worker reloads).
#
# Run as: the same user that currently owns /opt/projects/norman_database_system.
# That user must be able to run docker without sudo (be in the docker group).

set -euo pipefail

# ----- config ---------------------------------------------------------------

readonly PROJECT_ROOT="/opt/projects/norman_database_system"
readonly INITIAL_RELEASE="releases/initial"
readonly REPO_COMPOSE_FILE="docker-compose.production.yml"
readonly TARGET_COMPOSE_FILE="docker-compose.yml"

# ----- helpers --------------------------------------------------------------

log()   { printf '\033[1;36m[bootstrap]\033[0m %s\n' "$*"; }
warn()  { printf '\033[1;33m[bootstrap]\033[0m %s\n' "$*"; }
fail()  { printf '\033[1;31m[bootstrap]\033[0m %s\n' "$*" >&2; exit 1; }

confirm() {
    read -r -p "$(printf '\033[1;33m[bootstrap]\033[0m %s [y/N] ' "$1")" reply
    [[ "$reply" =~ ^[Yy]$ ]] || fail "Aborted by user."
}

# ----- preflight ------------------------------------------------------------

[[ -d "$PROJECT_ROOT" ]] || fail "Expected $PROJECT_ROOT to exist."
cd "$PROJECT_ROOT"

[[ -f "$TARGET_COMPOSE_FILE" ]] || fail "No $TARGET_COMPOSE_FILE found at $PROJECT_ROOT — wrong directory?"
[[ -f ".env" ]] || fail "No .env in $PROJECT_ROOT — refusing to bootstrap without a working environment."

if [[ -L "current" ]]; then
    fail "current/ symlink already exists. Bootstrap has already been run."
fi

if [[ -d "releases" ]]; then
    fail "releases/ directory already exists. Bootstrap has already been run."
fi

if ! command -v docker >/dev/null 2>&1; then
    fail "docker not found in PATH."
fi

if ! docker info >/dev/null 2>&1; then
    fail "docker daemon not reachable. Are you in the docker group?"
fi

# Verify the new compose file exists in the repo (we'll need it after the move)
if [[ ! -f "$REPO_COMPOSE_FILE" ]]; then
    fail "$REPO_COMPOSE_FILE not found. Pull the latest main before bootstrapping."
fi

log "Preflight checks passed."
log "About to restructure $PROJECT_ROOT for atomic deploys."
log "This will stop containers (~1–2 min downtime), then restart them."
confirm "Continue?"

# ----- 1. stop the stack ----------------------------------------------------

log "Stopping running stack…"
docker compose down

# ----- 2. stage the new compose file outside the move path ------------------

log "Staging production compose file in /tmp…"
cp "$REPO_COMPOSE_FILE" /tmp/nds-docker-compose.yml

# ----- 3. create new layout -------------------------------------------------

log "Creating releases/ and shared/ directories…"
mkdir -p releases shared

# ----- 4. move .env and storage into shared/ --------------------------------

log "Moving .env into shared/…"
mv .env shared/.env

log "Moving storage/ into shared/…"
if [[ -d storage ]]; then
    mv storage shared/storage
else
    warn "No storage/ directory found — creating an empty one in shared/."
    mkdir -p shared/storage/{app,framework,logs}
    mkdir -p shared/storage/framework/{cache,sessions,testing,views}
fi

# ----- 5. move everything else into releases/initial ------------------------

log "Moving current code into $INITIAL_RELEASE/…"
mkdir -p "$INITIAL_RELEASE"

# Move every file/dir in $PROJECT_ROOT except: releases/, shared/, the staged
# compose file (which doesn't exist here yet), and dotfiles we want to keep at
# the host level (none).
shopt -s dotglob nullglob
for item in *; do
    case "$item" in
        releases|shared) continue ;;
        *) mv "$item" "$INITIAL_RELEASE/" ;;
    esac
done
shopt -u dotglob nullglob

# ----- 6. install the host-level docker-compose.yml -------------------------

log "Installing production docker-compose.yml at host level…"
mv /tmp/nds-docker-compose.yml "$TARGET_COMPOSE_FILE"

# ----- 7. symlink shared resources into the initial release -----------------

log "Symlinking shared/.env into $INITIAL_RELEASE/…"
ln -s "$PROJECT_ROOT/shared/.env" "$INITIAL_RELEASE/.env"

log "Symlinking shared/storage into $INITIAL_RELEASE/…"
rm -rf "$INITIAL_RELEASE/storage"
ln -s "$PROJECT_ROOT/shared/storage" "$INITIAL_RELEASE/storage"

# ----- 8. create the atomic 'current' symlink -------------------------------

log "Creating current -> $INITIAL_RELEASE symlink…"
ln -sfn "$INITIAL_RELEASE" current

# ----- 9. bring the stack back up -------------------------------------------

log "Starting the stack against the new layout…"
docker compose up -d

log "Waiting 10 seconds for containers to settle…"
sleep 10

docker compose ps

log "Bootstrap complete."
log "Layout:"
ls -la "$PROJECT_ROOT"
log ""
log "Verify the site is up, then you can trigger automated deploys by merging to main."
log "Rollback (if needed): see docs/DEPLOY.md."
