<?php

use Abra\AbraStatamicRedirect\Http\Middleware\RedirectMiddleware;
use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    // Mock the RedirectRepository
    $this->redirectRepository = Mockery::mock(RedirectRepository::class);

    // Sample redirect data
    $this->sampleRedirect = [
        'id' => '123',
        'source' => '/old-page',
        'destination' => '/new-page',
        'status_code' => 301,
    ];
});

describe('RedirectMiddleware', function (): void {
    test('middleware passes through requests when no redirect exists', function (): void {
        config(['redirects.cache.enabled' => false]);

        $this->redirectRepository
            ->shouldReceive('find')
            ->with('/test-path')
            ->once()
            ->andReturn(null);

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('/test-path');

        $response = $middleware->handle($request, fn($req): Response => new Response('Original content'));

        expect($response->getContent())->toBe('Original content');
        expect($response->getStatusCode())->toBe(200);
    });

    test('middleware redirects when matching redirect is found', function (): void {
        config(['redirects.cache.enabled' => false]);

        $this->redirectRepository
            ->shouldReceive('find')
            ->with('/old-page')
            ->once()
            ->andReturn([
                'source' => '/old-page',
                'destination' => '/new-page',
                'status_code' => 301,
            ]);

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('/old-page');

        $response = $middleware->handle($request, fn($req): Response => new Response('Should not reach here'));

        expect($response->getStatusCode())->toBe(301);
        expect($response->getTargetUrl())->toBe('http://localhost/new-page');
    });

    test('middleware skips CP routes', function (): void {
        config(['statamic.cp.route' => 'cp']);

        // Repository should not be called for CP routes
        $this->redirectRepository->shouldNotReceive('find');

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('/cp/redirects');

        $response = $middleware->handle($request, fn($req): Response => new Response('CP Content'));

        expect($response->getContent())->toBe('CP Content');
        expect($response->getStatusCode())->toBe(200);
    });

    test('middleware respects custom CP route configuration', function (): void {
        config(['statamic.cp.route' => 'admin']);

        // Repository should not be called for custom CP routes
        $this->redirectRepository->shouldNotReceive('find');

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('/admin/dashboard');

        $response = $middleware->handle($request, fn($req): Response => new Response('Admin Content'));

        expect($response->getContent())->toBe('Admin Content');
        expect($response->getStatusCode())->toBe(200);
    });

    test('middleware preserves query parameters in redirects', function (): void {
        config(['redirects.cache.enabled' => false]);

        $this->redirectRepository
            ->shouldReceive('find')
            ->with('/old-page')
            ->once()
            ->andReturn([
                'source' => '/old-page',
                'destination' => '/new-page',
                'status_code' => 302,
            ]);

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('/old-page?utm_source=test&page=1');

        $response = $middleware->handle($request, fn($req): Response => new Response('Should not reach here'));

        expect($response->getStatusCode())->toBe(302);
        $targetUrl = $response->getTargetUrl();
        expect($targetUrl)->toContain('http://localhost/new-page?');
        expect($targetUrl)->toContain('utm_source=test');
        expect($targetUrl)->toContain('page=1');
    });

    test('middleware appends query parameters when destination already has query string', function (): void {
        config(['redirects.cache.enabled' => false]);

        $this->redirectRepository
            ->shouldReceive('find')
            ->with('/old-page')
            ->once()
            ->andReturn([
                'source' => '/old-page',
                'destination' => '/new-page?existing=param',
                'status_code' => 301,
            ]);

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('/old-page?new=param');

        $response = $middleware->handle($request, fn($req): Response => new Response('Should not reach here'));

        expect($response->getStatusCode())->toBe(301);
        expect($response->getTargetUrl())->toBe('http://localhost/new-page?existing=param&new=param');
    });

    test('middleware normalizes root path correctly', function (): void {
        config(['redirects.cache.enabled' => false]);

        $this->redirectRepository
            ->shouldReceive('find')
            ->with('/')
            ->once()
            ->andReturn([
                'source' => '/',
                'destination' => '/home',
                'status_code' => 301,
            ]);

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('/');

        $response = $middleware->handle($request, fn($req): Response => new Response('Should not reach here'));

        expect($response->getStatusCode())->toBe(301);
        expect($response->getTargetUrl())->toBe('http://localhost/home');
    });

    test('middleware normalizes paths by adding leading slash', function (): void {
        config(['redirects.cache.enabled' => false]);

        $this->redirectRepository
            ->shouldReceive('find')
            ->with('/test-path')
            ->once()
            ->andReturn(null);

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('test-path'); // No leading slash

        $response = $middleware->handle($request, fn($req): Response => new Response('Original content'));

        expect($response->getContent())->toBe('Original content');
    });

    test('middleware caches redirect lookups when cache is enabled', function (): void {
        config([
            'redirects.cache.enabled' => true,
            'redirects.cache.expiry' => 60,
        ]);

        // Clear any existing cache
        Cache::flush();

        $redirect = [
            'source' => '/cached-page',
            'destination' => '/new-cached-page',
            'status_code' => 301,
        ];

        $this->redirectRepository
            ->shouldReceive('find')
            ->with('/cached-page')
            ->once()
            ->andReturn($redirect);

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('/cached-page');

        // First request - should call repository and cache result
        $response = $middleware->handle($request, fn($req): Response => new Response('Should not reach here'));

        expect($response->getStatusCode())->toBe(301);

        // Verify cache was set
        $cacheKey = 'redirect_for_path_'.md5('/cached-page');
        expect(Cache::has($cacheKey))->toBeTrue();
        expect(Cache::get($cacheKey))->toBe($redirect);
    });

    test('middleware uses cached redirects when available', function (): void {
        config([
            'redirects.cache.enabled' => true,
            'redirects.cache.expiry' => 60,
        ]);

        $redirect = [
            'source' => '/cached-page',
            'destination' => '/cached-destination',
            'status_code' => 302,
        ];

        // Pre-populate cache
        $cacheKey = 'redirect_for_path_'.md5('/cached-page');
        Cache::put($cacheKey, $redirect, 60);

        // Repository should NOT be called since we have cached data
        $this->redirectRepository->shouldNotReceive('find');

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('/cached-page');

        $response = $middleware->handle($request, fn($req): Response => new Response('Should not reach here'));

        expect($response->getStatusCode())->toBe(302);
        expect($response->getTargetUrl())->toBe('http://localhost/cached-destination');
    });

    test('middleware does not cache null results', function (): void {
        config([
            'redirects.cache.enabled' => true,
            'redirects.cache.expiry' => 60,
        ]);

        // Clear cache
        Cache::flush();

        $this->redirectRepository
            ->shouldReceive('find')
            ->with('/non-existent')
            ->once()
            ->andReturn(null);

        $middleware = new RedirectMiddleware($this->redirectRepository);
        $request = Request::create('/non-existent');

        $response = $middleware->handle($request, fn($req): Response => new Response('Original content'));

        expect($response->getContent())->toBe('Original content');

        // Verify null results are not cached
        $cacheKey = 'redirect_for_path_'.md5('/non-existent');
        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('middleware handles different status codes correctly', function (): void {
        config(['redirects.cache.enabled' => false]);

        $statusCodes = [301, 302, 307, 308];

        foreach ($statusCodes as $statusCode) {
            $this->redirectRepository
                ->shouldReceive('find')
                ->with('/test-' . $statusCode)
                ->once()
                ->andReturn([
                    'source' => '/test-' . $statusCode,
                    'destination' => '/new-' . $statusCode,
                    'status_code' => $statusCode,
                ]);

            $middleware = new RedirectMiddleware($this->redirectRepository);
            $request = Request::create('/test-' . $statusCode);

            $response = $middleware->handle($request, fn($req): Response => new Response('Should not reach here'));

            expect($response->getStatusCode())->toBe($statusCode);
            expect($response->getTargetUrl())->toBe('http://localhost/new-' . $statusCode);
        }
    });

    test('middleware constructor reads cache configuration correctly', function (): void {
        config([
            'redirects.cache.enabled' => true,
            'redirects.cache.expiry' => 120,
        ]);

        $middleware = new RedirectMiddleware($this->redirectRepository);

        // Use reflection to test private properties
        $reflection = new ReflectionClass($middleware);

        $cacheEnabledProperty = $reflection->getProperty('cache_enabled');
        $cacheEnabledProperty->setAccessible(true);

        expect($cacheEnabledProperty->getValue($middleware))->toBeTrue();

        $cacheExpiryProperty = $reflection->getProperty('cache_expiry');
        $cacheExpiryProperty->setAccessible(true);

        expect($cacheExpiryProperty->getValue($middleware))->toBe(120);
    });

    test('middleware uses default cache configuration when not set', function (): void {
        // Remove cache config to test defaults
        config(['redirects' => []]);

        $middleware = new RedirectMiddleware($this->redirectRepository);

        // Use reflection to test private properties
        $reflection = new ReflectionClass($middleware);

        $cacheEnabledProperty = $reflection->getProperty('cache_enabled');
        $cacheEnabledProperty->setAccessible(true);

        expect($cacheEnabledProperty->getValue($middleware))->toBeFalse(); // Default false

        $cacheExpiryProperty = $reflection->getProperty('cache_expiry');
        $cacheExpiryProperty->setAccessible(true);

        expect($cacheExpiryProperty->getValue($middleware))->toBe(60); // Default 60
    });
});
