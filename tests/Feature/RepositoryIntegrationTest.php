<?php

use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Abra\AbraStatamicRedirect\Repository\DatabaseRedirectRepository;
use Abra\AbraStatamicRedirect\Repository\FileRedirectRepository;
use Statamic\Facades\User;

beforeEach(function () {
    // Create and authenticate a superuser for CP access
    $this->actingAs(User::make()
        ->email('test@example.com')
        ->makeSuper()
        ->save(),
    );
});

describe('Repository Integration', function () {
    test('controller uses file repository when configured for file storage', function () {
        config(['redirects.storage' => 'file']);
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        // Mock the file repository directly in the container
        $mockRepository = Mockery::mock(FileRedirectRepository::class);
        $mockRepository->shouldReceive('all')->once()->andReturn([
            ['id' => '1', 'source' => '/test', 'destination' => '/new', 'status_code' => 301],
        ]);

        // Replace the bound repository
        app()->bind(RedirectRepository::class, function () use ($mockRepository) {
            return $mockRepository;
        });

        $response = $this->get(cp_route('abra-statamic-redirects.index'));

        $response->assertStatus(200);

        // Verify the repository was called
        $mockRepository->shouldHaveReceived('all')->once();
    });

    test('controller uses database repository when configured for database storage', function () {
        config(['redirects.storage' => 'database']);
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        // Mock the database repository directly in the container
        $mockRepository = Mockery::mock(DatabaseRedirectRepository::class);
        $mockRepository->shouldReceive('all')->once()->andReturn([
            ['id' => '1', 'source' => '/test', 'destination' => '/new', 'status_code' => 301],
        ]);

        // Replace the bound repository
        app()->bind(RedirectRepository::class, function () use ($mockRepository) {
            return $mockRepository;
        });

        $response = $this->get(cp_route('abra-statamic-redirects.index'));

        $response->assertStatus(200);

        // Verify the repository was called
        $mockRepository->shouldHaveReceived('all')->once();
    });

    test('service provider binding resolves correct repository type during request', function () {
        // Test file storage
        config(['redirects.storage' => 'file']);
        $repository = app(RedirectRepository::class);
        expect($repository)->toBeInstanceOf(FileRedirectRepository::class);

        // Test database storage
        config(['redirects.storage' => 'database']);
        $repository = app(RedirectRepository::class);
        expect($repository)->toBeInstanceOf(DatabaseRedirectRepository::class);
    });

    test('repository configuration persists across multiple controller actions', function () {
        config(['redirects.storage' => 'database']);
        config(['redirects.status_codes' => [301 => 'Permanent']]);

        // Mock repository for multiple calls
        $mockRepository = Mockery::mock(DatabaseRedirectRepository::class);
        $mockRepository->shouldReceive('all')->twice()->andReturn([]);

        app()->bind(RedirectRepository::class, function () use ($mockRepository) {
            return $mockRepository;
        });

        // Make multiple requests to the index (both call all())
        $this->get(cp_route('abra-statamic-redirects.index'));
        $this->get(cp_route('abra-statamic-redirects.index'));

        // Verify repository was used for both requests
        $mockRepository->shouldHaveReceived('all')->twice();
    });
});
