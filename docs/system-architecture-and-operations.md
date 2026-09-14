# SPMB SMA AFBS: System Architecture and Operations

This document is the operational reference for SPMB SMA AFBS (Phase 29). It describes the application topology, model routing, deployment controls, integrations, security rules, and recovery procedures. Values below are paths, service names, and identifiers only; secrets must never be copied into this document.

## 1. SPMB Architecture

### Application stack

- **Framework:** Laravel 11 on PHP `>= 8.2`.
- **Server-rendered and interactive UI:** Blade with Livewire 3.
- **Frontend tooling:** Vite, Tailwind CSS, and Bootstrap. Vite produces the deployable assets in `public/build`.
- **Data and application services:** Laravel HTTP routes, middleware, controllers, services, queued work, scheduled Artisan commands, and a relational database.

### Production (Rumahweb cPanel)

The production installation separates application code from the web-facing document root:

| Component | Location/value |
|---|---|
| Application root (`APP_ROOT`) | `/home/sman5479/spmb-app` |
| Public root (`PUBLIC_ROOT`) | `/home/sman5479/public_html/web/www.seleksi` |
| Database | `sman5479_spmb` |
| Application URL | `https://seleksi.smaafbs.sch.id` |

Only the public assets and the front controller belong under `PUBLIC_ROOT`. The application `.env`, `vendor`, `storage`, and framework code remain outside the public web root.

### VPS staging

Staging runs from `/var/www/spmb-staging` and uses immutable, release-based directories:

```text
/var/www/spmb-staging/
  current -> releases/<active-sha>
  releases/<SHA>/
  shared/
    .env
    storage/
```

`current` is changed atomically, so the web server never needs to follow a partially copied release. The shared `.env` and `storage` survive releases; each release gets symlinks to them. Staging must use `APP_ENV=staging` and `APP_URL=https://staging-seleksi.smaafbs.sch.id`.

## 2. Coding Topics and AI Model Routing

Hermes is the **Orchestrator**: it clarifies the task, chooses the risk/complexity tier, reviews the result, and coordinates verification. Pi is the **Coding Executor**: it reads the repository, edits files, runs appropriate checks, and reports implementation details.

| Topic/complexity | Route | Thinking |
|---|---|---|
| Lightweight (Ringan) | `9router-chat` / `ag/gemini-3.8-flash-medium` | Default/normal |
| Medium (Menengah) | `9router-codex` / `cx/gpt-5.6-luna` | Default/normal |
| Medium-heavy (Menengah-Berat) | `9router-codex` / `cx/gpt-5.6-terra` | `high` |
| Heavy (Berat) | `9router-codex` / `cx/gpt-5.6-sol` | `high` |

Use the heavier tiers for migrations, security-sensitive deployment changes, integrations, broad refactors, and incident recovery. Do not put credentials, tokens, passwords, or private personal data in prompts or model context.

## 3. Staging Deployment Workflow

The canonical command is:

```bash
scripts/deploy-staging.sh <EXACT_SHA>
```

`<EXACT_SHA>` must be the full 40-character commit SHA and must resolve to a commit in the local repository. The script refuses production paths/domains, requires the shared staging `.env`, and validates its staging environment values.

### Deployment sequence

1. Create the release directory at `releases/<SHA>` and obtain the source with `git archive` for that exact commit.
2. Link `shared/.env` as the release `.env` and link `shared/storage` as release storage (including the public storage link).
3. Run `composer install --no-dev --optimize-autoloader --no-interaction`.
4. Run `npm ci` and `npm run build` when the Node toolchain and `package.json` are available.
5. Run `php artisan optimize:clear`.
6. Run `php artisan migrate --force`.
7. Run `php artisan optimize`.
8. Atomically switch `current` to the completed release.
9. Retain the five latest release directories and remove older releases.

A deployment lock prevents concurrent staging deployments. Build or migration failure occurs before the switch where possible; if failure occurs after a switch, the script restores the previous release.

### Staging rollback

Confirm the target release exists, then run:

```bash
scripts/deploy-staging.sh --rollback <PREVIOUS_SHA>
```

The rollback also uses an atomic symlink replacement. Verify the application URL, logs, and database compatibility after rollback. Database migrations are not automatically reversed; use a reviewed down migration or database restore only when explicitly approved.

## 4. Production Deployment and Preview

The production entry point is:

```bash
scripts/deploy-cpanel-ssh.sh
```

The script has strict target validation. It requires:

- `APP_ROOT=/home/sman5479/spmb-app` and the configured production public root.
- An existing server `.env`; it must not create fallback credentials.
- `APP_ENV=production` and the production application URL.
- `DB_DATABASE=sman5479_spmb`, with non-empty credentials supplied only by the existing server `.env`.
- No substitute, test, `ujian`, or default database credentials.

### Mandatory preview gate

Production work **always stops at PREVIEW first**. Review the exact commit SHA, changed files, build/migration implications, maintenance risk, and target paths before execution. A production deployment proceeds only after explicit approval of that exact SHA. The preview must not print secret values.

After approval:

1. Verify that a current backup completed successfully and is readable before changing application files.
2. Deploy the approved exact SHA to the production application root using the approved SSH/cPanel procedure.
3. Install dependencies and build assets without changing the server `.env` or generating replacement credentials.
4. Run cache clearing, migrations with `--force` when approved, and Laravel optimization.
5. Ensure the public front controller and assets point to the application root.
6. Perform an authenticated/appropriate health check, inspect web and Laravel logs, and record the deployed SHA and backup reference.

Do not skip the preview, exact-SHA approval, or backup verification for an urgent deployment. If any strict validation fails, stop and investigate rather than overriding it.

## 5. Read API and Synchronization

### Read endpoint

```text
GET /api/v1/integrations/akses/graduated-students
```

This endpoint is protected by the `akses.sync` token middleware and rate limiting (currently `throttle:30,1`). HTTPS is required by the integration configuration in production. The token is configured outside source control and must never be placed in this document, a commit, a chat, or Telegram.

### Sync endpoints

```text
GET  /api/sync/export
POST /api/sync/import
```

Sync routes must be treated as privileged integration operations. Apply the configured authentication, authorization, HTTPS, validation, audit logging, and rate limiting before enabling an external consumer. Never expose them as anonymous public endpoints.

### External integration policy

External integrations receive the minimum data required for their stated purpose. The graduated-student integration is read-only: it may query approved records and metadata, but may not mutate admissions, users, payments, results, tokens, or configuration. Any import operation is separately authorized, schema-validated, auditable, and approved by an administrator. Avoid returning passwords, tokens, internal logs, or unnecessary personal data.

## 6. Telegram Data Bot

The planned/integration architecture for the Telegram data bot is a read-only query layer over approved application services or reporting data. Telegram privacy mode must remain enabled. The bot must be restricted to an allowlist of authorized user IDs and groups, and commands must reject unknown chats/users.

The bot has no write, migration, deployment, shell, credential-management, or administrative access. It must not execute arbitrary SQL or accept secrets through chat. Responses should minimize personal data, avoid sensitive fields by default, and be logged without message contents or tokens. Revoke bot access and rotate its token immediately if an authorized chat or account is compromised.

## 7. Authorized Users and Security

- Use Laravel RBAC for admin roles and permissions; grant the least privilege needed for administration, verification, reporting, deployment review, and integration management.
- Enforce authentication, authorization middleware, CSRF protection for browser writes, validation, and activity/audit logging for sensitive actions.
- Set session lifetimes deliberately for the risk profile, expire idle sessions, invalidate sessions on logout/password change, and avoid shared administrator accounts.
- Use secure cookies in production: `Secure`, `HttpOnly`, and an appropriate `SameSite` policy. Require HTTPS for authenticated and integration traffic.
- Store `.env` files outside the public web root. Protect them with `chmod 640` or `chmod 600` as appropriate for the service account, and verify ownership and group access.
- Never commit secrets to Git or send them through Telegram. This includes `APP_KEY`, database passwords, API tokens, bot tokens, session secrets, and backup credentials.
- Review access logs, Laravel activity logs, failed authentication, token usage, and unexpected deployment activity regularly.

## 8. Service Names and Log Locations

| Service/log | Location |
|---|---|
| Web server: Nginx | `/var/log/nginx/` on VPS; use the configured cPanel/staging Nginx logs where applicable |
| PHP-FPM | Service `php8.2-fpm`; `/var/log/php8.2-fpm.log` where configured |
| Laravel application | `storage/logs/laravel.log` (shared storage on staging) |

On an incident, correlate the deployment timestamp and SHA with Nginx access/error logs, PHP-FPM errors, and `laravel.log`. Redact credentials and personal data when exporting log excerpts.

## 9. Disaster Recovery

### Backup strategy

The application backup command is:

```bash
php artisan spmb:backup-database
```

The Laravel scheduler runs it daily at **02:00**. Confirm the scheduler/cron is actually running, monitor exit status and backup size, and periodically test restoration. Backups must be access-controlled and retained according to the institution's retention policy.

For production pre-deploy protection, use the cPanel directory concept:

```text
/home/sman5479/deploy-backups/spmb/
```

Each deployment should have a timestamped backup/reference recorded before file changes. Do not store backup passwords in the repository or this document.

### Staging recovery runbook

1. Stop or pause further deployments and identify the last known-good SHA.
2. Inspect `current`, release directories, Laravel logs, Nginx/PHP-FPM logs, and migration status.
3. If code-only, run `scripts/deploy-staging.sh --rollback <PREVIOUS_SHA>` and health-check staging.
4. If data compatibility is involved, obtain approval for a tested migration rollback or restore the appropriate database backup.
5. Record the incident, cause, SHA, database action, and verification results.

### Production recovery runbook

1. Declare the incident, stop new deployments, and preserve the failing SHA and logs.
2. Confirm the pre-deploy backup in `/home/sman5479/deploy-backups/spmb/` is present, readable, and matched to the deployment.
3. For a code-only fault, restore the previously approved application release/files through the production SSH/cPanel procedure; do not delete the only copy of the current release.
4. For data corruption, place the application in an approved maintenance/read-only state and restore only the verified backup using a documented change. Coordinate destructive database actions with the owner.
5. Re-run cache/optimization steps as appropriate, verify permissions and the public front controller, then health-check the application and integrations.
6. Monitor logs and user-facing behavior, notify authorized stakeholders, and document the recovery and follow-up prevention work.

Recovery actions must preserve secrets, avoid ad-hoc credential replacement, and never treat a database restore as a substitute for reviewing the underlying deployment defect.
