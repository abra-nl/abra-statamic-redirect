<?php

use Abra\AbraStatamicRedirect\Repository\DatabaseRedirectRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

describe('Database Table Configuration', function () {
    test('database repository uses default table name when not configured', function () {
        // Create a temporary config instance without the redirects.table key
        $originalConfig = config('redirects.table');
        config(['redirects' => []]);

        $repository = new DatabaseRedirectRepository;

        // Use reflection to access private table property
        $reflection = new ReflectionClass($repository);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);

        expect($tableProperty->getValue($repository))->toBe('redirects');

        // Restore original config
        if ($originalConfig !== null) {
            config(['redirects.table' => $originalConfig]);
        }
    });

    test('database repository uses configured table name', function () {
        config(['redirects.table' => 'custom_redirects_table']);

        $repository = new DatabaseRedirectRepository;

        // Use reflection to access private table property
        $reflection = new ReflectionClass($repository);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);

        expect($tableProperty->getValue($repository))->toBe('custom_redirects_table');
    });

    test('database repository operations use configured table name', function () {
        $customTableName = 'my_custom_redirects';
        config(['redirects.table' => $customTableName]);

        // Create the custom table for testing
        Schema::create($customTableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source')->unique()->index();
            $table->string('destination');
            $table->integer('status_code')->default(301);
            $table->timestamps();
        });

        $repository = new DatabaseRedirectRepository;

        // Test store operation
        $redirectData = [
            'source' => '/test-source',
            'destination' => '/test-destination',
            'status_code' => 301,
        ];

        $storedRedirect = $repository->store($redirectData);

        // Verify the redirect was stored in the custom table
        $tableData = DB::table($customTableName)->where('id', $storedRedirect['id'])->first();
        expect($tableData)->not->toBeNull();
        expect($tableData->source)->toBe('/test-source');
        expect($tableData->destination)->toBe('/test-destination');

        // Test find operation
        $foundRedirect = $repository->find('/test-source');
        expect($foundRedirect)->not->toBeNull();
        expect($foundRedirect['source'])->toBe('/test-source');

        // Test all operation
        $allRedirects = $repository->all();
        expect($allRedirects)->toHaveCount(1);
        expect($allRedirects[0]['source'])->toBe('/test-source');

        // Test exists operation
        expect($repository->exists('/test-source'))->toBeTrue();
        expect($repository->exists('/non-existent'))->toBeFalse();

        // Test update operation
        $updatedRedirect = $repository->update($storedRedirect['id'], [
            'destination' => '/updated-destination',
        ]);
        expect($updatedRedirect['destination'])->toBe('/updated-destination');

        // Test delete operation
        $deleted = $repository->delete($storedRedirect['id']);
        expect($deleted)->toBeTrue();
        expect($repository->find('/test-source'))->toBeNull();

        // Clean up
        Schema::dropIfExists($customTableName);
    });

    test('multiple repository instances with different table configurations work independently', function () {
        $table1 = 'redirects_table_1';
        $table2 = 'redirects_table_2';

        // Create both tables
        Schema::create($table1, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source')->unique()->index();
            $table->string('destination');
            $table->integer('status_code')->default(301);
            $table->timestamps();
        });

        Schema::create($table2, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source')->unique()->index();
            $table->string('destination');
            $table->integer('status_code')->default(301);
            $table->timestamps();
        });

        // Create repositories with different table configurations
        config(['redirects.table' => $table1]);
        $repository1 = new DatabaseRedirectRepository;

        config(['redirects.table' => $table2]);
        $repository2 = new DatabaseRedirectRepository;

        // Store data in each repository
        $redirect1 = $repository1->store([
            'source' => '/table1-source',
            'destination' => '/table1-destination',
            'status_code' => 301,
        ]);

        $redirect2 = $repository2->store([
            'source' => '/table2-source',
            'destination' => '/table2-destination',
            'status_code' => 302,
        ]);

        // Verify data isolation
        expect($repository1->all())->toHaveCount(1);
        expect($repository2->all())->toHaveCount(1);

        expect($repository1->find('/table1-source'))->not->toBeNull();
        expect($repository1->find('/table2-source'))->toBeNull();

        expect($repository2->find('/table2-source'))->not->toBeNull();
        expect($repository2->find('/table1-source'))->toBeNull();

        // Verify direct table queries
        expect(DB::table($table1)->count())->toBe(1);
        expect(DB::table($table2)->count())->toBe(1);

        $table1Data = DB::table($table1)->first();
        $table2Data = DB::table($table2)->first();

        expect($table1Data->source)->toBe('/table1-source');
        expect($table2Data->source)->toBe('/table2-source');

        // Clean up
        Schema::dropIfExists($table1);
        Schema::dropIfExists($table2);
    });

    test('repository handles table name configuration changes correctly', function () {
        $originalTable = 'original_redirects';
        $newTable = 'new_redirects';

        // Create original table
        Schema::create($originalTable, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source')->unique()->index();
            $table->string('destination');
            $table->integer('status_code')->default(301);
            $table->timestamps();
        });

        // Configure and use original table
        config(['redirects.table' => $originalTable]);
        $repository = new DatabaseRedirectRepository;

        $redirect = $repository->store([
            'source' => '/original-source',
            'destination' => '/original-destination',
            'status_code' => 301,
        ]);

        expect($repository->all())->toHaveCount(1);

        // Create new table and reconfigure
        Schema::create($newTable, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source')->unique()->index();
            $table->string('destination');
            $table->integer('status_code')->default(301);
            $table->timestamps();
        });

        // Create new repository instance with new config
        config(['redirects.table' => $newTable]);
        $newRepository = new DatabaseRedirectRepository;

        // Verify new repository uses new table (should be empty)
        expect($newRepository->all())->toHaveCount(0);

        // Store in new table
        $newRepository->store([
            'source' => '/new-source',
            'destination' => '/new-destination',
            'status_code' => 301,
        ]);

        expect($newRepository->all())->toHaveCount(1);

        // Verify original table still has its data
        expect(DB::table($originalTable)->count())->toBe(1);
        expect(DB::table($newTable)->count())->toBe(1);

        // Clean up
        Schema::dropIfExists($originalTable);
        Schema::dropIfExists($newTable);
    });
});
