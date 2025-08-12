# GEMINI Development Guide

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
        <extensions>
            <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
        </extensions>
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
composer test:prepare && composer phpunit
```

This command automatically:
1. Clears the test environment cache
2. Creates/updates the test database schema
3. Loads fixture data
4. Runs all tests

**Running single test**: You can filter tests by name using the `--filter` option:
```bash
 composer test:prepare && composer phpunit -- --filter=SourceCrudControllerTest::testSourceDeleteAction
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

namespace App\Tests\Controller\Admin;

use App\Entity\Source;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SourceCrudControllerTest extends WebTestCase
{
    public function testSourcesListPageLoadsSuccessfully(): void
    {
        $client = static::createClient();

        // Login as admin user
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneByEmail('admin@test.com');

        $client->loginUser($testUser);

        // Access the sources list page
        $crawler = $client->request('GET', '/home/source');

        // Assert successful response
        $this->assertResponseIsSuccessful();
        
        // Assert page title
        $this->assertSelectorTextContains('h1', 'Sources');
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
composer test:prepare 
composer phpunit
```

## Symfony Console Commands for `bin/console`
```text
Available commands:
  about                                      Display information about the current project
  completion                                 Dump the shell completion script
  help                                       Display help for a command
  list                                       List commands
 app
  app:create-user                            Create new user
 asset-map
  asset-map:compile                          Compile all mapped assets and writes them to the final public output directory
 assets
  assets:compress                            Pre-compresses files to serve through a web server
  assets:install                             Install bundle's web assets under a public directory
 cache
  cache:clear                                Clear the cache
  cache:pool:clear                           Clear cache pools
  cache:pool:delete                          Delete an item from a cache pool
  cache:pool:invalidate-tags                 Invalidate cache tags for all or a specific pool
  cache:pool:list                            List available cache pools
  cache:pool:prune                           Prune cache pools
  cache:warmup                               Warm up an empty cache
 config
  config:dump-reference                      Dump the default configuration for an extension
 dbal
  dbal:run-sql                               Executes arbitrary SQL directly from the command line.
 debug
  debug:asset-map                            Output all mapped assets
  debug:autowiring                           List classes/interfaces you can use for autowiring
  debug:config                               Dump the current configuration for an extension
  debug:container                            Display current services for an application
  debug:dotenv                               List all dotenv files with variables and values
  debug:event-dispatcher                     Display configured listeners for an application
  debug:firewall                             Display information about your security firewall(s)
  debug:form                                 Display form type information
  debug:router                               Display current routes for an application
  debug:translation                          Display translation messages information
  debug:twig                                 Show a list of twig functions, filters, globals and tests
  debug:twig-component                       Display components and them usages for an application
  debug:validator                            Display validation constraints for classes
 doctrine
  doctrine:cache:clear-collection-region     Clear a second-level cache collection region
  doctrine:cache:clear-entity-region         Clear a second-level cache entity region
  doctrine:cache:clear-metadata              Clear all metadata cache of the various cache drivers
  doctrine:cache:clear-query                 Clear all query cache of the various cache drivers
  doctrine:cache:clear-query-region          Clear a second-level cache query region
  doctrine:cache:clear-result                Clear all result cache of the various cache drivers
  doctrine:database:create                   Creates the configured database
  doctrine:database:drop                     Drops the configured database
  doctrine:fixtures:load                     Load data fixtures to your database
  doctrine:mapping:info                      Show basic information about all mapped entities
  doctrine:migrations:current                Outputs the current version
  doctrine:migrations:diff                   Generate a migration by comparing your current database to your mapping information.
  doctrine:migrations:dump-schema            Dump the schema for your database to a migration.
  doctrine:migrations:execute                Execute one or more migration versions up or down manually.
  doctrine:migrations:generate               Generate a blank migration class.
  doctrine:migrations:latest                 Outputs the latest version
  doctrine:migrations:list                   Display a list of all available migrations and their status.
  doctrine:migrations:migrate                Execute a migration to a specified version or the latest available version.
  doctrine:migrations:rollup                 Rollup migrations by deleting all tracked versions and insert the one version that exists.
  doctrine:migrations:status                 View the status of a set of migrations.
  doctrine:migrations:sync-metadata-storage  Ensures that the metadata storage is at the latest version.
  doctrine:migrations:up-to-date             Tells you if your schema is up-to-date.
  doctrine:migrations:version                Manually add and delete migration versions from the version table.
  doctrine:query:dql                         Executes arbitrary DQL directly from the command line
  doctrine:query:sql                         Executes arbitrary SQL directly from the command line.
  doctrine:schema:create                     Processes the schema and either create it directly on EntityManager Storage Connection or generate the SQL output
  doctrine:schema:drop                       Drop the complete database schema of EntityManager Storage Connection or generate the corresponding SQL output
  doctrine:schema:update                     Executes (or dumps) the SQL needed to update the database schema to match the current mapping metadata
  doctrine:schema:validate                   Validate the mapping files
 error
  error:dump                                 Dump error pages to plain HTML files that can be directly served by a web server
 importmap
  importmap:audit                            Check for security vulnerability advisories for dependencies
  importmap:install                          Download all assets that should be downloaded
  importmap:outdated                         List outdated JavaScript packages and their latest versions
  importmap:remove                           Remove JavaScript packages
  importmap:require                          Require JavaScript packages
  importmap:update                           Update JavaScript packages to their latest versions
 lint
  lint:container                             Ensure that arguments injected into services match type declarations
  lint:translations                          Lint translations files syntax and outputs encountered errors
  lint:twig                                  Lint a Twig template and outputs encountered errors
  lint:xliff                                 Lint an XLIFF file and outputs encountered errors
  lint:yaml                                  Lint a YAML file and outputs encountered errors
 mailer
  mailer:test                                Test Mailer transports by sending an email
 make
  make:admin:crud                            Creates a new EasyAdmin CRUD controller class
  make:admin:dashboard                       Creates a new EasyAdmin Dashboard class
  make:auth                                  Create a Guard authenticator of different flavors
  make:command                               Create a new console command class
  make:controller                            Create a new controller class
  make:crud                                  Create CRUD for Doctrine entity class
  make:docker:database                       Add a database container to your compose.yaml file
  make:entity                                Create or update a Doctrine entity class, and optionally an API Platform resource
  make:fixtures                              Create a new class to load Doctrine fixtures
  make:form                                  Create a new form class
  make:listener                              [make:subscriber] Creates a new event subscriber class or a new event listener class
  make:message                               Create a new message and handler
  make:messenger-middleware                  Create a new messenger middleware
  make:migration                             Create a new migration based on database changes
  make:registration-form                     Create a new registration form system
  make:reset-password                        Create controller, entity, and repositories for use with symfonycasts/reset-password-bundle
  make:schedule                              Create a scheduler component
  make:security:custom                       Create a custom security authenticator.
  make:security:form-login                   Generate the code needed for the form_login authenticator
  make:serializer:encoder                    Create a new serializer encoder class
  make:serializer:normalizer                 Create a new serializer normalizer class
  make:stimulus-controller                   Create a new Stimulus controller
  make:test                                  [make:unit-test|make:functional-test] Create a new test class
  make:twig-component                        Create a Twig (or Live) component
  make:twig-extension                        Create a new Twig extension with its runtime class
  make:user                                  Create a new security user class
  make:validator                             Create a new validator and constraint class
  make:voter                                 Create a new security voter class
  make:webhook                               Create a new Webhook
 router
  router:match                               Help debug routes by simulating a path info match
 secrets
  secrets:decrypt-to-local                   Decrypt all secrets and stores them in the local vault
  secrets:encrypt-from-local                 Encrypt all local secrets to the vault
  secrets:generate-keys                      Generate new encryption keys
  secrets:list                               List all secrets
  secrets:remove                             Remove a secret from the vault
  secrets:reveal                             Reveal the value of a secret
  secrets:set                                Set a secret in the vault
 security
  security:hash-password                     Hash a user password
 translation
  translation:extract                        Extract missing translations keys from code to translation files
  translation:pull                           Pull translations from a given provider.
  translation:push                           Push translations to a given provider.
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

## **Important Notes for Development**

- This project uses PHP 8.2+ features and Symfony 7.3
- PHPUnit configuration is critical - tests will fail without proper `phpunit.xml` and `tests/bootstrap.php`
- The project uses modern Doctrine attributes instead of annotations
- Gedmo extensions are available for entities (timestampable, sluggable, etc.)
- EasyAdmin is configured for admin interface development
- If you need to create something, first check if it can be done with `maker` bundle and symfony command

## Doctrine

Always use the explicit criteria-based methods instead of dynamic methods like `findOneByEmail()`:
```php
/** @var UserRepository $userRepository */
$userRepository = static::getContainer()->get(UserRepository::class);
$testUser = $userRepository->findOneBy(['email' => 'admin@test.com']);
```
