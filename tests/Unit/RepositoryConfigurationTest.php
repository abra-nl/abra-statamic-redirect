<?php

use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Abra\AbraStatamicRedirect\Repository\DatabaseRedirectRepository;
use Abra\AbraStatamicRedirect\Repository\FileRedirectRepository;

describe('Repository Configuration', function (): void {
    test('resolves file repository when storage is set to file', function (): void {
        config(['redirects.storage' => 'file']);

        $repository = app(RedirectRepository::class);

        expect($repository)->toBeInstanceOf(FileRedirectRepository::class);
    });

    test('resolves database repository when storage is set to database', function (): void {
        config(['redirects.storage' => 'database']);

        $repository = app(RedirectRepository::class);

        expect($repository)->toBeInstanceOf(DatabaseRedirectRepository::class);
    });

    test('defaults to file repository when storage config is not set', function (): void {
        config(['redirects.storage' => null]);

        $repository = app(RedirectRepository::class);

        expect($repository)->toBeInstanceOf(FileRedirectRepository::class);
    });

    test('defaults to file repository when storage config is invalid', function (): void {
        config(['redirects.storage' => 'invalid_storage_type']);

        $repository = app(RedirectRepository::class);

        expect($repository)->toBeInstanceOf(FileRedirectRepository::class);
    });

    test('repository binding creates new instances each time', function (): void {
        config(['redirects.storage' => 'file']);

        $repository1 = app(RedirectRepository::class);
        $repository2 = app(RedirectRepository::class);

        // Should be different instances but same type
        expect($repository1)->not->toBe($repository2);
        expect($repository1)->toBeInstanceOf(FileRedirectRepository::class);
        expect($repository2)->toBeInstanceOf(FileRedirectRepository::class);
    });

    test('repository type changes when config is changed at runtime', function (): void {
        // Start with file storage
        config(['redirects.storage' => 'file']);
        $fileRepository = app(RedirectRepository::class);
        expect($fileRepository)->toBeInstanceOf(FileRedirectRepository::class);

        // Clear the singleton binding
        app()->forgetInstance(RedirectRepository::class);

        // Change to database storage
        config(['redirects.storage' => 'database']);
        $databaseRepository = app(RedirectRepository::class);
        expect($databaseRepository)->toBeInstanceOf(DatabaseRedirectRepository::class);

        // Verify they are different instances
        expect($fileRepository)->not->toBe($databaseRepository);
    });
});
