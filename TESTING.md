# Testing Guide

This project uses [Pest](https://pestphp.com/) for testing, which provides an elegant testing framework built on top of PHPUnit.

## Prerequisites

### For Laravel Herd Pro Users (Recommended)

If you're using **Laravel Herd Pro**, Xdebug is already included! The composer scripts are pre-configured to work with Herd's Xdebug installation.

No additional setup required - just run the coverage commands below.

### For Other Environments

To generate code coverage reports, you need either **Xdebug** or **PCOV** installed:

#### Installing Xdebug (Recommended for development)

```bash
# macOS with Homebrew
brew install php@8.1-xdebug  # or your PHP version
# or
pecl install xdebug

# Ubuntu/Debian
sudo apt-get install php-xdebug

# Enable in php.ini
zend_extension=xdebug.so
xdebug.mode=coverage
```

#### Installing PCOV (Faster for CI/CD)

```bash
# Using PECL
pecl install pcov

# Enable in php.ini  
extension=pcov.so
pcov.enabled=1
```

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

# Run tests with verbose output
./vendor/bin/pest --verbose

# Run specific test file
./vendor/bin/pest tests/Unit/DatabaseTableConfigurationTest.php

# Run tests matching a pattern
./vendor/bin/pest --filter="RedirectController"
```

### Code Coverage Commands

```bash
# Generate coverage report in terminal
composer test:coverage

# Generate HTML coverage report (opens in browser)
composer test:coverage-html

# Generate Clover XML coverage report (for CI/CD)
composer test:coverage-clover

# Generate text coverage report
composer test:coverage-text

# Enforce minimum coverage threshold (80%)
composer test:min-coverage
```

## Code Coverage Reports

### HTML Report
The HTML report provides a visual, interactive coverage report:

```bash
composer test:coverage-html
# Open coverage-html/index.html in your browser
```

### Terminal Report
For quick coverage overview:

```bash
composer test:coverage-text
```

### CI/CD Integration
For automated testing pipelines:

```bash
composer test:coverage-clover
# Generates coverage.xml for services like CodeClimate, Coveralls, etc.
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

## Coverage Goals

- **Minimum Coverage**: 80% (enforced by `composer test:min-coverage`)
- **Good Coverage**: 90%+
- **Excellent Coverage**: 95%+

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

## Continuous Integration

For GitHub Actions, GitLab CI, or other CI services:

```yaml
- name: Run tests with coverage
  run: composer test:coverage-clover

- name: Upload coverage to Codecov
  uses: codecov/codecov-action@v1
  with:
    file: ./coverage.xml
```

## Troubleshooting

### "No code coverage driver available"
- Install Xdebug or PCOV (see Prerequisites above)
- Verify installation: `php -m | grep -E "(xdebug|pcov)"`

### Coverage reports are empty
- Ensure your tests are actually running your source code
- Check that the `src/` directory paths are correct in `pest.xml`

### Tests are slow with coverage
- Use PCOV instead of Xdebug for faster coverage collection
- Run coverage only when needed, not during development

## Additional Resources

- [Pest Documentation](https://pestphp.com/docs)
- [PHPUnit Code Coverage](https://phpunit.readthedocs.io/en/latest/code-coverage.html)
- [Xdebug Documentation](https://xdebug.org/docs/)