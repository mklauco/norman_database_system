# Contributing — How to ship a change

**Audience:** developers joining the NDS project. Read this first.

This document is short on purpose. For deeper detail see [CICD_ARCHITECTURE.md](CICD_ARCHITECTURE.md) (how the pipeline works) and [DEPLOY.md](DEPLOY.md) (operational runbook).

---

## TL;DR — the short version

```
                  branch off
   development ─────────────────▶  feature/your-change
        ▲                                  │
        │  PR (you open)                   │
        └──────────────────────────────────┘
        │
        │  PR (Martin opens, when ready to release)
        ▼
       main  ─────▶  production (auto-deploy)
```

**Always branch off `development`.** Never branch off `main` (production code is not where new work starts). Never branch off another feature branch (unless explicitly stacked — see §3.4).

**Two PRs, two roles.** Don't confuse them:

| PR | Who opens it | Who reviews & merges |
|---|---|---|
| **Feature PR** — `feature/X → development` | The developer who wrote the change (you) | Martin |
| **Release PR** — `development → main` | Opened automatically once `development` moves ahead of `main` | Martin |

- As a developer, you only ever open feature PRs targeting `development`. You never touch `main`.
- Martin batches accumulated `development` work into a release PR when he decides to ship.
- Merging the release PR auto-fires the deploy workflow.
- **Don't push directly to `development` or `main`.** Both should be PR-only.
- **Never run `php artisan migrate*` on production.** Migrations go through the `Migrate Production DB` GitHub Action.

---

## 1. Branching model

| Branch | Who pushes | What it represents |
|---|---|---|
| `main` | nobody directly — only via merged PR from `development` | Currently in production |
| `development` | nobody directly — only via merged PR from feature branches | Integration: the next release candidate |
| `feature/*`, `fix/*`, `chore/*` | you | Your work-in-progress, short-lived (delete after merge) |

Branch names are descriptive: `feature/empodat-export-pagination`, `fix/login-redirect-loop`, `chore/upgrade-livewire`.

---

## 2. Making a change

```bash
# 1. Sync development
git checkout development
git pull origin development

# 2. Branch
git checkout -b feature/your-change

# 3. Code, commit
git add <files>
git commit -m "Short imperative: what changed and why"

# 4. Push and open a PR
git push -u origin feature/your-change
gh pr create --base development --title "..." --body "..."
# (Or use the GitHub UI — link appears in your push output.)
```

Then in the PR:
- **CI runs automatically.** Two jobs: composer install and frontend build. Both must pass.
- **Wait for review.** All PRs are reviewed by **Martin (`@mklauco`)** at this stage. As the team grows we may delegate.
- **Merge** when CI is green and the review is approved.

Your branch gets deleted after merge. Don't keep working on it.

---

## 3. Day-to-day workflow patterns

The single-PR flow above covers 80% of cases. Here's what to do for the rest.

### 3.1 House rules (read once)

| Topic | Rule |
|---|---|
| Sync feature branch with development | **Merge** development into your feature branch. Don't rebase. |
| Closing a PR | **Squash and merge.** One commit per PR on the target branch. |
| Commit messages | Free-form. No required prefix. Just be descriptive. |
| Reviewer | `@mklauco` for all PRs at this stage. |
| Hotfixes | Through `development` like everything else. No fast-path to `main`. |

### 3.2 Long-running features (work that takes more than a couple of days)

While you're working on `feature/your-change`, other people will be merging things into `development`. Periodically **pull `development` into your branch** to stay in sync and surface conflicts early:

```bash
git checkout feature/your-change
git fetch origin
git merge origin/development
# Resolve conflicts if any, commit the merge
git push
```

Do this at least every couple of days. The longer you wait, the worse the conflicts.

**Don't rebase** a shared/pushed branch. Rebase rewrites history and breaks anyone else's local copy of your branch (and confuses GitHub's PR view). Merge keeps history honest.

### 3.3 Working on multiple features in parallel

Each feature gets its own branch. Switching is just `git checkout`:

```bash
git checkout -b feature/a    # work on A
git commit -am "wip A"
git checkout -b feature/b    # work on B
git commit -am "wip B"
git checkout feature/a       # back to A
```

Each branch should be branched off `development`, not off another feature branch (unless they're explicitly stacked — see 3.4).

### 3.4 Stacked PRs (rare; only when feature B truly depends on feature A)

If feature B can't exist without feature A, and A is still in review:

```bash
git checkout feature/a
git checkout -b feature/b    # B branched off A, not development
# … work on B …
gh pr create --base feature/a   # PR targets A's branch, not development
```

After A is merged into development, **rebase B onto development** (rebase is OK here because B hasn't been reviewed yet):

```bash
git checkout feature/b
git fetch origin
git rebase --onto origin/development feature/a
git push --force-with-lease
gh pr edit --base development
```

If you don't understand the above paragraph, you don't need stacked PRs. Just sequence the work: ship A, then start B.

### 3.5 Resolving conflicts when `development` moves

If GitHub says "This branch has conflicts that must be resolved":

```bash
git checkout feature/your-change
git fetch origin
git merge origin/development
# Open the conflicted files, resolve the <<<<<<< markers
git add <resolved-files>
git commit                    # default message "Merge branch 'development' into ..."
git push
```

The PR updates automatically and CI re-runs.

If the conflicts are huge or scary, ask Martin before resolving — it might mean two features are colliding badly and need coordination.

### 3.6 Draft PRs

Open a **Draft Pull Request** when you want CI to run on your work but you're not ready for review. Click the dropdown on the green "Create pull request" button and choose "Create draft pull request".

Convert to "Ready for review" when you actually want eyes on it. This is a courtesy: Martin won't get notified for draft PRs, so they don't pile up in his queue.

### 3.7 Code review etiquette

For the reviewer (Martin today):
- Comment on specific lines via the GitHub diff view.
- Mark the PR "Approve" or "Request changes" when the review is done.
- For trivial typo-level comments, "Approve" with comments is fine — don't block on cosmetic preferences.

For the author (you):
- Don't take review comments personally — treat them as design discussion.
- Push fixup commits in response. Don't force-push to a branch under review — it makes the reviewer lose their place in the diff. The squash-merge will collapse fixups at merge time.
- After addressing comments, **re-request review** by clicking the "🔄" icon next to the reviewer's name. Don't expect them to notice silently.

### 3.8 When CI fails on your PR

Click the failed check, read the log, fix the cause in your branch, push again. CI re-runs automatically. There is no "re-run without changes" option for the same commit — every fix needs a real change or an empty commit (`git commit --allow-empty -m "Retry CI"`).

If CI is failing for a reason that isn't your fault (transient infra issue, Composer registry hiccup), push an empty commit and watch.

### 3.9 When the deploy fails after your PR is merged

You merged to `main`, the `Deploy` workflow shows a red ❌, and production may or may not be in a half-deployed state. Don't panic.

1. **Check production is up:** open https://norman-databases.org/ in a browser. If it loads, the deploy aborted before swapping the symlink — production is unchanged. Just diagnose the failure and fix forward in a new PR.
2. **If production is down:** follow the rollback procedure in [DEPLOY.md](DEPLOY.md#rollback). It takes ~30 seconds — `current` symlink points back at the previous release.
3. **Either way, tell Martin immediately.**

### 3.10 Hotfix workflow

Production is broken and needs a fix now. The path is the same as a normal feature, just expedited:

```bash
git checkout development
git pull
git checkout -b fix/short-description
# … fix it …
git push -u origin fix/short-description
gh pr create --base development --title "Hotfix: ..."
```

After CI green and Martin's approval, **two PRs land in quick succession**: yours into `development`, then `development → main` (Martin handles that one). The `main` merge fires the deploy.

We don't allow `fix/* → main` directly, even for emergencies. The reason: skipping `development` means `development` gets out of sync with `main`, and the next normal release accidentally re-introduces the bug being fixed (because development was branched before the hotfix landed). Always through `development`.

If `development` has unshippable WIP that's blocking the hotfix release, that's a process problem to flag, not a reason to bypass the path.

### 3.11 Reverting a merged PR

If a PR turns out to be wrong and is already on `development` (or worse, `main`):

- **From `development`:** open the merged PR on GitHub, click "Revert". Creates a revert PR. Merge that.
- **From `main`:** same procedure, but the revert PR will itself trigger a deploy. So a revert is itself a release.
- **For irreversible operations** (executed migrations, files deleted from disk, external API calls): a revert undoes the *code*, NOT the side effects. Tell Martin before reverting anything that touched the database or external systems.

---

## 4. What CI checks today

`.github/workflows/ci.yml` runs on every push and PR to `development` or `main`:

- `composer validate --strict` and `composer install`
- `npm ci && npm run build`

CI does **not yet** run tests, lint, or static analysis. Don't take this as licence to ship broken code — those are coming. Run tests locally before pushing (`php artisan test`).

---

## 5. What happens after merge to `development`

Nothing automatic. The new code waits in `development` for the next release.

`development` is the integration branch — it's where multiple features pile up before they ship together.

The release PR (`development → main`) is opened **automatically** by `.github/workflows/release-pr.yml` as soon as `development` moves ahead of `main`, and stays open, accumulating commits, until someone merges it. If one is already open it is left alone — GitHub keeps it current by itself.

**Opening it is not releasing it.** Nothing deploys until the PR is merged, and **releases are still Martin's call**: he decides when there's enough on `development` to justify shipping, reviews the aggregate diff, and merges. Merge it with a **merge commit, never a squash** — squashing makes the two branches diverge and every release after that needs a reconcile PR. As a developer you never merge the release PR yourself. If your change is urgent and can't wait, ping Martin — he'll cut the release.

---

## 6. What happens after merge to `main` (the release)

Automatic deploy. Within ~60 seconds:

1. GitHub Actions clones the new code into `releases/<timestamp>_<sha>/` on the server.
2. Installs PHP and JS dependencies inside ephemeral containers.
3. Puts the site in maintenance mode (`artisan down`, ~10 s of HTTP 503).
4. Atomically swaps `current` symlink to the new release.
5. Rebuilds Laravel caches; reloads PHP-FPM; restarts queue workers.
6. Lifts maintenance mode.

Verification: open https://norman-databases.org/ — should be live.

If anything breaks: see [DEPLOY.md](DEPLOY.md#rollback) for the rollback procedure (5-second symlink swap to the previous release).

---

## 7. Database migrations

**Migrations DO NOT run automatically on deploy.** They are a manual step.

When your change includes a migration:

1. Get the code merged to `main` (deploy lands the migration files on the server but doesn't run them).
2. Tell Martin (or whoever has migrate rights) to run the `Migrate Production DB` workflow:
   - GitHub → Actions → Migrate Production DB → Run workflow
   - Type `MIGRATE PRODUCTION` in the confirmation field.
   - Leave `pretend` set to `true` for the first run — it shows the SQL without executing.
   - Re-run with `pretend: false` to actually execute.

**Why manual:** migrations on a 50M-row table can lock production. Operator approval is the gate.

**Never** SSH to the server and run `php artisan migrate` directly. Always go through the workflow so there's an audit trail.

---

## 8. Test database safety

`tests/TestCase.php` aborts any test run that points at a non-test database. The guard allows:

- `sqlite :memory:` (the phpunit.xml default)
- Database names starting with `norman_test` or `ci_`

If you see "Test suite refused to run", you accidentally pointed tests at a real DB. Check your environment or `phpunit.xml`.

This guard exists because the live `nds` database was wiped once by a stray `migrate:fresh` during testing. Don't disable it.

---

## 9. Things that will get your PR rejected

- Push directly to `development` or `main`
- Skip CI
- Run migrations on production by SSHing
- Edit production code on the server
- Commit secrets (`.env`, `auth.json`, API keys)
- Modify `composer.lock` without explaining why
- Edit `Dockerfile` or `docker-compose.*.yml` without flagging it for Martin
- Add new dependencies without justification
- Disable the test DB guard
- Submit work-in-progress without marking the PR as draft
- Force-push to a shared branch

---

## 10. Local development setup

(Brief — full setup belongs in the README. This is the deploy-relevant part.)

- Local dev uses the repo's `docker-compose.yml`, NOT `docker-compose.production.yml`.
- Your local `.env` is yours, not committed. Use `.env.example` as a starting point.

### One-time test database setup

Tests run against a real PostgreSQL database (matches production). Each developer needs an empty `norman_test` database in their local Postgres instance — same host/port/user as the dev `nds` database.

```bash
psql -h 127.0.0.1 -p 5433 -U app -d nds -c "CREATE DATABASE norman_test OWNER app;"
```

(Adjust `-h`, `-p`, `-U` to match your local Postgres if different. The default in `phpunit.xml` is `127.0.0.1:5433` with user `app`.)

That's all. The `tests/TestCase.php` guard already permits any database name starting with `norman_test`, so you're protected from a stray `migrate:fresh` accidentally hitting your dev `nds` database.

Run tests with:

```bash
php artisan test
```

Each run starts with a clean schema (`RefreshDatabase` trait drops and recreates tables). Your `nds` dev database is never touched.

---

## 11. Where to read more

- [CICD_ARCHITECTURE.md](CICD_ARCHITECTURE.md) — full reference: workflows, server layout, secrets, audit trails. Read if you want to understand the system.
- [DEPLOY.md](DEPLOY.md) — operational runbook: bootstrap, rollback, troubleshooting. Read if you're on call.
- [TESTING_AND_CICD_PLAN.md](TESTING_AND_CICD_PLAN.md) — the original plan. Historical; the implementation differs in places.

---

## 12. Quick reference

| Want to… | Do this |
|---|---|
| Start a new feature | `git checkout development && git pull && git checkout -b feature/...` |
| See if a deploy succeeded | https://github.com/mklauco/norman_database_system/actions |
| See what's in production right now | On server: `readlink /opt/projects/norman_database_system/current` (trailing 7 chars = commit SHA) |
| Run tests locally | `php artisan test` |
| Run a migration on prod | Actions → "Migrate Production DB" → Run workflow |
| Roll back a bad deploy | See [DEPLOY.md](DEPLOY.md#rollback) |
| Report a bug | GitHub Issues |
| Ask a question | Slack / email Martin |
