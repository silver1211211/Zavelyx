# Zavelyx production safety and owner deployment

## Non-negotiable database safety

- Never run tests on a production host or with production environment variables.
- Never run `migrate`, `migrate:*`, any `db:*` command, `model:prune`, or `schema:dump` in production. The application now blocks these commands when `APP_ENV=production`.
- Test runs require `APP_ENV=testing`, `ZAVELYX_TEST_DATABASE_ISOLATED=true`, an empty `DB_URL`, and SQLite `:memory:` (or a disposable SQLite file below `storage/framework/testing`).
- Restore and verify the owner-approved database backup before deploying application code.
- Take and verify a new database backup immediately before every owner-approved deployment.

## Recommended database accounts

Have the hosting owner or DBA create separate credentials. Do not reuse the application credential for schema maintenance.

The normal application account needs only data access:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON `<zavelyx_database>`.* TO '<zavelyx_app>'@'<application_host>';
```

The migration-only account is stored outside the normal application `.env` and used only in a reviewed maintenance window:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
ON `<zavelyx_database>`.* TO '<zavelyx_migrator>'@'<maintenance_host>';
```

Review grants with `SHOW GRANTS`, restrict hosts, use unique strong passwords, and revoke the migration account when it is not needed. This pull request has no schema migration.

## Owner-controlled administrator recovery

After the database backup is restored, the owner can set temporary credentials interactively. The password is hidden and is never accepted as a command-line option:

```bash
php artisan admin:credentials:set --acknowledge-production
```

The command requires confirmation, hashes the temporary password, invalidates prior administrator sessions, and forces the administrator to replace both username and password before dashboard access. Do not send credentials to developers or place them in shell scripts.

## Reviewed deployment procedure

1. Restore and validate the correct pre-incident database backup.
2. Confirm customer, order, transaction, wallet, service, and provider record counts with owner-approved read-only checks.
3. Create and verify an off-host database backup.
4. Review and merge the pull request.
5. On the application host, fetch the reviewed commit and install dependencies without scripts that migrate the database.
6. Run `npm ci` and `npm run build` in the release directory.
7. Do **not** run migrations; this change has no schema changes.
8. Clear application/config/view caches using the existing deployment process. Do not restart shared services.
9. If administrator recovery is required, the owner runs the interactive command above.
10. Verify admin login, forced credential replacement, customer login, order display, and the one-minute scheduler.
11. Observe `orders:update --limit=200` logs. Confirm missing `remains` shows unconfirmed progress and explicit zero completes in the same cycle.

Rollback is application-code-only: redeploy the prior reviewed commit. Do not roll back, truncate, reseed, or recreate the production database.
