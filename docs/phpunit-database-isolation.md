# PHPUnit database isolation

All PHPUnit tests are fail-closed to a dedicated local MySQL target:

- database: `spmb_testing`
- user: `spmb_testing_user`
- host: `127.0.0.1`
- connection: `mysql`

`phpunit.xml` forces `APP_ENV=testing`, `DB_CONNECTION=mysql`, and `DB_DATABASE=spmb_testing`. `tests/TestCase.php` rejects any other database, known production/staging database, non-MySQL connection, or non-dedicated username before Laravel test setup continues.

## Credentials

Do not put the password in Git, Telegram, shell transcripts, CI logs, or command arguments. Supply it through the process environment or a local secret manager:

```bash
export SPMB_TEST_DB_USERNAME=spmb_testing_user
export SPMB_TEST_DB_PASSWORD='(obtain from the local secret manager)'
php artisan config:clear
```

`.env.testing` and `.env.testing.example` contain references only; neither contains a password.

## Provisioning (administrator only)

A MySQL administrator must create the isolated target and grant privileges only on that schema. Use a locally protected admin session and replace the placeholders without recording them:

```sql
CREATE DATABASE IF NOT EXISTS `spmb_testing`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'spmb_testing_user'@'127.0.0.1'
  IDENTIFIED BY '<locally supplied secret>';
ALTER USER 'spmb_testing_user'@'127.0.0.1'
  IDENTIFIED BY '<locally supplied secret>';
GRANT ALL PRIVILEGES ON `spmb_testing`.* TO 'spmb_testing_user'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Do not grant global privileges, do not use `root`, and do not run these statements for production or staging schemas. Verify the grant with `SHOW GRANTS FOR 'spmb_testing_user'@'127.0.0.1';`.

## Safe test sequence

Only after the dedicated user is provisioned and credentials are present:

```bash
php artisan migrate:fresh --force
php artisan test
```

`migrate:fresh` is safe here because the test guard and PHPUnit configuration target exactly `spmb_testing`; never override `DB_DATABASE` for this command.
