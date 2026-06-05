# NORMAN Database System — Deployment Runbook

**Audience:** Martin (release manager) and any developer who needs to understand how code reaches production.

This system uses **atomic deploys** via a `current` symlink that gets swapped to point at a fresh release directory. Containers stay running across deploys; only the code they read from disk changes. Rollback is a 5-second symlink swap.

## Server layout

```
/opt/projects/norman_database_system/
├── docker-compose.yml          # Production compose, edited on the server only
├── current  ->  releases/<latest>
├── releases/
│   ├── 20260512_141200_a1b2c3d/   # Each deploy creates one of these
│   ├── 20260510_093000_d4e5f6a/
│   ├── …                          # Last 5 are kept; older are pruned
│   └── initial/                   # The original code from before bootstrap (kept forever)
└── shared/
    ├── .env                    # Single source of truth for env. Symlinked into every release.
    └── storage/                # Uploads, logs, sessions, framework cache. Persists across deploys.
```

The repo's `docker-compose.production.yml` is the source for the host-level `docker-compose.yml`. They are not auto-synced — see [Updating the production compose file](#updating-the-production-compose-file).

## Branching & deploy flow

```
feature/*  ──PR──▶  development  ──PR(release)──▶  main  ──auto──▶  production
   (devs)            (CI must pass)              (Martin approves)    (deploy.yml)
```

- Devs branch off `development`, PR back to `development`. CI must pass.
- Martin merges `development → main` via PR when ready to release.
- Push to `main` triggers `.github/workflows/deploy.yml` automatically.
- **Migrations do not run automatically.** Trigger them manually after a deploy via `.github/workflows/migrate.yml`.

## Required GitHub Secrets

Set in **Settings → Secrets and variables → Actions**:

| Secret | Value |
|---|---|
| `DEPLOY_SSH_HOST` | Server **IPv4 address** — currently `145.223.117.219`. Use the IP literal, **not** a hostname. See [Deploy times out at the SSH step (IPv6 pitfall)](#deploy-times-out-at-the-ssh-step-ipv6-pitfall). |
| `DEPLOY_SSH_USER` | SSH user that owns `/opt/projects/norman_database_system` and is in the `docker` group |
| `DEPLOY_SSH_KEY` | Private SSH key (full PEM contents). Generate fresh on the server: `ssh-keygen -t ed25519 -f ~/.ssh/nds_deploy -C github-actions-deploy -N ""`. Paste the **private** key here. Append the **public** key (`~/.ssh/nds_deploy.pub`) to the deploy user's `~/.ssh/authorized_keys` on the server. |
| `DEPLOY_KNOWN_HOSTS` | Output of `ssh-keyscan -4 <host>` from your laptop — pins the server's host key. Must be keyscanned against the **same IPv4 value** as `DEPLOY_SSH_HOST`, or `StrictHostKeyChecking=yes` will reject the connection. |

Optional:

| Secret | Value |
|---|---|
| `DEPLOY_SSH_PORT` | SSH port if not 22 |

To populate `DEPLOY_KNOWN_HOSTS`:

```bash
ssh-keyscan -4 145.223.117.219 2>/dev/null
```

Paste the three key lines (`ssh-ed25519`, `ssh-rsa`, `ecdsa-…`) into the secret; the `#`-comment lines are ignored. The `-4` flag matters — see the [IPv6 pitfall](#deploy-times-out-at-the-ssh-step-ipv6-pitfall) below.

Notification emails on workflow failure are handled by GitHub itself — repo watchers receive them automatically based on their account notification settings. To customise, see your GitHub account → Settings → Notifications.

## One-time bootstrap (Martin, weekend window)

The bootstrap script restructures the server from a flat checkout into the `current/releases/shared/` layout. It causes ~1–2 minutes of downtime and is idempotent-safe (it refuses to run if the layout is already in place).

**Pre-conditions on the server:**

1. SSH in as the deploy user.
2. `cd /opt/projects/norman_database_system`
3. Pull the latest `main` so `docker-compose.production.yml` and `scripts/server-bootstrap.sh` are present:
   ```bash
   git pull origin main
   ```
4. Verify the SMTP/SSH secrets above are configured in GitHub.

**Run the bootstrap:**

```bash
./scripts/server-bootstrap.sh
```

The script will:

1. Stop the running stack (`docker compose down`).
2. Create `releases/` and `shared/`.
3. Move `.env` and `storage/` into `shared/`.
4. Move all other code into `releases/initial/`.
5. Symlink `shared/.env` and `shared/storage` back into `releases/initial/`.
6. Install `docker-compose.production.yml` as the host-level `docker-compose.yml`.
7. Create the `current → releases/initial` symlink.
8. Bring the stack back up.

**After bootstrap, verify:**

```bash
ls -la /opt/projects/norman_database_system
# Should show: current -> releases/initial, releases/, shared/, docker-compose.yml

docker compose ps
# All containers Up

# Rebuild the public/storage symlink so uploads/exports remain reachable
docker exec nds-app php artisan storage:link --force
docker exec nds-app ls -la public/storage

curl -I https://nds.mkassets.sk
# 200 OK (or 503 only if you forgot to disable maintenance)
```

### If bootstrap fails partway through

The bootstrap script does multiple `mv` operations and a symlink swap. If it dies mid-way, the layout is half-restructured. To restore the pre-bootstrap state:

```bash
cd /opt/projects/norman_database_system
docker compose down

# Use rsync (preserves dotfiles, ownership, and symlinks) to copy everything back
rsync -a releases/initial/ ./
mv shared/.env .
rm -rf storage && mv shared/storage ./storage

# Remove the new layout
rm -rf releases shared current

# The original docker-compose.yml is now back at the project root (it was inside releases/initial/).
# Bring the old stack back up.
docker compose up -d
```

`rsync -a` is used (not `mv releases/initial/* .`) because `*` does not match dotfiles like `.gitignore`, `.dockerignore`, `.editorconfig`, `.github/`. Using `mv *` would silently leave those behind.

## Normal deploy (zero-downtime)

1. Merge a PR `development → main`.
2. GitHub Actions kicks off **Deploy → Deploy to production**. Watch it in the Actions tab.
3. The workflow:
   - SSHes to the server
   - Clones the repo into `releases/<timestamp>_<sha>/`
   - Symlinks `shared/.env` and `shared/storage` into the new release
   - Runs `composer install --no-dev` in an ephemeral container against the new release
   - Runs `npm ci && npm run build` in an ephemeral container
   - Puts the app into maintenance mode (`artisan down`)
   - Atomically swaps `current → releases/<new>`
   - Rebuilds Laravel caches (`artisan optimize`)
   - Sends `SIGUSR2` to PHP-FPM so it picks up the new code immediately
   - Restarts queue workers and the scheduler so they load the new code
   - Lifts maintenance mode
   - Prunes `releases/` to the last 5 (never touches `initial`)
4. You receive an email on success or failure.

**The deploy never runs migrations.** If the merged PR includes new migrations, run them via the migrate workflow (next section).

## Running migrations

Migrations are manual and gated behind a confirmation phrase.

1. Deploy the code first (above). The new release contains the migration files but the schema is unchanged.
2. Go to **Actions → Migrate Production DB → Run workflow**.
3. Fill in:
   - **confirm**: type `MIGRATE PRODUCTION` exactly
   - **pretend**: leave **true** the first time (shows SQL without running it). Run again with **false** once the SQL looks right.
4. Read the action log carefully. The pretend output shows exactly what would happen.
5. Re-run with **pretend = false** to actually execute.

**Never run migrations directly with `docker exec` on the server unless the GitHub Actions runner is unavailable.** The workflow exists so every migration is logged, attributed to a GitHub user, and emailed.

## Rollback

If a deploy breaks production:

```bash
ssh <deploy-user>@<server>
cd /opt/projects/norman_database_system

# 1. See what's available
ls -1t releases/

# 2. Repoint current to the previous good release
ln -sfn releases/<previous-good-name> current

# 3. Rebuild caches and reload
docker exec nds-app php artisan optimize:clear
docker exec nds-app php artisan optimize
docker exec nds-app sh -c 'kill -USR2 1'

# 4. Restart workers (they are still running old-new code)
docker compose restart task-app-schedule task-app-queue-default task-app-queue-medium task-app-queue-exports

# 5. Verify
curl -I https://nds.mkassets.sk
```

Total time: under a minute. **Rollback does NOT undo migrations.** If the failed deploy ran migrations that broke things, you'll need to write a corrective migration or `migrate:rollback` manually — there is no automated DB rollback.

## Updating the production compose file

The repo's `docker-compose.production.yml` is the canonical source. The server has a copy at `/opt/projects/norman_database_system/docker-compose.yml`. They're not auto-synced — only the bootstrap installs the file.

When you change `docker-compose.production.yml` (rare — maybe to bump an image, add a service, or change resource limits):

1. Merge the change to `main` as normal.
2. SSH to the server.
3. `cd /opt/projects/norman_database_system`
4. Diff to confirm: `diff docker-compose.yml current/docker-compose.production.yml`
5. Copy: `cp current/docker-compose.production.yml docker-compose.yml`
6. Apply: `docker compose up -d` (this only restarts containers whose definition actually changed)

If image rebuilds are needed (Dockerfile changed): `docker compose build && docker compose up -d`.

## Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Deploy fails at `composer install` | Composer can't resolve deps; a transitive dep changed | Check the cloned `composer.lock` matches the dev one; re-run |
| Deploy fails at `npm run build` | Node dep mismatch or build error | Reproduce locally with `npm ci && npm run build`; fix in a new PR |
| `current/` points to a release dir that doesn't exist | Manual mistake | `ln -sfn releases/<known-good> current`, then `artisan optimize` |
| Site returns 503 after deploy | Maintenance mode wasn't lifted | `docker exec nds-app php artisan up` |
| Queue jobs running old code | Workers weren't restarted | `docker compose restart task-app-queue-default task-app-queue-medium task-app-queue-exports` |
| `php artisan` complains about cached config after edit | Stale cache | `docker exec nds-app php artisan optimize:clear && docker exec nds-app php artisan optimize` |
| GitHub Actions can't SSH | Wrong key, wrong host, or `authorized_keys` missing the public key | Test from a workstation: `ssh -i ~/.ssh/nds_deploy <user>@<host> echo OK` |
| Deploy fails at **Run remote deploy** with `ssh: connect to host *** port 22: Connection timed out` | The runner is reaching the server over **IPv6**, which the server doesn't serve. See [IPv6 pitfall](#deploy-times-out-at-the-ssh-step-ipv6-pitfall). | Set `DEPLOY_SSH_HOST` to the **IPv4 literal** and regenerate `DEPLOY_KNOWN_HOSTS` with `ssh-keyscan -4`. |
| Deploy connects but fails with `Host key verification failed` | `DEPLOY_KNOWN_HOSTS` doesn't match the value in `DEPLOY_SSH_HOST` (e.g. you changed the host but not the known-hosts) | Regenerate: `ssh-keyscan -4 <DEPLOY_SSH_HOST>` and paste into `DEPLOY_KNOWN_HOSTS`. |
| `docker exec nds-app` says container not found | Container died | `docker compose ps` to inspect, `docker compose up -d` to revive |

### Deploy times out at the SSH step (IPv6 pitfall)

**Symptom.** Deploys that worked for weeks suddenly fail — every run dies at the **Run remote deploy** step, before any remote command executes:

```
ssh: connect to host *** port 22: Connection timed out
##[error]Process completed with exit code 255.
```

CI is green, the server is up, you can SSH in from your laptop, and nothing on the server changed. Only the GitHub Actions deploy can't connect.

**Root cause (2026-06-04 incident).** The deploy host became **dual-stack in DNS** — it resolved to both an IPv4 (`A`) and an IPv6 (`AAAA`, `2a02:4780:41:b417::1`) address. GitHub's hosted runners are dual-stack and **prefer IPv6** when an `AAAA` record exists. They tried to reach the server over IPv6, but the server's IPv6 is misconfigured and **not reachable from the outside** (it had a bogus `/48` host netmask and a dead v6 gateway), so the SYN was black-holed and the connection timed out. IPv4 to the server worked the whole time — which is why every other client (laptops, the office) was unaffected.

The host firewall (UFW), `fail2ban`, and `sshd` were all fine and **not** the cause. `sshd` was listening, UFW allowed port 22 and logged no drops for it, and `fail2ban` never banned a GitHub IP. The packets simply never arrived over the broken IPv6 path.

**Why the first "force IPv4" attempt didn't fix it.** Adding `ssh -4` to `deploy.yml` was the right instinct, but it didn't help on its own: `ssh -4` asks DNS only for the `A` record, yet `DEPLOY_SSH_HOST` itself was pointing at an IPv6-resolving value, so there was no IPv4 address for `-4` to use. Forcing IPv4 only works if the **host value you hand SSH can actually resolve to IPv4.**

**The fix that worked.**

1. 🌐 GitHub → **Settings → Secrets and variables → Actions** → set `DEPLOY_SSH_HOST` to the **IPv4 literal** (`145.223.117.219`). This sidesteps DNS and dual-stack entirely.
2. 🖥️ On your laptop: `ssh-keyscan -4 145.223.117.219` → paste the three key lines into `DEPLOY_KNOWN_HOSTS` (it must match the new host value, or `StrictHostKeyChecking=yes` rejects the connection — see the `Host key verification failed` troubleshooting row).
3. 🌐 GitHub → **Actions → Deploy →** open the failed run → **Re-run jobs → Re-run failed jobs** (secrets are re-read at run time, so no new commit is needed). It connects in well under a second.

**How to confirm this is the failure you're hitting** (read-only, from any machine that can reach the server):

```bash
# Does the deploy hostname hand out an AAAA record? (an AAAA = runners may try IPv6)
dig +short AAAA norman-databases.org

# Is IPv4 :22 reachable but IPv6 :22 not?
nc -4 -z -w 8 145.223.117.219 22      # expect: succeeded
nc -6 -z -w 8 2a02:4780:41:b417::1 22 # expect: timeout (if you have v6 egress)
```

**Permanent prevention (do one of these).** Pinning `DEPLOY_SSH_HOST` to the IP fixes deploys, but the server still advertises an `AAAA` it can't serve, which will keep tripping any IPv6-capable client:

- **DNS:** remove the `AAAA` record for `norman-databases.org` (and `www`) if you don't actually offer IPv6, **or**
- **Server:** fix the host's IPv6 — correct the netmask (`/48` → `/64`) and the v6 default gateway so the advertised address is genuinely reachable.

Keep `DEPLOY_SSH_HOST` as the IPv4 literal regardless: the deploy path should never depend on the public web hostname's DNS.

## What this system does NOT do (by design)

- **No automated DB migrations.** Migrations are too risky to auto-run.
- **No automated rollback.** A failed health check doesn't auto-revert; a human decides.
- **No blue-green or canary.** This is single-host atomic deploy. Adequate for current scale.
- **No staging environment.** Add one when team size justifies it.
- **No secret rotation.** SSH key rotation is a manual annual task.

## Future improvements (not blockers)

- Health check post-deploy (`curl -fsS https://nds.mkassets.sk/up || exit 1`) before declaring success
- Automated rollback if health check fails
- Slack/Discord notifications instead of (or alongside) email
- A `staging` environment that mirrors production, deployed from `development` automatically
- Database backup snapshot before every migrate run
