# Testing Guide

This project uses [Pest](https://pestphp.com/) for testing, which provides an elegant testing framework built on top of PHPUnit.

## Prerequisites

## Running Tests

### Basic Test Commands

```bash
# Run all tests
composer test
# or
./vendor/bin/pest

# Run only unit tests
composer test:unit

# Run only feature tests  
composer test:feature

# Run specific test file
./vendor/bin/pest tests/Unit/DatabaseTableConfigurationTest.php

# Run tests matching a pattern
./vendor/bin/pest --filter="RedirectController"
```

## Test Structure

```
tests/
├── Feature/           # Integration tests that test full application flow
│   ├── RedirectControllerTest.php
│   ├── RepositoryIntegrationTest.php
│   └── DatabaseTableIntegrationTest.php
├── Unit/              # Unit tests for individual classes/methods
│   ├── DatabaseTableConfigurationTest.php
│   ├── MigrationTableConfigurationTest.php  
│   └── RepositoryConfigurationTest.php
├── Pest.php          # Test configuration
└── TestCase.php      # Base test class
```

## Test Suites

The project is organized into test suites:

- **Unit Tests**: Test individual classes and methods in isolation
- **Feature Tests**: Test complete workflows and integrations

## Configuration Files

- `pest.xml`: PHPUnit/Pest configuration with coverage settings
- `tests/Pest.php`: Pest-specific configuration and global functions
- `tests/TestCase.php`: Base test class with Statamic setup

## Writing Tests

### Example Unit Test
```php
test('repository uses configured table name', function () {
    config(['redirects.table' => 'custom_table']);
    
    $repository = new DatabaseRedirectRepository();
    
    expect($repository->getTableName())->toBe('custom_table');
});
```

### Example Feature Test
```php
test('creates redirect through controller', function () {
    $this->actingAs(User::make()->makeSuper());
    
    $response = $this->post(cp_route('redirects.store'), [
        'source' => '/old-page',
        'destination' => '/new-page',
        'status_code' => 301
    ]);
    
    $response->assertRedirect();
    expect(Redirect::count())->toBe(1);
});
```

## Troubleshooting


## Additional Resources

- [Pest Documentation](https://pestphp.com/docs)
- [PHPUnit Code Coverage](https://phpunit.readthedocs.io/en/latest/code-coverage.html)
- [Xdebug Documentation](https://xdebug.org/docs/)