<?php

use Abra\AbraStatamicRedirect\Http\Middleware\RedirectMiddleware;
use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Abra\AbraStatamicRedirect\Repository\DatabaseRedirectRepository;
use Abra\AbraStatamicRedirect\Repository\FileRedirectRepository;
use Abra\AbraStatamicRedirect\ServiceProvider;
use Illuminate\Support\Facades\File;
use Statamic\Facades\CP\Nav;

beforeEach(function () {
    // Mock Nav facade to prevent navigation extension errors
    Nav::shouldReceive('extend')->byDefault();

    // Create a fresh service provider instance
    $this->serviceProvider = new ServiceProvider($this->app);

    // Register the service provider to get default config
    $this->serviceProvider->register();
});

describe('ServiceProvider', function () {
    describe('repository binding', function () {
        test('binds FileRedirectRepository when storage is file', function () {
            config(['redirects.storage' => 'file']);

            $this->serviceProvider->bootAddon();

            $repository = $this->app->make(RedirectRepository::class);

            expect($repository)->toBeInstanceOf(FileRedirectRepository::class);
        });

        test('binds DatabaseRedirectRepository when storage is database', function () {
            config(['redirects.storage' => 'database']);

            $this->serviceProvider->bootAddon();

            $repository = $this->app->make(RedirectRepository::class);

            expect($repository)->toBeInstanceOf(DatabaseRedirectRepository::class);
        });

        test('defaults to FileRedirectRepository when storage config is null', function () {
            config(['redirects.storage' => null]);

            $this->serviceProvider->bootAddon();

            $repository = $this->app->make(RedirectRepository::class);

            expect($repository)->toBeInstanceOf(FileRedirectRepository::class);
        });

        test('defaults to FileRedirectRepository for invalid storage type', function () {
            config(['redirects.storage' => 'invalid_storage_type']);

            $this->serviceProvider->bootAddon();

            $repository = $this->app->make(RedirectRepository::class);

            expect($repository)->toBeInstanceOf(FileRedirectRepository::class);
        });

        test('resolves different instances each time when not singleton', function () {
            config(['redirects.storage' => 'file']);

            $this->serviceProvider->bootAddon();

            $repository1 = $this->app->make(RedirectRepository::class);
            $repository2 = $this->app->make(RedirectRepository::class);

            expect($repository1)->not->toBe($repository2);
        });
    });

    describe('configuration', function () {
        test('registers configuration correctly', function () {
            $this->serviceProvider->register();

            // Test default configuration values are merged
            expect(config('redirects.storage'))->toBe('file')
                ->and(config('redirects.file_path'))->toContain('redirects.yaml')
                ->and(config('redirects.table'))->toBe('redirects')
                ->and(config('redirects.cache.enabled'))->toBeTrue()
                ->and(config('redirects.cache.expiry_time'))->toBe(60);
        });

        test('custom config values override defaults', function () {
            // Set custom config before registering
            config([
                'redirects.storage' => 'custom',
                'redirects.cache.enabled' => false,
            ]);

            $this->serviceProvider->register();

            // Custom values should be preserved
            expect(config('redirects.storage'))->toBe('custom')
                ->and(config('redirects.cache.enabled'))->toBeFalse();
        });

        test('publishes config file with correct tag', function () {
            $this->serviceProvider->bootAddon();

            // Verify config publishing is set up correctly
            $publishes = ServiceProvider::pathsToPublish(ServiceProvider::class, 'config');

            expect($publishes)->not->toBeEmpty();

            // Check if the config file is in the publishing paths
            $configFound = false;
            foreach (array_keys($publishes) as $source) {
                if (str_contains($source, 'redirects.php')) {
                    $configFound = true;
                    break;
                }
            }

            expect($configFound)->toBeTrue();
        });
    });

    describe('middleware registration', function () {
        test('registers RedirectMiddleware in statamic.web group', function () {
            // Access the middleware groups property
            $reflection = new ReflectionClass($this->serviceProvider);
            $middlewareProperty = $reflection->getProperty('middlewareGroups');
            $middlewareGroups = $middlewareProperty->getValue($this->serviceProvider);

            expect($middlewareGroups)->toHaveKey('statamic.web')
                ->and($middlewareGroups['statamic.web'])->toContain(RedirectMiddleware::class);
        });
    });

    describe('navigation extension', function () {
        test('extends Statamic CP navigation with Redirects item', function () {
            // Mock Nav facade to capture extension calls
            Nav::shouldReceive('extend')
                ->once()
                ->with(Closure::class);

            $this->serviceProvider->bootAddon();
        });

        test('navigation item has correct properties', function () {
            // Capture the navigation extension closure
            $capturedClosure = null;

            Nav::shouldReceive('extend')
                ->once()
                ->with(Closure::class)
                ->andReturnUsing(function ($closure) use (&$capturedClosure) {
                    $capturedClosure = $closure;
                });

            $this->serviceProvider->bootAddon();

            // Mock nav builder to test the closure
            $navBuilder = Mockery::mock();
            $navItem = Mockery::mock();

            $navBuilder->shouldReceive('create')
                ->once()
                ->with('Redirects')
                ->andReturn($navItem);

            $navItem->shouldReceive('section')
                ->once()
                ->with('Settings')
                ->andReturn($navItem);

            $navItem->shouldReceive('route')
                ->once()
                ->with('statamic.cp.abra-statamic-redirects.index')
                ->andReturn($navItem);

            $navItem->shouldReceive('icon')
                ->once()
                ->with('list')
                ->andReturn($navItem);

            // Execute the captured closure
            $capturedClosure($navBuilder);
        });
    });

    describe('view loading', function () {
        test('loads views from correct namespace and path', function () {
            $this->serviceProvider->bootAddon();

            // Verify views are loaded with correct namespace
            $hints = $this->app['view']->getFinder()->getHints();

            expect($hints)->toHaveKey('abra-redirects');

            // Check if any view path contains 'resources/views'
            $viewPathFound = false;
            foreach ($hints['abra-redirects'] as $path) {
                if (str_contains($path, 'resources/views')) {
                    $viewPathFound = true;
                    break;
                }
            }

            expect($viewPathFound)->toBeTrue();
        });

        test('view files exist in the specified directory', function () {
            $viewsPath = __DIR__.'/../../resources/views';

            expect(File::exists($viewsPath.'/index.blade.php'))->toBeTrue()
                ->and(File::exists($viewsPath.'/edit.blade.php'))->toBeTrue()
                ->and(File::exists($viewsPath.'/create.blade.php'))->toBeTrue()
                ->and(File::exists($viewsPath.'/components/form.blade.php'))->toBeTrue();
        });
    });

    describe('migration publishing', function () {
        test('publishes migrations to correct directory', function () {
            $this->serviceProvider->bootAddon();

            // Verify migration publishing is set up correctly
            $publishes = ServiceProvider::pathsToPublish(ServiceProvider::class);

            // Check if migrations directory is published
            $foundMigrationPublish = false;
            foreach ($publishes as $source => $destination) {
                if (str_contains($source, 'database/migrations') && str_contains($destination, 'database/migrations')) {
                    $foundMigrationPublish = true;
                    break;
                }
            }

            expect($foundMigrationPublish)->toBeTrue();
        });

        test('migration files exist in source directory', function () {
            $migrationsPath = __DIR__.'/../../database/migrations';

            expect(File::isDirectory($migrationsPath))->toBeTrue();

            // Check for migration files (they should exist)
            $migrationFiles = File::glob($migrationsPath.'/*.php');
            expect($migrationFiles)->not->toBeEmpty();
        });
    });

    describe('integration tests', function () {
        test('all services are properly registered and bootable', function () {
            // Boot the service provider (register was already called in beforeEach)
            $this->serviceProvider->bootAddon();

            // Verify core services work together
            $repository = $this->app->make(RedirectRepository::class);
            expect($repository)->toBeInstanceOf(FileRedirectRepository::class);

            // Verify configuration is available
            expect(config('redirects.storage'))->toBe('file');

            // Verify views are loadable
            $hints = $this->app['view']->getFinder()->getHints();
            expect($hints)->toHaveKey('abra-redirects');
        });

        test('switching storage types works correctly', function () {
            // Test file storage
            config(['redirects.storage' => 'file']);
            $this->serviceProvider->bootAddon();
            $fileRepo = $this->app->make(RedirectRepository::class);
            expect($fileRepo)->toBeInstanceOf(FileRedirectRepository::class);

            // Reset binding
            $this->app->forgetInstance(RedirectRepository::class);

            // Test database storage
            config(['redirects.storage' => 'database']);
            $this->serviceProvider->bootAddon();
            $dbRepo = $this->app->make(RedirectRepository::class);
            expect($dbRepo)->toBeInstanceOf(DatabaseRedirectRepository::class);
        });
    });
});
