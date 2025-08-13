# GEMINI Development Guide

This guide details essential procedures and practices for working on the Finance App project.

## Project Rules
- Use `composer` to manage PHP dependencies.
- When working on tests, avoid modifying application code unless:
    - You discover a bug, or
    - You have explicit approval from the project owner.
- Before marking a task as complete, always execute tests and verify application behavior using browser tools.

**Checklist for Common Developer Workflows (always start here):**
1. Review assigned task and clarify requirements if needed.
2. Set up/update your environment as described below.
3. Make only required code or test changes per rules above.
4. Run code-style and static analysis tools before testing.
5. Execute the full test suite and verify app behavior in browser.
6. Upon completion, confirm all checks with `composer final-check`.

## Build and Setup Instructions
This application uses Symfony 7.3 and PHP >=8.2.

1. **Install Dependencies**
   ```bash
   composer install
   ```
2. **Setup Environment**
    - Copy `.env` to `.env.local` and set the necessary variables, especially the database connection string.
   ```bash
   cp .env .env.local
   ```
3. **Database Initialization**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

**Environment Files Policy:**
- Never alter files ending with `.local` (e.g., `.env.local`, `.env.dev.local`) via automation. These are local overrides specific to each developer.

## Testing
- PHPUnit 12.3 is used along with SQLite for isolated test databases.
- The DAMA Doctrine Test Bundle wraps test transactions to ensure idempotency.

### Test Database Setup
- The testing database is defined in `.env.test`:
  ```env
  DATABASE_URL="sqlite:///%kernel.project_dir%/var/data/test.sqlite"
  ```

### Required Test Configuration
1. `phpunit.xml`: Main configuration
2. `tests/bootstrap.php`: Bootstraps testing environment

(Refer to repository for configuration file examples.)

### Running Tests
- Use the pre-configured Composer command to handle test setup and execution:
  ```bash
  composer test:prepare && composer phpunit
  ```
- To run a specific test, use the `--filter` flag:
  ```bash
  composer test:prepare && composer phpunit -- --filter=SourceCrudControllerTest::testSourceDeleteAction
  ```

### Creating Tests
- Place new tests in `tests/`.
- Scaffold test classes using:
  ```bash
  php bin/console make:test
  ```
- For controller tests, extend `Symfony\Bundle\FrameworkBundle\Test\WebTestCase` and use realistic authentication flows. (See documentation for sample test code.)

## Code Quality and Style
### PHP-CS-Fixer
- Fix style violations:
  ```bash
  composer cs-fix
  ```
- Check for issues without fixing:
  ```bash
  composer cs-check
  ```
- Configuration: `.php-cs-fixer.dist.php`

### PHPStan (Static Analysis)
- Run static analysis:
  ```bash
  composer phpstan
  ```
- Configuration: `phpstan.neon.dist` (level 6), checks `src` and `tests`, temp at `var/phpstan`

### Combined Linting
- Run both style check and static analysis:
  ```bash
  composer lint
  ```

## Project Overview
### Major Dependencies
- Symfony 7.3: Framework core
- EasyAdmin Bundle 4.24: Admin interface
- Gedmo Doctrine Extensions: Behaviors (e.g., timestampable)
- Doctrine ORM & Migrations
- PHPUnit 12.3: Testing
- PHPStan: Static analysis

### Main Entities
- `User`: Implements security, uses timestampable
- `Transaction`, `Statement`, `Source`, `Category`: Core business objects
- All use Symfony 7.3 features and PHP 8.2+ attributes

### Common Development Commands
```bash
# Database operations:
php bin/console doctrine:migrations:migrate
./bin/console doctrine:database:drop --force --env=test
./bin/console doctrine:schema:create --no-interaction --env=test
# Code quality:
composer cs-fix
composer phpstan
composer lint
# Testing:
composer test:prepare
composer phpunit
```

## Symfony Console Commands
For a comprehensive command list, use:
```bash
php bin/console list
```
Refer to in-repository documentation for more details on available commands (e.g., `app:create-user`, database, asset, cache, make, migration, and debug commands).

## Browser-Based Functional Testing
- Site: http://finance.ineersa.local/
- Admin login:
    - Email: `admin@test.com`
    - Password: `admin`
- If login fails, reload fixtures.
- On CSRF errors, clear cookies and restart the browser.
- Always verify changes using browser dev tools.

## Development Notes
- Project targets PHP 8.2+ and Symfony 7.3.
- Essential: Have valid `phpunit.xml` and `tests/bootstrap.php` for tests to pass.
- Doctrine is configured with modern attributes and Gedmo extensions.
- Admin interface is managed via EasyAdmin.
- When introducing new functionality, check first if it can be generated via Symfony Maker Bundle.

## Doctrine Query Best Practice
- Prefer explicit criteria over dynamic repository methods:
  ```php
  /** @var UserRepository $userRepository */
  $userRepository = static::getContainer()->get(UserRepository::class);
  $testUser = $userRepository->findOneBy(['email' => 'admin@test.com']);
  ```

## Task Completion Policy
- **Never consider a task complete without running:**
  ```bash
  composer final-check
  ```

After each major stage (setup, coding, testing), validate your results: check for absence of errors, ensure application behavior matches requirements, and correct any issues before proceeding to the next step.
