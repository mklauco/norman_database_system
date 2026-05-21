# NDS CI/CD Architecture — Reference

**Audience:** anyone auditing how code moves from a developer's laptop to production. Companion to:
- [DEPLOY.md](DEPLOY.md) — operational runbook (bootstrap, deploy, rollback, troubleshooting)
- [TESTING_AND_CICD_PLAN.md](TESTING_AND_CICD_PLAN.md) — original plan, kept for historical context. The implementation differs in places; trust this document over the plan.

This file is the single source of truth for what exists today.

---

## 1. Component inventory

### 1.1 Repository

- **GitHub:** `mklauco/norman_database_system` (public)
- **Branches:**
  - `main` — what is in production. Protected (recommended; not yet enforced — see §8).
  - `development` — integration branch. All feature work targets it.
  - `feature/*`, `fix/*`, `chore/*` — short-lived, deleted after merge.

### 1.2 GitHub Actions workflows

Three files in `.github/workflows/`:

| File | Trigger | Purpose | Run time |
|---|---|---|---|
| `ci.yml` | push or PR to `development` or `main` | Validates that the codebase builds | ~30–60 s |
| `deploy.yml` | push to `main` only (i.e. PR merge) | Deploys the new commit to production | ~30–60 s |
| `migrate.yml` | manual (`workflow_dispatch`) | Runs `php artisan migrate` on production | ~10–60 s |

**Concurrency controls:**
- `ci.yml` cancels in-progress runs on the same branch (newer commits supersede older).
- `deploy.yml` does NOT cancel in-progress (no overlapping deploys; the second waits).
- `migrate.yml` does NOT cancel in-progress (no overlapping migrations).

### 1.3 Production server

- **Host:** `145.223.117.219`
- **OS user:** `deployer` (uid 1002, GID `developers`/`deployer`)
- **App root:** `/opt/projects/norman_database_system/`
- **Docker stack:** 6 containers (5 app/queue/scheduler + 1 nginx)
  - `nds-app` — PHP-FPM (PHP 8.4)
  - `nds-app-schedule` — `php artisan schedule:work`
  - `nds-app-queue-default` — queue: `default,high`
  - `nds-app-queue-medium` — queue: `medium`
  - `nds-app-queue-exports` — queue: `exports` (long-running, large memory)
  - `nds-webserver` — nginx:alpine
- **External network:** `mkassets_docker_network` — shared with other projects on the host (incl. PostgreSQL DB)
- **Database:** PostgreSQL — runs in a separate container (NOT in this docker-compose stack), reached via `mkassets_docker_network`

### 1.4 Server filesystem layout

```
/opt/projects/norman_database_system/
├── docker-compose.yml          # Production compose (host-managed)
├── docker-compose.yml.bak      # Backup created during in-flight fix
├── current  →  releases/<latest>
├── releases/
│   ├── initial/                # The state from the bootstrap moment, never deleted
│   ├── 20260509_131402_8094255/   # auto-deploy
│   ├── 20260509_131823_07ce45a/   # auto-deploy
│   └── …                          # last 5 retained, older are pruned by deploy.yml
└── shared/
    ├── .env                    # Single source of truth for env. Symlinked into every release.
    └── storage/                # Laravel storage (logs, cache, sessions, uploads).
                                # Persists across deploys.
```

**Rules:**
- The host-level `docker-compose.yml` is NOT auto-synced from the repo. To update it, manually copy `current/docker-compose.production.yml` over it and run `docker compose up -d`.
- Every release directory contains a complete checkout of the repo + `vendor/` + `node_modules/` + `public/build/`, ready to serve.
- `releases/initial` is special: it was created by the bootstrap script from the pre-existing on-server checkout. Never delete it.
- The `.env` and `storage` symlinks inside each release point at absolute paths under `/opt/projects/norman_database_system/shared/`. The compose file mounts `./shared` into containers at the same absolute path so the symlinks resolve inside the container too.

### 1.5 Local docker-compose (dev only)

The repo also contains `docker-compose.yml` (mounts `./` into `/var/www`). That file is **for local development on a contributor's machine** and is not used in production.

---

## 2. Secrets inventory

Stored in **GitHub → Settings → Secrets and variables → Actions**:

| Secret | Used by | Origin |
|---|---|---|
| `DEPLOY_SSH_HOST` | `deploy.yml`, `migrate.yml` | Manually entered IP `145.223.117.219` |
| `DEPLOY_SSH_USER` | `deploy.yml`, `migrate.yml` | Manually entered `deployer` |
| `DEPLOY_SSH_KEY` | `deploy.yml`, `migrate.yml` | ed25519 private key generated on the server (`/home/deployer/.ssh/nds_deploy`) |
| `DEPLOY_KNOWN_HOSTS` | `deploy.yml`, `migrate.yml` | `ssh-keyscan` of the server, captured from a workstation outside the server |

Optional / not currently set:
- `DEPLOY_SSH_PORT` — defaults to 22 in the workflows.

The matching public key (`/home/deployer/.ssh/nds_deploy.pub`) is appended to the deploy user's `~/.ssh/authorized_keys` on the server.

**Rotation:** when rotating, generate a new keypair on the server, append the new public key to `authorized_keys`, update `DEPLOY_SSH_KEY` in GitHub, run a no-op deploy to verify, then remove the old public key from `authorized_keys`.

No SMTP secrets are configured. Failure notifications are delivered by GitHub's default mechanism (repo watchers receive emails per their account preferences).

---

## 3. Data flow — push to `development`

```
Developer pushes to development (or opens PR targeting it)
   │
   ▼
GitHub Actions: ci.yml fires
   │
   ├── Job: "Composer Install"   — composer validate + composer install
   └── Job: "Frontend Build"     — npm ci + npm run build
   │
   ▼
Both green → status checks pass on branch / PR
   │
   ▼
PR can be merged into development (no auto-deploy from development)
```

**Effect on production:** none. The `development` branch never deploys.

---

## 4. Data flow — merge to `main` (production deploy)

```
PR development → main is merged in GitHub UI
   │
   ▼
GitHub Actions: ci.yml fires (validates the merge commit)
GitHub Actions: deploy.yml fires (in parallel; doesn't wait for ci.yml)
   │
   ▼
deploy.yml: job "Deploy to production" runs on ubuntu-latest
   │
   ├── Step: Setup SSH
   │   – Writes DEPLOY_SSH_KEY to ~/.ssh/deploy_key (chmod 600)
   │   – Writes DEPLOY_KNOWN_HOSTS to ~/.ssh/known_hosts
   │
   └── Step: Run remote deploy (one SSH session, single bash heredoc)
       │
       ▼  (now on production server, as deployer)
       cd /opt/projects/norman_database_system
       │
       ├── Preflight checks
       │   – current/ symlink exists
       │   – shared/ exists
       │   – shared/.env exists
       │   – ≥ 500 MB free disk
       │
       ├── Clone the new release
       │   – RELEASE_NAME = <UTC timestamp>_<7-char SHA>
       │   – git clone --depth=1 --branch main <repo URL> releases/<RELEASE_NAME>/
       │   – Verifies cloned SHA matches expected
       │
       ├── Link shared resources into the new release
       │   – ln -sfn $PROJECT_ROOT/shared/.env releases/<NAME>/.env
       │   – rm -rf releases/<NAME>/storage
       │   – ln -sfn $PROJECT_ROOT/shared/storage releases/<NAME>/storage
       │
       ├── Install PHP deps in an ephemeral container
       │   – docker run --rm
       │       -v releases/<NAME>:/var/www
       │       -v shared:/opt/projects/norman_database_system/shared  ← critical, see §6.1
       │       --user $(id -u):$(id -g)
       │       laravel-app
       │       composer install --no-dev --optimize-autoloader
       │
       ├── Build frontend assets in an ephemeral container
       │   – Same docker run pattern, runs `npm ci && npm run build`
       │
       ├── Maintenance mode ON
       │   – docker exec nds-app php artisan down --render="errors::503" --retry=15
       │
       ├── ATOMIC SYMLINK SWAP
       │   – ln -sfn releases/<NAME> current
       │   – Reads previous target for the log
       │
       ├── Rebuild Laravel caches against new code
       │   – docker exec nds-app php artisan optimize:clear
       │   – docker exec nds-app php artisan optimize
       │
       ├── Graceful PHP-FPM reload
       │   – docker exec nds-app sh -c 'kill -USR2 1'
       │   – PHP-FPM master (PID 1) reloads workers gracefully
       │
       ├── Restart queue workers and scheduler
       │   – docker compose restart task-app-schedule task-app-queue-default
       │     task-app-queue-medium task-app-queue-exports
       │   – Forces them to load new code (they hold it in memory otherwise)
       │
       ├── Maintenance mode OFF
       │   – docker exec nds-app php artisan up
       │
       └── Prune old releases
           – Keeps last 5
           – Never deletes 'initial'
           – Never deletes the active release (compares canonical paths via readlink -f)
```

**User-visible impact:** ~5–15 seconds of HTTP 503 between maintenance-on and maintenance-off.

**Failure handling:** the workflow uses `set -euo pipefail` — any failed step aborts the deploy. If failure happens *before* the symlink swap, production is untouched (still serving from the previous release). If failure happens *after* the swap but before queue restart, the site is on new code but workers are stale — fix forward by re-running the workflow or rolling back per [DEPLOY.md](DEPLOY.md#rollback).

---

## 5. Data flow — manual migration

```
Operator goes to Actions → "Migrate Production DB" → "Run workflow"
   │
   ▼
Form requires:
   – confirm phrase: must be exactly "MIGRATE PRODUCTION"
   – pretend: boolean, default true (highly recommended for first run)
   │
   ▼
Job verifies confirm phrase, fails if it doesn't match
   │
   ▼
Job sets up SSH (same as deploy.yml)
   │
   ▼
Job SSHes to server, runs:
   – docker exec nds-app php artisan migrate:status   (always, for the log)
   – If pretend: docker exec nds-app php artisan migrate --pretend
   – Else:        docker exec nds-app php artisan migrate --force
                  + repeats migrate:status afterwards
```

**Recommended workflow:** deploy code first; that lands the migration files on the server. Then run this workflow with `pretend=true`, read the SQL in the action log, then re-run with `pretend=false`.

The migrate workflow does NOT auto-trigger on push or PR. Migrations are operator-initiated and audit-logged in GitHub Actions (you can see who clicked the button and when).

---

## 6. Critical implementation details (read these if auditing)

### 6.1 Shared volume mount inside containers

Every release has `.env` and `storage/` as symlinks pointing at absolute paths under `/opt/projects/norman_database_system/shared/`. For these symlinks to resolve **inside** containers, the compose file mounts `./shared:/opt/projects/norman_database_system/shared` alongside `./current:/var/www`. Without this second mount, Laravel boot fails with "no existing directory at /var/www/storage/logs". The same mount is added to every ephemeral `docker run` invocation in `deploy.yml`.

This was the root cause of an initial deploy failure during go-live. Fixed in commit `01d1b86`.

### 6.2 PHP-FPM reload via SIGUSR2

`docker exec nds-app sh -c 'kill -USR2 1'` works only because PHP-FPM is PID 1 in the `nds-app` container (Dockerfile's `CMD ["php-fpm"]`). If the entrypoint ever changes, replace this with the appropriate reload command. The fallback if SIGUSR2 fails: PHP-FPM's OPcache will revalidate file timestamps within a few seconds anyway, so worst case the new code becomes visible after a brief delay.

### 6.3 Atomic swap mechanism

`ln -sfn` replaces a symlink atomically (single rename(2) syscall under the hood). Containers' bind mounts of `./current` resolve the symlink at every file access (kernel-level path traversal), not once at container start. Therefore, the moment the symlink target changes, every subsequent file lookup inside the container sees the new release.

### 6.4 Prune safety

The prune step at the end of a deploy uses `readlink -f` to canonicalise both the active symlink target and each candidate for deletion, comparing absolute paths. This is robust to the symlink being created with relative or absolute targets.

### 6.5 Disk-space preflights

- `scripts/server-bootstrap.sh` requires ≥ 2 GB free on `/opt/projects/norman_database_system` (one-time, idempotent).
- `deploy.yml` requires ≥ 500 MB free per deploy.

If either fails, the operation aborts before any destructive action.

### 6.6 Test database safety guard

`tests/TestCase.php` has a `guardAgainstRealDatabase()` method called in `setUp()` BEFORE Laravel's `RefreshDatabase` trait runs. It refuses to allow tests to proceed unless the active connection is `sqlite :memory:` or the database name starts with `norman_test` or `ci_`. This prevents a recurrence of an earlier incident where `migrate:fresh` wiped the live `nds` database during a test.

`phpunit.xml` is a second wall: it forces `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` for the test environment regardless of what's in `.env`.

---

## 7. Audit trail — where to look for what

| Event | Where to look |
|---|---|
| Who pushed what to which branch | GitHub repo → Insights → Network / Commits, or `git log` on any clone |
| When did CI run, did it pass | GitHub → Actions → CI workflow → list of runs, each with logs |
| When did a deploy run, did it succeed | GitHub → Actions → Deploy workflow → list of runs, each with full SSH log |
| What commit is currently in production | On the server: `readlink /opt/projects/norman_database_system/current` (the trailing 7-char hex is the commit SHA) |
| What commits are in production history | On the server: `ls -1t /opt/projects/norman_database_system/releases/` shows last 5 + initial |
| Who triggered a migration, did it execute or pretend, what SQL ran | GitHub → Actions → Migrate Production DB → click the run → expand the SSH step |
| Production application errors | On the server: `docker exec nds-app tail -f /var/www/storage/logs/laravel.log` |
| Container health | On the server: `docker compose ps` (running 6 containers, healthy/unhealthy status) |
| Disk usage | On the server: `df -h /opt/projects/norman_database_system && du -sh releases/*` |

---

## 8. Known gaps and deferred items

These are explicit non-goals of the current implementation. Track separately if you want to add them.

1. **Branch protection rules** are not yet enforced in GitHub Settings. Currently anyone with push access can push directly to `main` (bypassing CI). Recommended: require PR + status checks + 1 CODEOWNERS approval on `main`.
2. **CODEOWNERS file** does not exist. Adding it would force review on critical paths (migrations, middleware, routes, deploy infrastructure).
3. **`deploy.sh`** still exists in the repo as a relic from the old manual-deploy process. Unused but should be deleted to avoid confusion.
4. **Test execution in CI** is not enabled. 33 of 45 existing tests fail on the SQLite phpunit config because of Postgres-specific migration SQL. Adding a Postgres-tests CI job is a separate task.
5. **Static analysis** (Larastan/PHPStan) is not installed.
6. **No staging environment.** First test of any deploy change is in production; mitigated by 5-second rollback.
7. **No automated DB backup before migrations.** The operator relies on an externally-scheduled nightly backup.
8. **Three queue worker containers report `(unhealthy)`** as a pre-existing condition. The healthcheck (`pgrep -f 'queue:work'`) intermittently fails to detect the worker process. Cosmetic on the surface; not blocking deploys, but should be diagnosed (real failure vs. flaky check).
9. **Build artifacts** (`vendor/`, `public/build/`) are produced on the production host during deploy. Long-term, building in CI and shipping artifacts is faster and isolates the production host from build-time resource spikes.
10. **PHP version pin in CI:** `ci.yml` uses PHP 8.4. `composer.json` allows `^8.2`. New devs running the site on 8.2 may install dependencies that won't run on 8.4 without warning. Pin once you decide on a single supported version.
11. **The deploy workflow swaps the `current` symlink but does NOT recreate the containers.** Docker resolves bind-mount sources at container-create time and binds the underlying directory by inode — so after a deploy, the running containers are still bound to the OLD release directory and serving stale code (including stale migration files; `php artisan migrate --pretend` will report "Nothing to migrate" until containers are recreated). `docker compose up -d` alone is a no-op because compose config hasn't changed; `restart` is also insufficient. The fix is `docker compose up -d --force-recreate` (a few seconds of downtime). Until added to the workflow, every deploy that ships code changes effectively reaches production only after a manual recreate.
12. **The deploy workflow does not clear the compiled Blade view cache.** Compiled views live in `shared/storage/framework/views/` and persist across releases. A deploy that bumps a third-party package version (e.g. Livewire) can leave cached `.php` files referencing classes that no longer exist, producing a 500 on the first page that hits a stale template. Workaround: `docker exec nds-app php artisan view:clear` post-deploy.
13. **The `nds-app` image only ships `pdo_pgsql`, not the procedural `pgsql` extension.** Any code that needs PostgreSQL's COPY protocol (bulk-load seeders, custom dump pipelines) hits `Call to undefined function pg_connect()`. Workaround: install at runtime with `apt-get install -y libpq-dev && docker-php-ext-install pgsql && docker-php-ext-enable pgsql` — ephemeral, wiped by `--force-recreate`. Should be added to the Dockerfile.
14. **`/dev/shm` on the `nds-postgres` container is the Docker default of 64 MB.** Operations that ask PostgreSQL to allocate large shared-memory segments (e.g. parallel `VACUUM ANALYZE` on a large table — wants ~1 GB) fail with a misleading "Disk full" error. Either bump `shm_size` on the postgres service in `docker-compose.production.yml` or have heavy callers explicitly disable parallel workers (`VACUUM (ANALYZE, PARALLEL 0)`).

---

## 9. How to verify the pipeline end-to-end (audit procedure)

To confirm the pipeline works as documented, run this from a developer machine:

1. On `development`, make a trivial change (whitespace in a non-critical file). Commit, push.
2. Verify `ci.yml` runs and passes within ~60 s.
3. Open a PR `development → main`. Verify CI passes on the PR.
4. Merge the PR.
5. Watch `deploy.yml` in the Actions tab. Should complete green within ~60 s.
6. SSH to server: `readlink /opt/projects/norman_database_system/current` should now point at a release dir whose name ends with the new commit's first 7 chars.
7. Verify https://norman-databases.org/login serves the site (browser, not curl — Cloudflare's bot rules block bare curl).

If any step diverges, the pipeline has drifted from this document.

---

## 10. References

- [DEPLOY.md](DEPLOY.md) — operational runbook
- [.github/workflows/ci.yml](../.github/workflows/ci.yml)
- [.github/workflows/deploy.yml](../.github/workflows/deploy.yml)
- [.github/workflows/migrate.yml](../.github/workflows/migrate.yml)
- [docker-compose.production.yml](../docker-compose.production.yml)
- [scripts/server-bootstrap.sh](../scripts/server-bootstrap.sh)
- [tests/TestCase.php](../tests/TestCase.php) — test DB safety guard
