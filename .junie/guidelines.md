# Junie Development Guide

This document provides essential information for developers working on the Finance App project.

**Important rules for project**:
 - Use `composer` to install dependencies
 - If working on tests, try to not modify actual code unless bug was found or specifically requested/got permission from the project owner
 - Run tests to ensure proper functionality, including checking functionality by running browser tools

## Build and Configuration

This is a Symfony 7.3 project requiring PHP >=8.2. The following are the basic steps to get the project running:

1.  **Install Dependencies**:
    ```bash
    composer install
    ```

2.  **Configure Environment**:
    Create a `.env.local` file by copying `.env` and customize the variables, especially the database connection string.
    ```bash
    cp .env .env.local
    ```

3.  **Database Setup**:
    Run the following commands to set up the database:
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

**Important Note on Environment Files**: Any files ending in `.local` (e.g., `.env.local`, `.env.dev.local`) are intended for local overrides and should **never** be modified by automated tooling. These files are specific to your local environment.

## Testing

The project uses PHPUnit 12.3 for testing with SQLite database for test data isolation. The application uses **DAMA Doctrine Test Bundle** which ensures that data is idempotent between tests by wrapping tests into transactions, preventing test data interference.

### Test Database Configuration

The project uses SQLite database for testing as configured in `.env.test`:
```
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data/test.sqlite"
```

### Required Test Configuration Files

Before running tests, ensure these files exist:

1. **phpunit.xml** - Main PHPUnit configuration:
   ```xml
   <?xml version="1.0" encoding="UTF-8"?>
   
   <phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
            backupGlobals="false"
            colors="true"
            bootstrap="tests/bootstrap.php"
   >
       <php>
           <ini name="display_errors" value="1" />
           <ini name="error_reporting" value="-1" />
           <server name="APP_ENV" value="test" force="true" />
           <server name="SHELL_VERBOSITY" value="-1" />
           <server name="SYMFONY_PHPUNIT_REMOVE" value="" />
           <server name="SYMFONY_PHPUNIT_VERSION" value="12.3" />
           <env name="KERNEL_CLASS" value="App\Kernel" />
       </php>

       <testsuites>
           <testsuite name="Project Test Suite">
               <directory>tests</directory>
           </testsuite>
       </testsuites>

       <source>
           <include>
               <directory suffix=".php">src</directory>
           </include>
       </source>
   </phpunit>
   ```

2. **tests/bootstrap.php** - Test bootstrap file:
   ```php
   <?php
   
   use Symfony\Component\Dotenv\Dotenv;
   
   require dirname(__DIR__).'/vendor/autoload.php';
   
   if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
       require dirname(__DIR__).'/config/bootstrap.php';
   } elseif (method_exists(Dotenv::class, 'bootEnv')) {
       (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
   }
   ```

### Running Tests

**Recommended**: Use the integrated composer command that handles all test setup:

```bash
composer test
```

This command automatically:
1. Clears the test environment cache
2. Creates/updates the test database schema
3. Loads fixture data
4. Runs all tests

**Manual alternative**: To run tests manually, you need to prepare the test database first:

```bash
php bin/console cache:clear --env=test
php bin/console doctrine:database:drop --force --env=test
php bin/console doctrine:schema:create --no-interaction --env=test
php bin/console doctrine:fixtures:load --no-interaction --env=test
php bin/phpunit
```

### Creating New Tests

New tests should be placed in the `tests/` directory. You can use the `make:test` command to generate a new test class:

```bash
php bin/console make:test
```

For example, to create a test for a controller, create a file in `tests/Controller/` and extend `Symfony\Bundle\FrameworkBundle\Test\WebTestCase`.

Here is an example of a simple test:

```php
<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExampleControllerTest extends WebTestCase
{
    public function testExample(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Personal Finance App');
        $this->assertSelectorExists('body');
    }
}
```

## Code Style and Static Analysis

This project uses custom Composer scripts for code quality tools:

### PHP-CS-Fixer

You can run the code style fixer with the following commands:

```bash
composer cs-fix    # Apply fixes
composer cs-check  # Check for issues without fixing
```

Configuration is in `.php-cs-fixer.dist.php`.

### PHPStan

To run PHPStan static analysis, use:

```bash
composer phpstan
```

Configuration is in `phpstan.neon.dist` at level 6, analyzing `src` and `tests` directories with custom tmpDir at `var/phpstan`.

### Combined Linting

To run both PHP-CS-Fixer and PHPStan together:

```bash
composer lint
```

## Project Architecture

### Key Dependencies

- **Symfony 7.3**: Core framework
- **EasyAdmin Bundle 4.24**: Admin interface
- **Gedmo Doctrine Extensions**: Timestampable and other behaviors
- **Doctrine**: ORM and migrations
- **PHPUnit 12.3**: Testing framework
- **PHPStan**: Static analysis

### Entity Structure

The project includes the following main entities with Doctrine ORM:

- **User**: Implements Symfony security interfaces, uses Gedmo timestampable trait
- **Transaction**: Financial transaction records
- **Statement**: Bank statements
- **Source**: Data sources
- **Category**: Transaction categories

All entities use modern Symfony 7.3 features and PHP 8.2+ attributes for configuration.

### Development Commands

```bash
# Database
php bin/console doctrine:migrations:migrate
./bin/console doctrine:database:drop --force --env=test
./bin/console doctrine:schema:create --no-interaction --env=test

# Code Quality
composer cs-fix
composer phpstan
composer lint

# Testing
php bin/phpunit

# Symfony
php bin/console make:test
php bin/console cache:clear
```

## Functional Testing

You can test functionality in the browser at: http://finance.ineersa.local/

**Login Credentials**:
- Email: admin@test.com  
- Password: admin

**Important Note**:
If credentials are incorrect, reload fixtures
If getting invalid CSRF token error, try to clear cookies and restart the browser

Always test new functionality and changes using browser developer tools to ensure proper behavior.

## Important Notes for Development

- This project uses PHP 8.2+ features and Symfony 7.3
- PHPUnit configuration is critical - tests will fail without proper `phpunit.xml` and `tests/bootstrap.php`
- The project uses modern Doctrine attributes instead of annotations
- Gedmo extensions are available for entities (timestampable, sluggable, etc.)
- EasyAdmin is configured for admin interface development

