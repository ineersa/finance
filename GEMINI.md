# Gemini Development Guide

This document provides essential information for developers working on the Finance App project.

## Build and Configuration

This is a Symfony 7 project. The following are the basic steps to get the project running:

1.  **Install Dependencies**:
    ```bash
    composer install
    ```

2.  **Configure Environment**:
    Create a `.env.local` file by copying `.env` and customize the variables, especially the database connection string.
    ```bash
    cp .env .env.local
    ```

3.  **Database Migrations**:
    Run the following command to apply database migrations:
    ```bash
    php bin/console doctrine:migrations:migrate
    ```

**Important Note on Environment Files**: Any files ending in `.local` (e.g., `.env.local`, `.env.dev.local`) are intended for local overrides and should **never** be modified by automated tooling. These files are specific to your local environment.

## Testing

The project uses PHPUnit for testing.

### Running Tests

To run the entire test suite, use the following command:

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
php bin/phpunit
```

### Creating New Tests

New tests should be placed in the `tests/` directory. You can use the `make:test` command to generate a new test class:

```bash
php bin/console make:test
```

For example, to create a test for a controller, you can create a file in `tests/Controller/` and extend `Symfony\Bundle\FrameworkBundle\Test\WebTestCase`.

Here is an example of a simple test for the `WelcomeController`:

```php
<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WelcomeControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Personal Finance App');
    }
}
```

## Code Style and Static Analysis

This project uses `friendsofphp/php-cs-fixer` to enforce a consistent code style and `phpstan/phpstan` for static analysis.

### Linting and Code Style

You can run the linter and code style fixer with the following commands:

```bash
composer lint
composer lint:fix
```

### PHPStan

To run PHPStan, use the following command:

```bash
composer phpstan
```

You may need to create a `phpstan.neon` file in the root of the project to configure PHPStan. Here is an example configuration that includes the Doctrine extension:

```neon
includes:
    - vendor/phpstan/phpstan-doctrine/extension.neon

parameters:
    level: 8
    paths:
        - src
        - tests
```
