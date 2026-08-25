<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

function requireRedirectsMigrations(): void
{
    require_once __DIR__.'/../../database/migrations/2025_05_08_100000_create_redirects_table.php';
    require_once __DIR__.'/../../database/migrations/2026_08_25_000000_add_host_to_redirects_table.php';
}

describe('AddHostToRedirectsTable migration', function (): void {
    test('upgrades a table created by an already-installed version of the addon', function (): void {
        config(['redirects' => []]);
        requireRedirectsMigrations();

        Schema::dropIfExists('redirects');

        // Simulate a site that installed the addon before host scoping existed:
        // only the original create-table migration has ever run.
        (new CreateRedirectsTable)->up();

        DB::table('redirects')->insert([
            'id' => (string) Str::uuid(),
            'source' => '/legacy-source',
            'destination' => '/legacy-destination',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        expect(Schema::hasColumn('redirects', 'host'))->toBeFalse();

        // Now the addon is updated and the new migration runs.
        (new AddHostToRedirectsTable)->up();

        expect(Schema::hasColumn('redirects', 'host'))->toBeTrue();

        // The pre-existing row is backfilled to the global host, unchanged.
        $legacyRow = DB::table('redirects')->where('source', '/legacy-source')->first();
        expect($legacyRow->host)->toBe('')
            ->and($legacyRow->destination)->toBe('/legacy-destination');

        // The old single-column unique(['source']) constraint is gone: the same
        // source can now also have a host-specific override.
        DB::table('redirects')->insert([
            'id' => (string) Str::uuid(),
            'host' => 'abra.nl',
            'source' => '/legacy-source',
            'destination' => '/nl-destination',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        expect(DB::table('redirects')->where('source', '/legacy-source')->count())->toBe(2);

        // The new composite unique(['host', 'source']) still prevents true duplicates.
        expect(fn () => DB::table('redirects')->insert([
            'id' => (string) Str::uuid(),
            'source' => '/legacy-source',
            'destination' => '/duplicate-destination',
            'created_at' => now(),
            'updated_at' => now(),
        ]))->toThrow(Exception::class);

        Schema::dropIfExists('redirects');
    });

    test('runs cleanly as part of a fresh install migrating both files in order', function (): void {
        config(['redirects' => []]);
        requireRedirectsMigrations();

        Schema::dropIfExists('redirects');

        (new CreateRedirectsTable)->up();
        (new AddHostToRedirectsTable)->up();

        expect(Schema::hasColumn('redirects', 'host'))->toBeTrue();

        DB::table('redirects')->insert([
            'id' => (string) Str::uuid(),
            'source' => '/fresh-source',
            'destination' => '/fresh-destination',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('redirects')->where('source', '/fresh-source')->first();
        expect($row->host)->toBe('');

        Schema::dropIfExists('redirects');
    });

    test('down reverses the migration, restoring the original schema', function (): void {
        config(['redirects' => []]);
        requireRedirectsMigrations();

        Schema::dropIfExists('redirects');

        $create = new CreateRedirectsTable;
        $addHost = new AddHostToRedirectsTable;

        $create->up();
        $addHost->up();
        expect(Schema::hasColumn('redirects', 'host'))->toBeTrue();

        $addHost->down();
        expect(Schema::hasColumn('redirects', 'host'))->toBeFalse();

        $create->down();
        expect(Schema::hasTable('redirects'))->toBeFalse();
    });

    test('respects a configured custom table name', function (): void {
        $customTableName = 'custom_add_host_redirects';
        config(['redirects.table' => $customTableName]);
        requireRedirectsMigrations();

        Schema::dropIfExists($customTableName);

        $create = new CreateRedirectsTable;
        $addHost = new AddHostToRedirectsTable;

        $create->up();
        $addHost->up();

        expect(Schema::hasColumn($customTableName, 'host'))->toBeTrue();

        $addHost->down();
        $create->down();
        expect(Schema::hasTable($customTableName))->toBeFalse();
    });
});
