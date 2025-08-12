# Project Guidelines (Advanced)

This document captures project-specific knowledge to speed up advanced development on the Finance (Symfony 7.3, PHP ≥ 8.2) application.

## 1) Build and Configuration

- Runtime matrix
  - PHP: ≥ 8.2 with intl, ctype, iconv, json, pdo_mysql
  - DB: MySQL/MariaDB (tested against MySQL 8.0.32)
  - Node is NOT required (uses Symfony Asset Mapper + Importmap)

- Environment
  - Core env vars: `APP_ENV`, `APP_SECRET`, `DATABASE_URL`, `MAILER_DSN`
  - Local overrides: use `.env.local` (do not commit secrets). Example:
    ```dotenv
    APP_ENV=dev
    APP_SECRET=<random 32 chars>
    DATABASE_URL="mysql://USER:PASSWORD@127.0.0.1:3306/finance?serverVersion=8.0.32&charset=utf8mb4"
    MAILER_DSN=mailjet+api://<public>:<secret>@default
    ```
    
- Install / bootstrap
  - Dependencies: `composer install`
  - Symfony Flex auto-scripts will run: `assets:install`, `importmap:install`
  - Importmap: to add a browser dependency: `php bin/console importmap:require <pkg>` (kept in `importmap.php`)

- Database
  - Create DB: `php bin/console doctrine:database:create` (if not present)
  - Migrate: `php bin/console doctrine:migrations:migrate -n`
  - Create admin user (ROLE_ADMIN needed for back-office):
    ```bash
    php bin/console app:create-user admin@example.com <password> --role=ROLE_ADMIN
    ```

- Running the app
  - Using PHP built-in server: `php -S 127.0.0.1:8000 -t public`
  - Or using the Symfony CLI: `symfony server:start -d`
  - Back-office (EasyAdmin) is under `/home` (see `DashboardController`), guarded by `ROLE_ADMIN`.

- Assets
  - Asset mapper entrypoint for admin is `assets/admin.js` (registered via `DashboardController::configureAssets()` -> `addAssetMapperEntry('admin')`).
  - Styles are under `assets/styles`, Stimulus controllers under `assets/controllers` (Stimulus bundle installed). If you add controllers, ensure importmap has the proper packages.
  - For prod, precompile assets: `php bin/console asset-map:compile` and warmup cache: `php bin/console cache:warmup --env=prod`.

## 2) Testing

This repository does not ship with PHPUnit preconfigured. You have two options depending on scope:

- Quick smoke tests (no framework): Use short, framework-free PHP scripts for fast checks (suitable for enums, small services that don’t need Kernel).
- Full test suite (recommended for ongoing work): Add PHPUnit or Symfony PHPUnit Bridge and commit `phpunit.xml.dist` + tests/.

### 2.1 Quick smoke test (example we validated)

For simple units like enums, create a temporary script and run it via PHP. Example content (we executed this successfully locally):

```php
<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Enum\TransactionTypeEnum;
$failures = [];
try {
    $credit = TransactionTypeEnum::Credit;
    $debit = TransactionTypeEnum::Debit;
} catch (Throwable $e) { $failures[] = $e->getMessage(); }
if (($credit->value ?? null) !== 'credit') $failures[] = 'Credit value mismatch';
if (($debit->value ?? null) !== 'debit')   $failures[] = 'Debit value mismatch';
if ($credit === $debit)                    $failures[] = 'Enum cases should be distinct';
if ($failures) { fwrite(STDERR, implode("\n", $failures)."\n"); exit(1);} 
fwrite(STDOUT, "OK\n");
```

- Run: `php path/to/script.php`
- Exit code 0 indicates success; non-zero indicates failure with reasons.
- Clean up after: remove the temporary script from the repo.

Notes:
- These scripts are ideal for demonstrating a test workflow without introducing a test framework or external services (DB, Mailer). Keep them self-contained and side-effect free.

### 2.2 Full PHPUnit setup (recommended for sustained development)

If you need proper unit/functional testing:

- phpunit/phpunit is already present in require-dev (currently ^12.x). You can run vendor/bin/phpunit once you add a minimal phpunit.xml.dist.
- Optionally add symfony/phpunit-bridge for extended integration with Symfony (simple-phpunit, deprecations handling).

Example (optional) to add the bridge:

```bash
composer require --dev symfony/phpunit-bridge
```

Then add `phpunit.xml.dist` and create tests under `tests/` with PSR-4 `App\Tests\` (already configured in composer.json). Example minimal test:

```php
<?php
declare(strict_types=1);
namespace App\Tests\Unit;
use PHPUnit\Framework\TestCase;
use App\Enum\TransactionTypeEnum;
final class TransactionTypeEnumTest extends TestCase {
    public function testValues(): void {
        self::assertSame('credit', TransactionTypeEnum::Credit->value);
        self::assertSame('debit',  TransactionTypeEnum::Debit->value);
    }
}
```

- Run: `vendor/bin/phpunit` or `vendor/bin/simple-phpunit` (if using the bridge).
- For functional tests with Kernel, extend `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase` and set `KERNEL_CLASS=App\Kernel` in PHPUnit config.
- Use the `when@test` config in `config/packages/*` (see `security.yaml`) to speed up password hashing and avoid external dependencies.

### 2.3 Guidelines on adding new tests

- Prefer unit tests for pure domain code (Enums, Value Objects, simple services) – fast and isolated.
- For DB-dependent code, rely on transaction rollbacks or a dedicated test DB. Configure `DATABASE_URL` for test env via `.env.test.local`.
- Keep tests deterministic: no reliance on wall clock or network. Inject clocks or use fakes where applicable.

## 3) Additional Development Information

- Coding style
  - Use PHP-CS-Fixer (already required in dev):
    ```bash
    ./vendor/bin/php-cs-fixer fix src
    ```
  - Adopt Symfony coding standards; run fixer before commits.

- Doctrine and data model
  - DoctrineBundle and ORM are available. Manage schema via migrations only; do not sync-metadata in production.
  - Extensions: `gedmo/doctrine-extensions` with `stof/doctrine-extensions-bundle` are installed; prefer their annotations/attributes where appropriate.

- Security
  - Access control: `/home` requires `ROLE_ADMIN`; `/` and `/login` are public in `config/packages/security.yaml`.
  - Login throttling enabled with low thresholds in `main` firewall – be mindful during E2E tests.

- Admin (EasyAdmin)
  - Dashboard route: `/home` (`#[AdminDashboard(routePath: '/home')]`).
  - Menu wired for Users CRUD; add new sections via `configureMenuItems()`.

- Debugging
  - Symfony Web Profiler is enabled in `dev`. Access `_profiler` from dev toolbar or `/ _profiler` paths.
  - Logs: `var/log/dev.log`, `var/log/prod.log`.
  - Useful commands: `bin/console debug:container`, `debug:router`, `cache:clear`, `cache:warmup`.

- Assets & Stimulus
  - Stimulus controllers live under `assets/controllers`. The admin password generator is an example controller (`assets/controllers/admin/password_generator_controller.js`).
  - If you add controllers requiring new npm libs, use `importmap:require` to pin them.

- CI considerations
  - Cache Composer (`vendor/`) and Symfony cache (`var/cache/`), but avoid committing them.
  - For DB, run migrations in CI before functional tests; consider ephemeral MySQL service.

---

Meta: A quick smoke test was created and executed locally to validate the example process; it has been removed to keep the repo clean. For persistent testing, prefer adding PHPUnit as outlined above.

Verification (2025-08-11 23:10 local time): Executed scripts/tmp_smoke_enum.php — output "OK" and exit code 0.
