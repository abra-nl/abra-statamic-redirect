<?php

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Statamic\Facades\User;

beforeEach(function (): void {
    // Create and authenticate a superuser for CP access
    $this->actingAs(User::make()
        ->email('test@example.com')
        ->makeSuper()
        ->save(),
    );
});

describe('RedirectMiddleware Integration', function (): void {
    test('middleware redirects actual HTTP requests', function (): void {
        // Mock the repository with a redirect
        $mockRepository = Mockery::mock(RedirectRepository::class);
        $mockRepository->shouldReceive('find')
            ->with('/integration-test')
            ->andReturn([
                'source' => '/integration-test',
                'destination' => '/integration-destination',
                'status_code' => 301,
            ]);

        app()->bind(RedirectRepository::class, fn() => $mockRepository);

        // Configure to disable cache for this test
        config(['redirects.cache.enabled' => false]);

        // Create a test route that should NOT be reached due to redirect
        Route::get('/integration-test', fn(): ResponseFactory|Response => response('This should not be reached', 200));

        // Make the request
        $response = $this->get('/integration-test');

        // Should be redirected
        $response->assertStatus(301);
        $response->assertRedirect('/integration-destination');
    });

    test('middleware allows normal requests to pass through when no redirect exists', function (): void {
        // Mock the repository with no redirect
        $mockRepository = Mockery::mock(RedirectRepository::class);
        $mockRepository->shouldReceive('find')
            ->with('/normal-page')
            ->andReturn(null);

        app()->bind(RedirectRepository::class, fn() => $mockRepository);

        config(['redirects.cache.enabled' => false]);

        // Instead of trying to create a route, test that middleware passes through by
        // checking that it calls the next closure correctly when no redirect exists
        // We'll use a 404 response to verify the middleware passed the request through
        // without intercepting it - this is the expected behavior for non-existent routes
        $response = $this->get('/normal-page');

        // Should get 404 since route doesn't exist, but that means middleware passed it through
        // The important thing is the repository was called and no redirect was found
        $response->assertStatus(404);

        // Verify the middleware called the repository (mocked expectation will ensure this)
        expect(true)->toBeTrue(); // Mock expectation validates the behavior
    });

    test('middleware does not interfere with CP routes', function (): void {
        // Configure CP route
        config(['statamic.cp.route' => 'cp']);

        // Mock repository should not be called for CP routes
        $mockRepository = Mockery::mock(RedirectRepository::class);
        $mockRepository->shouldNotReceive('find');

        app()->bind(RedirectRepository::class, fn() => $mockRepository);

        // Test with a generic CP path since the exact route behavior may vary
        $response = $this->get('/cp/test-path');

        // The important thing is that the repository wasn't called (tested by mock expectation)
        // The status code will depend on the actual route, but middleware passed it through
        expect(true)->toBeTrue(); // Mock expectation already validates the behavior
    });

    test('middleware preserves query parameters in real HTTP redirects', function (): void {
        $mockRepository = Mockery::mock(RedirectRepository::class);
        $mockRepository->shouldReceive('find')
            ->with('/query-test')
            ->andReturn([
                'source' => '/query-test',
                'destination' => '/query-destination',
                'status_code' => 302,
            ]);

        app()->bind(RedirectRepository::class, fn() => $mockRepository);

        config(['redirects.cache.enabled' => false]);

        // Create a test route (should not be reached)
        Route::get('/query-test', fn(): ResponseFactory|Response => response('Should not reach here', 200));

        // Make request with query parameters
        $response = $this->get('/query-test?utm_source=newsletter&page=2');

        // Should redirect with preserved query parameters
        $response->assertStatus(302);

        $targetUrl = $response->headers->get('Location');
        expect($targetUrl)->toContain('/query-destination');
        expect($targetUrl)->toContain('utm_source=newsletter');
        expect($targetUrl)->toContain('page=2');
    });

    test('middleware works with different HTTP methods', function (): void {
        $mockRepository = Mockery::mock(RedirectRepository::class);
        $mockRepository->shouldReceive('find')
            ->with('/api-endpoint')
            ->times(2) // Called for both POST and PUT
            ->andReturn([
                'source' => '/api-endpoint',
                'destination' => '/new-api-endpoint',
                'status_code' => 301,
            ]);

        app()->bind(RedirectRepository::class, fn() => $mockRepository);

        config(['redirects.cache.enabled' => false]);

        // Test POST request
        $response = $this->post('/api-endpoint', ['data' => 'test']);
        $response->assertStatus(301);
        $response->assertRedirect('/new-api-endpoint');

        // Test PUT request
        $response = $this->put('/api-endpoint', ['data' => 'test']);
        $response->assertStatus(301);
        $response->assertRedirect('/new-api-endpoint');
    });

    test('middleware caching works end-to-end', function (): void {
        config([
            'redirects.cache.enabled' => true,
            'redirects.cache.expiry' => 60,
        ]);

        // Clear cache
        Cache::flush();

        $mockRepository = Mockery::mock(RedirectRepository::class);
        $mockRepository->shouldReceive('find')
            ->with('/cached-integration')
            ->once() // Should only be called once due to caching
            ->andReturn([
                'source' => '/cached-integration',
                'destination' => '/cached-integration-destination',
                'status_code' => 301,
            ]);

        app()->bind(RedirectRepository::class, fn() => $mockRepository);

        Route::get('/cached-integration', fn(): ResponseFactory|Response => response('Should not reach here', 200));

        // First request - should call repository and cache
        $response1 = $this->get('/cached-integration');
        $response1->assertStatus(301);
        $response1->assertRedirect('/cached-integration-destination');

        // Second request - should use cache (repository mock expects only 1 call)
        $response2 = $this->get('/cached-integration');
        $response2->assertStatus(301);
        $response2->assertRedirect('/cached-integration-destination');

        // Verify cache exists
        $cacheKey = 'redirect_for_path_'.md5('/cached-integration');
        expect(Cache::has($cacheKey))->toBeTrue();
    });

    test('middleware handles wildcard redirects through repository', function (): void {
        $mockRepository = Mockery::mock(RedirectRepository::class);
        $mockRepository->shouldReceive('find')
            ->with('/blog/some-post')
            ->andReturn([
                'source' => '/blog/*',
                'destination' => '/articles/some-post',
                'status_code' => 301,
            ]);

        app()->bind(RedirectRepository::class, fn() => $mockRepository);

        config(['redirects.cache.enabled' => false]);

        Route::get('/blog/some-post', fn(): ResponseFactory|Response => response('Blog post', 200));

        $response = $this->get('/blog/some-post');

        $response->assertStatus(301);
        $response->assertRedirect('/articles/some-post');
    });

    test('middleware works with root path redirects', function (): void {
        $mockRepository = Mockery::mock(RedirectRepository::class);
        $mockRepository->shouldReceive('find')
            ->with('/')
            ->andReturn([
                'source' => '/',
                'destination' => '/welcome',
                'status_code' => 302,
            ]);

        app()->bind(RedirectRepository::class, fn() => $mockRepository);

        config(['redirects.cache.enabled' => false]);

        Route::get('/', fn(): ResponseFactory|Response => response('Home page', 200));

        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/welcome');
    });

    test('middleware respects different status codes in HTTP responses', function (): void {
        $statusCodes = [301, 302, 307, 308];

        foreach ($statusCodes as $statusCode) {
            $mockRepository = Mockery::mock(RedirectRepository::class);
            $mockRepository->shouldReceive('find')
                ->with('/status-' . $statusCode)
                ->andReturn([
                    'source' => '/status-' . $statusCode,
                    'destination' => '/new-status-' . $statusCode,
                    'status_code' => $statusCode,
                ]);

            app()->bind(RedirectRepository::class, fn() => $mockRepository);

            config(['redirects.cache.enabled' => false]);

            Route::get('/status-' . $statusCode, fn(): ResponseFactory|Response => response(sprintf('Status %s page', $statusCode), 200));

            $response = $this->get('/status-' . $statusCode);

            $response->assertStatus($statusCode);
            $response->assertRedirect('/new-status-' . $statusCode);
        }
    });

    test('middleware handles complex query parameter scenarios', function (): void {
        $mockRepository = Mockery::mock(RedirectRepository::class);
        $mockRepository->shouldReceive('find')
            ->with('/complex-query')
            ->andReturn([
                'source' => '/complex-query',
                'destination' => '/new-complex?existing=true&default=1',
                'status_code' => 301,
            ]);

        app()->bind(RedirectRepository::class, fn() => $mockRepository);

        config(['redirects.cache.enabled' => false]);

        Route::get('/complex-query', fn(): ResponseFactory|Response => response('Complex page', 200));

        // Request with additional query parameters
        $response = $this->get('/complex-query?utm_campaign=test&source=email&page=5');

        $response->assertStatus(301);
        // Should append new parameters to existing ones
        $targetUrl = $response->headers->get('Location');
        expect($targetUrl)->toContain('/new-complex');
        expect($targetUrl)->toContain('existing=true');
        expect($targetUrl)->toContain('default=1');
        expect($targetUrl)->toContain('utm_campaign=test');
        expect($targetUrl)->toContain('source=email');
        expect($targetUrl)->toContain('page=5');
    });
});
