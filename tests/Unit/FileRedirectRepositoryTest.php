<?php

use Carbon\Carbon;
use Abra\AbraStatamicRedirect\Repository\FileRedirectRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;

beforeEach(function (): void {
    // Create a temporary directory for test files
    $this->testDir = storage_path('tests/redirects');
    $this->testFile = $this->testDir.'/redirects.yaml';

    // Ensure clean state
    if (File::exists($this->testDir)) {
        File::deleteDirectory($this->testDir);
    }

    // Configure the test environment
    config([
        'redirects.file_path' => $this->testFile,
        'redirects.cache_enabled' => false, // Default to cache disabled for most tests
        'redirects.cache_expiry' => 60,
    ]);

    // Clear cache before each test
    Cache::flush();
});

afterEach(function (): void {
    // Clean up test files
    if (File::exists($this->testDir)) {
        File::deleteDirectory($this->testDir);
    }

    // Clear cache after each test
    Cache::flush();
});

describe('FileRedirectRepository', function (): void {
    describe('constructor', function (): void {
        test('creates directory and file if they do not exist', function (): void {
            expect(File::exists($this->testDir))->toBeFalse()
                ->and(File::exists($this->testFile))->toBeFalse();

            new FileRedirectRepository;

            expect(File::exists($this->testDir))->toBeTrue()
                ->and(File::exists($this->testFile))->toBeTrue()
                ->and(File::get($this->testFile))->toBe("# Redirects\n");
        });

        test('does not overwrite existing file', function (): void {
            File::makeDirectory($this->testDir, 0755, true);
            File::put($this->testFile, "existing content\n");

            new FileRedirectRepository;

            expect(File::get($this->testFile))->toBe("existing content\n");
        });

        test('reads cache configuration correctly', function (): void {
            config([
                'redirects.cache_enabled' => true,
                'redirects.cache_expiry' => 120,
            ]);

            $repository = new FileRedirectRepository;

            // Use reflection to test private properties
            $reflection = new ReflectionClass($repository);

            $cacheEnabledProperty = $reflection->getProperty('cache_enabled');
            expect($cacheEnabledProperty->getValue($repository))->toBeTrue();

            $cacheExpiryProperty = $reflection->getProperty('cache_expiry');
            expect($cacheExpiryProperty->getValue($repository))->toBe(120);
        });
    });

    describe('all', function (): void {
        test('returns empty array for empty file', function (): void {
            $repository = new FileRedirectRepository;

            expect($repository->all())->toBe([]);
        });

        test('returns redirects from YAML file', function (): void {
            $redirects = [
                [
                    'id' => 'test-1',
                    'source' => '/old-page',
                    'destination' => '/new-page',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
                [
                    'id' => 'test-2',
                    'source' => '/another-old',
                    'destination' => '/another-new',
                    'status_code' => 302,
                    'created_at' => '2023-01-02T00:00:00+00:00',
                    'updated_at' => '2023-01-02T00:00:00+00:00',
                ],
            ];

            File::makeDirectory($this->testDir, 0755, true);
            File::put($this->testFile, YAML::dump($redirects));

            $repository = new FileRedirectRepository;

            expect($repository->all())->toBe($redirects);
        });

        test('handles corrupted YAML file gracefully', function (): void {
            File::makeDirectory($this->testDir, 0755, true);
            File::put($this->testFile, 'invalid: yaml: content: [[[');

            $repository = new FileRedirectRepository;

            expect($repository->all())->toBe([]);
        });

        test('caches results when cache is enabled', function (): void {
            config(['redirects.cache_enabled' => true]);

            $redirects = [
                [
                    'id' => 'cached-test',
                    'source' => '/cached',
                    'destination' => '/new-cached',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
            ];

            File::makeDirectory($this->testDir, 0755, true);
            File::put($this->testFile, YAML::dump($redirects));

            $repository = new FileRedirectRepository;

            // First call should cache the result
            $result1 = $repository->all();
            expect($result1)->toBe($redirects);
            expect(Cache::has('redirects.all'))->toBeTrue();

            // Modify file content
            File::put($this->testFile, YAML::dump([]));

            // Second call should return cached result
            $result2 = $repository->all();
            expect($result2)->toBe($redirects); // Still the cached version
        });

        test('uses cached results when available', function (): void {
            config(['redirects.cache_enabled' => true]);

            $cachedRedirects = [
                [
                    'id' => 'cached-redirect',
                    'source' => '/from-cache',
                    'destination' => '/to-cache',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
            ];

            // Pre-populate cache
            Cache::put('redirects.all', $cachedRedirects, 60);

            $repository = new FileRedirectRepository;

            // Should return cached results without reading file
            expect($repository->all())->toBe($cachedRedirects);
        });
    });

    describe('find', function (): void {
        beforeEach(function (): void {
            $this->redirects = [
                [
                    'id' => 'exact-1',
                    'source' => '/exact-match',
                    'destination' => '/exact-destination',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
                [
                    'id' => 'wildcard-1',
                    'source' => '/blog/*',
                    'destination' => '/articles/{slug}',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
                [
                    'id' => 'trailing-slash',
                    'source' => '/with-slash/',
                    'destination' => '/without-slash',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
            ];

            File::makeDirectory($this->testDir, 0755, true);
            File::put($this->testFile, YAML::dump($this->redirects));
        });

        test('finds exact matches', function (): void {
            $repository = new FileRedirectRepository;

            $result = $repository->find('/exact-match');

            expect($result)->toBe($this->redirects[0]);
        });

        test('normalizes URLs for matching', function (): void {
            $repository = new FileRedirectRepository;

            // Should find match despite trailing slash difference
            $result = $repository->find('/with-slash');

            expect($result)->toBe($this->redirects[2]);
        });

        test('finds wildcard matches', function (): void {
            $repository = new FileRedirectRepository;

            $result = $repository->find('/blog/my-post');

            expect($result)->toBe($this->redirects[1]);
        });

        test('returns null when no match is found', function (): void {
            $repository = new FileRedirectRepository;

            $result = $repository->find('/non-existent-page');

            expect($result)->toBeNull();
        });

        test('prioritizes exact matches over wildcard matches', function (): void {
            $redirects = [
                [
                    'id' => 'wildcard-first',
                    'source' => '/blog/*',
                    'destination' => '/articles/{slug}',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
                [
                    'id' => 'exact-second',
                    'source' => '/blog/special',
                    'destination' => '/special-article',
                    'status_code' => 302,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
            ];

            File::put($this->testFile, YAML::dump($redirects));
            $repository = new FileRedirectRepository;

            // Should find exact match, not wildcard
            $result = $repository->find('/blog/special');

            expect($result['id'])->toBe('exact-second');
        });

        test('handles multiple wildcard patterns', function (): void {
            $redirects = [
                [
                    'id' => 'api-wildcard',
                    'source' => '/api/*/users',
                    'destination' => '/v2/api/{version}/users',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
                [
                    'id' => 'files-wildcard',
                    'source' => '/files/*.jpg',
                    'destination' => '/images/{filename}.jpg',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
            ];

            File::put($this->testFile, YAML::dump($redirects));
            $repository = new FileRedirectRepository;

            expect($repository->find('/api/v1/users')['id'])->toBe('api-wildcard')
                ->and($repository->find('/files/photo.jpg')['id'])->toBe('files-wildcard')
                ->and($repository->find('/files/photo.png'))->toBeNull();
        });
    });

    describe('store', function (): void {
        test('adds new redirect to empty file', function (): void {
            $repository = new FileRedirectRepository;

            $data = [
                'source' => '/new-source',
                'destination' => '/new-destination',
                'status_code' => 302,
            ];

            $result = $repository->store($data);

            expect($result)->toHaveKeys(['id', 'source', 'destination', 'status_code', 'created_at', 'updated_at'])
                ->and($result['source'])->toBe('/new-source')
                ->and($result['destination'])->toBe('/new-destination')
                ->and($result['status_code'])->toBe(302)
                ->and($result['id'])->toBeString();

            // Verify it was saved to file
            $allRedirects = $repository->all();
            expect($allRedirects)->toHaveCount(1);
            expect($allRedirects[0]['id'])->toBe($result['id']);
        });

        test('adds redirect to existing redirects', function (): void {
            $existingRedirects = [
                [
                    'id' => 'existing-1',
                    'source' => '/existing',
                    'destination' => '/existing-dest',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
            ];

            File::makeDirectory($this->testDir, 0755, true);
            File::put($this->testFile, YAML::dump($existingRedirects));

            $repository = new FileRedirectRepository;

            $data = [
                'source' => '/new-addition',
                'destination' => '/new-addition-dest',
            ];

            $result = $repository->store($data);

            expect($result['status_code'])->toBe(301); // Default value

            $allRedirects = $repository->all();
            expect($allRedirects)->toHaveCount(2);
            expect($allRedirects[1]['id'])->toBe($result['id']);
        });

        test('generates unique UUID for each redirect', function (): void {
            $repository = new FileRedirectRepository;

            $result1 = $repository->store([
                'source' => '/test-1',
                'destination' => '/dest-1',
            ]);

            $result2 = $repository->store([
                'source' => '/test-2',
                'destination' => '/dest-2',
            ]);

            expect($result1['id'])->toBeString()
                ->and($result2['id'])->toBeString()
                ->and($result1['id'])->not->toBe($result2['id']);
        });

        test('sets timestamps on creation', function (): void {
            $repository = new FileRedirectRepository;

            $before = now()->subSecond();
            $result = $repository->store([
                'source' => '/timestamp-test',
                'destination' => '/timestamp-dest',
            ]);
            $after = now()->addSecond();

            expect($result['created_at'])->toBeString()
                ->and($result['updated_at'])->toBeString()
                ->and($result['created_at'])->toBe($result['updated_at']);

            $createdAt = Carbon::parse($result['created_at']);
            expect($createdAt->between($before, $after))->toBeTrue();
        });

        test('clears cache when cache is enabled', function (): void {
            config(['redirects.cache_enabled' => true]);

            $repository = new FileRedirectRepository;

            // Pre-populate cache
            Cache::put('redirects.all', [], 60);
            expect(Cache::has('redirects.all'))->toBeTrue();

            $repository->store([
                'source' => '/cache-clear-test',
                'destination' => '/cache-clear-dest',
            ]);

            expect(Cache::has('redirects.all'))->toBeFalse();
        });
    });

    describe('update', function (): void {
        beforeEach(function (): void {
            $this->existingRedirects = [
                [
                    'id' => 'update-test-1',
                    'source' => '/original-source',
                    'destination' => '/original-dest',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
                [
                    'id' => 'update-test-2',
                    'source' => '/another-source',
                    'destination' => '/another-dest',
                    'status_code' => 302,
                    'created_at' => '2023-01-02T00:00:00+00:00',
                    'updated_at' => '2023-01-02T00:00:00+00:00',
                ],
            ];

            File::makeDirectory($this->testDir, 0755, true);
            File::put($this->testFile, YAML::dump($this->existingRedirects));
        });

        test('updates existing redirect completely', function (): void {
            $repository = new FileRedirectRepository;

            $updateData = [
                'source' => '/updated-source',
                'destination' => '/updated-dest',
                'status_code' => 307,
            ];

            $result = $repository->update('update-test-1', $updateData);

            expect($result['id'])->toBe('update-test-1')
                ->and($result['source'])->toBe('/updated-source')
                ->and($result['destination'])->toBe('/updated-dest')
                ->and($result['status_code'])->toBe(307)
                ->and($result['created_at'])->toBe('2023-01-01T00:00:00+00:00')
                ->and($result['updated_at'])->not->toBe('2023-01-01T00:00:00+00:00');
            // Unchanged
            // Updated

            // Verify persistence
            $allRedirects = $repository->all();
            expect($allRedirects[0])->toBe($result);
        });

        test('updates redirect partially', function (): void {
            $repository = new FileRedirectRepository;

            $updateData = [
                'destination' => '/partially-updated-dest',
            ];

            $result = $repository->update('update-test-1', $updateData);

            expect($result['id'])->toBe('update-test-1')
                ->and($result['source'])->toBe('/original-source') // Unchanged
                ->and($result['destination'])->toBe('/partially-updated-dest') // Changed
                ->and($result['status_code'])->toBe(301); // Unchanged
        });

        test('updates timestamp on modification', function (): void {
            $repository = new FileRedirectRepository;

            $before = now()->subSecond();
            $result = $repository->update('update-test-1', ['destination' => '/new-dest']);
            $after = now()->addSecond();

            expect($result['created_at'])->toBe('2023-01-01T00:00:00+00:00'); // Unchanged

            $updatedAt = Carbon::parse($result['updated_at']);
            expect($updatedAt->between($before, $after))->toBeTrue();
        });

        test('returns empty array when ID not found', function (): void {
            $repository = new FileRedirectRepository;

            $result = $repository->update('non-existent-id', [
                'destination' => '/some-dest',
            ]);

            expect($result)->toBe([]);

            // Verify no changes to file
            $allRedirects = $repository->all();
            expect($allRedirects)->toBe($this->existingRedirects);
        });

        test('clears cache when cache is enabled', function (): void {
            config(['redirects.cache_enabled' => true]);

            $repository = new FileRedirectRepository;

            // Load existing redirects to populate cache
            $initialRedirects = $repository->all();
            expect(Cache::has('redirects.all'))->toBeTrue();

            $repository->update('update-test-1', ['destination' => '/new-dest']);

            expect(Cache::has('redirects.all'))->toBeFalse();
        });
    });

    describe('delete', function (): void {
        beforeEach(function (): void {
            $this->redirectsForDeletion = [
                [
                    'id' => 'delete-test-1',
                    'source' => '/delete-me',
                    'destination' => '/delete-dest',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
                [
                    'id' => 'delete-test-2',
                    'source' => '/keep-me',
                    'destination' => '/keep-dest',
                    'status_code' => 302,
                    'created_at' => '2023-01-02T00:00:00+00:00',
                    'updated_at' => '2023-01-02T00:00:00+00:00',
                ],
                [
                    'id' => 'delete-test-3',
                    'source' => '/also-keep',
                    'destination' => '/also-keep-dest',
                    'status_code' => 301,
                    'created_at' => '2023-01-03T00:00:00+00:00',
                    'updated_at' => '2023-01-03T00:00:00+00:00',
                ],
            ];

            File::makeDirectory($this->testDir, 0755, true);
            File::put($this->testFile, YAML::dump($this->redirectsForDeletion));
        });

        test('deletes existing redirect successfully', function (): void {
            $repository = new FileRedirectRepository;

            $result = $repository->delete('delete-test-1');

            expect($result)->toBeTrue();

            $remaining = $repository->all();
            expect($remaining)->toHaveCount(2);
            expect($remaining[0]['id'])->toBe('delete-test-2');
            expect($remaining[1]['id'])->toBe('delete-test-3');
        });

        test('returns false when ID not found', function (): void {
            $repository = new FileRedirectRepository;

            $result = $repository->delete('non-existent-id');

            expect($result)->toBeFalse();

            // Verify no changes
            $allRedirects = $repository->all();
            expect($allRedirects)->toHaveCount(3);
        });

        test('re-indexes array after deletion', function (): void {
            $repository = new FileRedirectRepository;

            // Delete middle item
            $repository->delete('delete-test-2');

            $remaining = $repository->all();
            expect($remaining)->toHaveCount(2);

            // Verify array is properly indexed (no gaps)
            expect(array_keys($remaining))->toBe([0, 1]);
        });

        test('clears cache when cache is enabled', function (): void {
            config(['redirects.cache_enabled' => true]);

            $repository = new FileRedirectRepository;

            // Load existing redirects to populate cache
            $initialRedirects = $repository->all();
            expect(Cache::has('redirects.all'))->toBeTrue();

            $repository->delete('delete-test-1');

            expect(Cache::has('redirects.all'))->toBeFalse();
        });
    });

    describe('exists', function (): void {
        beforeEach(function (): void {
            $this->redirectsForExistence = [
                [
                    'id' => 'exists-test-1',
                    'source' => '/existing-source',
                    'destination' => '/existing-dest',
                    'status_code' => 301,
                    'created_at' => '2023-01-01T00:00:00+00:00',
                    'updated_at' => '2023-01-01T00:00:00+00:00',
                ],
                [
                    'id' => 'exists-test-2',
                    'source' => '/another-source/',  // With trailing slash
                    'destination' => '/another-dest',
                    'status_code' => 302,
                    'created_at' => '2023-01-02T00:00:00+00:00',
                    'updated_at' => '2023-01-02T00:00:00+00:00',
                ],
            ];

            File::makeDirectory($this->testDir, 0755, true);
            File::put($this->testFile, YAML::dump($this->redirectsForExistence));
        });

        test('returns true for existing source', function (): void {
            $repository = new FileRedirectRepository;

            expect($repository->exists('/existing-source'))->toBeTrue();
        });

        test('returns false for non-existing source', function (): void {
            $repository = new FileRedirectRepository;

            expect($repository->exists('/non-existing-source'))->toBeFalse();
        });

        test('normalizes URLs for existence check', function (): void {
            $repository = new FileRedirectRepository;

            // Should find match despite trailing slash difference
            expect($repository->exists('/another-source'))->toBeTrue(); // No trailing slash
            expect($repository->exists('/another-source/'))->toBeTrue(); // With trailing slash
        });

        test('excludes specific ID from check', function (): void {
            $repository = new FileRedirectRepository;

            expect($repository->exists('/existing-source'))->toBeTrue() // Without exclusion - should exist
                ->and($repository->exists('/existing-source', 'exists-test-1'))->toBeFalse() // With exclusion - should not exist (excluded)
                ->and($repository->exists('/existing-source', 'different-id'))->toBeTrue(); // Different ID exclusion - should still exist
        });

        test('handles empty redirects file', function (): void {
            File::put($this->testFile, YAML::dump([]));

            $repository = new FileRedirectRepository;

            expect($repository->exists('/any-source'))->toBeFalse();
        });
    });

    describe('file operations error handling', function (): void {
        test('handles missing file directory gracefully', function (): void {
            // Use a path that doesn't exist
            $nonExistentPath = '/tmp/non-existent-dir/redirects.yaml';

            config(['redirects.file_path' => $nonExistentPath]);

            // Should not throw exception
            $repository = new FileRedirectRepository;

            expect($repository->all())->toBe([]);
        });

        test('handles file read errors gracefully', function (): void {
            // Create a file that will cause read issues
            File::makeDirectory($this->testDir, 0755, true);
            File::put($this->testFile, 'invalid yaml content: [[[[[');

            $repository = new FileRedirectRepository;

            // Should return empty array instead of throwing
            expect($repository->all())->toBe([]);
        });

        test('creates initial file with proper content', function (): void {
            $repository = new FileRedirectRepository;

            $content = File::get($this->testFile);
            expect($content)->toBe("# Redirects\n");
        });
    });

    describe('caching integration', function (): void {
        test('invalidates cache on all write operations', function (): void {
            config(['redirects.cache_enabled' => true]);

            $repository = new FileRedirectRepository;

            // Test store clears cache
            $repository->store(['source' => '/test1', 'destination' => '/dest1']);

            // Load to populate cache
            $redirects = $repository->all();
            expect(Cache::has('redirects.all'))->toBeTrue();

            // Test update clears cache
            $created = $repository->store(['source' => '/cache-test', 'destination' => '/cache-dest']);
            $repository->all(); // Populate cache
            expect(Cache::has('redirects.all'))->toBeTrue();
            $repository->update($created['id'], ['destination' => '/new-dest']);
            expect(Cache::has('redirects.all'))->toBeFalse();

            // Test delete clears cache
            $repository->all(); // Re-populate cache
            expect(Cache::has('redirects.all'))->toBeTrue();
            $repository->delete($created['id']);
            expect(Cache::has('redirects.all'))->toBeFalse();
        });
    });
});
