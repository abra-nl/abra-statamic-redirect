<?php

use Abra\AbraStatamicRedirect\Repository\DatabaseRedirectRepository;
use Abra\AbraStatamicRedirect\Repository\FileRedirectRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Statamic\Facades\YAML;

function makeHostScopedDatabaseRepository(string $table): DatabaseRedirectRepository
{
    config(['redirects.table' => $table, 'redirects.cache_enabled' => false]);

    Schema::dropIfExists($table);
    Schema::create($table, function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('host')->default('');
        $table->string('source');
        $table->string('destination');
        $table->integer('status_code')->default(301);
        $table->timestamps();
        $table->unique(['host', 'source']);
    });

    return new DatabaseRedirectRepository;
}

describe('Host-scoped redirects: DatabaseRedirectRepository', function (): void {
    test('a global redirect matches requests on any host', function (): void {
        $repository = makeHostScopedDatabaseRepository('host_scope_global');
        $repository->store(['source' => '/contact', 'destination' => '/global-contact']);

        expect($repository->find('/contact', 'abra.ai')['destination'])->toBe('/global-contact')
            ->and($repository->find('/contact', 'abra.nl')['destination'])->toBe('/global-contact')
            ->and($repository->find('/contact')['destination'])->toBe('/global-contact');

        Schema::dropIfExists('host_scope_global');
    });

    test('a host-specific redirect only matches that exact host', function (): void {
        $repository = makeHostScopedDatabaseRepository('host_scope_specific');
        $repository->store(['host' => 'abra.nl', 'source' => '/contact', 'destination' => '/nl-contact']);

        expect($repository->find('/contact', 'abra.nl')['destination'])->toBe('/nl-contact')
            ->and($repository->find('/contact', 'abra.ai'))->toBeNull()
            ->and($repository->find('/contact'))->toBeNull();

        Schema::dropIfExists('host_scope_specific');
    });

    test('a host-specific redirect wins over a global redirect for the same source', function (): void {
        $repository = makeHostScopedDatabaseRepository('host_scope_precedence');
        $repository->store(['source' => '/contact', 'destination' => '/global-contact']);
        $repository->store(['host' => 'abra.nl', 'source' => '/contact', 'destination' => '/nl-contact']);

        expect($repository->find('/contact', 'abra.nl')['destination'])->toBe('/nl-contact')
            ->and($repository->find('/contact', 'abra.ai')['destination'])->toBe('/global-contact');

        Schema::dropIfExists('host_scope_precedence');
    });

    test('a host-scoped wildcard does not leak to other hosts', function (): void {
        $repository = makeHostScopedDatabaseRepository('host_scope_wildcard');
        $repository->store(['host' => 'abra.nl', 'source' => '/blog/*', 'destination' => '/nl-articles/*']);

        expect($repository->find('/blog/my-post', 'abra.nl')['destination'])->toBe('/nl-articles/my-post')
            ->and($repository->find('/blog/my-post', 'abra.ai'))->toBeNull()
            ->and($repository->find('/blog/my-post'))->toBeNull();

        Schema::dropIfExists('host_scope_wildcard');
    });

    test('a host-specific wildcard wins over a global wildcard for the same host', function (): void {
        $repository = makeHostScopedDatabaseRepository('host_scope_wildcard_precedence');
        $repository->store(['source' => '/blog/*', 'destination' => '/global-articles/*']);
        $repository->store(['host' => 'abra.nl', 'source' => '/blog/*', 'destination' => '/nl-articles/*']);

        expect($repository->find('/blog/my-post', 'abra.nl')['destination'])->toBe('/nl-articles/my-post')
            ->and($repository->find('/blog/my-post', 'abra.ai')['destination'])->toBe('/global-articles/my-post');

        Schema::dropIfExists('host_scope_wildcard_precedence');
    });

    test('accidentally-pasted scheme is normalized when storing or updating a host', function (): void {
        $repository = makeHostScopedDatabaseRepository('host_scope_normalize');

        $stored = $repository->store(['host' => 'https://abra.nl', 'source' => '/contact', 'destination' => '/nl-contact']);
        expect($stored['host'])->toBe('abra.nl');

        $updated = $repository->update($stored['id'], ['host' => 'https://abra.nl/']);
        expect($updated['host'])->toBe('abra.nl');

        expect($repository->find('/contact', 'abra.nl'))->not->toBeNull();

        Schema::dropIfExists('host_scope_normalize');
    });

    test('exists respects exact host scope for duplicate detection', function (): void {
        $repository = makeHostScopedDatabaseRepository('host_scope_exists');
        $repository->store(['source' => '/contact', 'destination' => '/global-contact']);

        expect($repository->exists('/contact'))->toBeTrue()
            ->and($repository->exists('/contact', 'abra.nl'))->toBeFalse();

        $repository->store(['host' => 'abra.nl', 'source' => '/contact', 'destination' => '/nl-contact']);

        expect($repository->exists('/contact', 'abra.nl'))->toBeTrue();

        Schema::dropIfExists('host_scope_exists');
    });
});

describe('Host-scoped redirects: FileRedirectRepository', function (): void {
    beforeEach(function (): void {
        $this->testDir = storage_path('tests/host-scope-redirects');
        $this->testFile = $this->testDir.'/redirects.yaml';

        if (File::exists($this->testDir)) {
            File::deleteDirectory($this->testDir);
        }

        config([
            'redirects.file_path' => $this->testFile,
            'redirects.cache_enabled' => false,
        ]);
    });

    afterEach(function (): void {
        if (File::exists($this->testDir)) {
            File::deleteDirectory($this->testDir);
        }
    });

    test('a global redirect matches requests on any host', function (): void {
        $repository = new FileRedirectRepository;
        $repository->store(['source' => '/contact', 'destination' => '/global-contact']);

        expect($repository->find('/contact', 'abra.ai')['destination'])->toBe('/global-contact')
            ->and($repository->find('/contact', 'abra.nl')['destination'])->toBe('/global-contact');
    });

    test('a host-specific redirect only matches that exact host', function (): void {
        $repository = new FileRedirectRepository;
        $repository->store(['host' => 'abra.nl', 'source' => '/contact', 'destination' => '/nl-contact']);

        expect($repository->find('/contact', 'abra.nl')['destination'])->toBe('/nl-contact')
            ->and($repository->find('/contact', 'abra.ai'))->toBeNull();
    });

    test('a host-specific redirect wins over a global redirect for the same source', function (): void {
        $repository = new FileRedirectRepository;
        $repository->store(['source' => '/contact', 'destination' => '/global-contact']);
        $repository->store(['host' => 'abra.nl', 'source' => '/contact', 'destination' => '/nl-contact']);

        expect($repository->find('/contact', 'abra.nl')['destination'])->toBe('/nl-contact')
            ->and($repository->find('/contact', 'abra.ai')['destination'])->toBe('/global-contact');
    });

    test('a host-scoped wildcard does not leak to other hosts', function (): void {
        $repository = new FileRedirectRepository;
        $repository->store(['host' => 'abra.nl', 'source' => '/blog/*', 'destination' => '/nl-articles/*']);

        expect($repository->find('/blog/my-post', 'abra.nl')['destination'])->toBe('/nl-articles/my-post')
            ->and($repository->find('/blog/my-post', 'abra.ai'))->toBeNull();
    });

    test('legacy rows without a host key keep matching any host', function (): void {
        File::makeDirectory($this->testDir, 0755, true);
        File::put($this->testFile, YAML::dump([
            [
                'id' => 'legacy-1',
                'source' => '/legacy-page',
                'destination' => '/new-legacy-page',
                'status_code' => 301,
                'created_at' => '2023-01-01T00:00:00+00:00',
                'updated_at' => '2023-01-01T00:00:00+00:00',
            ],
        ]));

        $repository = new FileRedirectRepository;

        expect($repository->find('/legacy-page', 'abra.ai')['destination'])->toBe('/new-legacy-page')
            ->and($repository->find('/legacy-page', 'abra.nl')['destination'])->toBe('/new-legacy-page')
            ->and($repository->exists('/legacy-page'))->toBeTrue()
            // A global row does not block creating a host-specific override for the same source.
            ->and($repository->exists('/legacy-page', 'abra.ai'))->toBeFalse();
    });

    test('accidentally-pasted scheme is normalized when storing or updating a host', function (): void {
        $repository = new FileRedirectRepository;

        $stored = $repository->store(['host' => 'https://abra.nl', 'source' => '/contact', 'destination' => '/nl-contact']);
        expect($stored['host'])->toBe('abra.nl');

        $updated = $repository->update($stored['id'], ['host' => 'https://abra.nl/']);
        expect($updated['host'])->toBe('abra.nl');
    });
});
