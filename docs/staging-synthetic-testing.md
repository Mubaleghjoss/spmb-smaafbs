# SPMB staging snapshot and synthetic testing

This workflow is intentionally one-way: production is a read-only source and `spmb_staging` is the only restore target. No application, bot, or test process may use production credentials from staging.

## Snapshot/restore gate

1. Use the existing production control path for production inspection/backup only: `/home/hermesadmin/bin/smaafbs-prod-dispatch`.
2. Take and verify a readable production backup before any restore.
3. Restore into the separately provisioned `spmb_staging` database. Never put production DB credentials in the staging `.env`.
4. Sanitize the restored environment before booting the app:
   - generate a staging-only `APP_KEY`;
   - set `APP_ENV=staging`, staging `APP_URL`, staging session cookie/domain;
   - clear/revoke copied sessions, password reset tokens, API/integration tokens and bot auth secrets;
   - configure new staging-only `SPMB_DATA_BOT_TOKEN`, `SPMB_DATA_BOT_WEBHOOK_SECRET`, and `SPMB_SYNTHETIC_SECRET`;
   - disable outbound mail and any production integrations unless explicitly required for the test.
5. Compare production and staging totals before synthetic data: applicants, gender, verified/pending, complete/incomplete documents, quota, and waiting list. Record the comparison as an artifact.

The current dispatcher accepts only its reviewed production commands (`status prod`, `audit prod`, deploy/cleanup gates). It does not currently expose a snapshot/restore verb; adding that production capability requires a separate reviewed change to the dispatcher/controller and explicit operator approval. This repository does not execute production operations.

## Synthetic applicants

On staging/local/testing only, configure `SPMB_SYNTHETIC_SECRET`, then run:

```bash
php artisan spmb:test-applicant PROD-TEST-20260915-001 <TAHUN_AJARAN_ID> <GELOMBANG_ID> --count=3 --gender=L
```

The command invokes the same `PendaftaranController::proses` registration path, including validation, category selection, quota preparation, form creation, and initial stage creation. It does not issue direct applicant SQL. Synthetic records carry the exact `test_run_id` and `is_test=true`; quota/read API queries do not exclude them.

## Deterministic notifications

Lifecycle events are emitted by Laravel and sent synchronously to `SPMB_DATA_BOT_WEBHOOK_URL` with:

- `X-SPMB-Webhook-Timestamp`;
- `X-SPMB-Webhook-Signature: sha256=<HMAC_SHA256(timestamp + '.' + raw_json, SPMB_DATA_BOT_WEBHOOK_SECRET)>`;
- `X-SPMB-Event-Type`.

The payload includes `is_test` and `test_run_id`. The bot must verify timestamp freshness and the HMAC before formatting Telegram messages. Test messages must begin with `🧪 SIMULASI SPMB`. The bot should call the read API for `/kuota`; it must not write SQL or invoke an AI model for these deterministic events.

Covered event types are: `account_created`, `registration_completed`, `biodata_completed`, `parent_data_completed`, `school_origin_completed`, `documents_submitted`, `verification_changed`, and `selection_stage_changed`.

## Safe cleanup

Preview exact matches first:

```bash
php artisan spmb:test-cleanup PROD-TEST-20260915-001
```

Apply is blocked outside local/testing/staging and requires an explicit flag (and confirmation unless `--yes` is supplied):

```bash
php artisan spmb:test-cleanup PROD-TEST-20260915-001 --apply
```

Cleanup filters both `test_run_id` and `is_test=true`, physically deletes only those applicants, relies on existing foreign-key cascades for related records, and prints before/after quota/statistics plus the remaining exact-match count.
