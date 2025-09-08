<?php

use Illuminate\Support\Facades\Schema;

describe('Migration Table Configuration', function () {
    test('migration creates table with default name when not configured', function () {
        // Clear the redirects config to test default behavior
        config(['redirects' => []]);
        
        // Run the migration
        $migrationPath = __DIR__ . '/../../database/migrations/2025_05_08_100000_create_redirects_table.php';
        require_once $migrationPath;
        
        $migration = new CreateRedirectsTable();
        
        // Clean up any existing table
        Schema::dropIfExists('redirects');
        
        $migration->up();
        
        // Verify the default table was created
        expect(Schema::hasTable('redirects'))->toBeTrue();
        
        // Verify the table structure
        expect(Schema::hasColumn('redirects', 'id'))->toBeTrue();
        expect(Schema::hasColumn('redirects', 'source'))->toBeTrue();
        expect(Schema::hasColumn('redirects', 'destination'))->toBeTrue();
        expect(Schema::hasColumn('redirects', 'status_code'))->toBeTrue();
        expect(Schema::hasColumn('redirects', 'created_at'))->toBeTrue();
        expect(Schema::hasColumn('redirects', 'updated_at'))->toBeTrue();
        
        // Clean up
        $migration->down();
        expect(Schema::hasTable('redirects'))->toBeFalse();
    });

    test('migration creates table with configured custom name', function () {
        $customTableName = 'my_awesome_redirects';
        config(['redirects.table' => $customTableName]);
        
        // Run the migration
        $migrationPath = __DIR__ . '/../../database/migrations/2025_05_08_100000_create_redirects_table.php';
        require_once $migrationPath;
        
        $migration = new CreateRedirectsTable();
        
        // Clean up any existing tables
        Schema::dropIfExists('redirects');
        Schema::dropIfExists($customTableName);
        
        $migration->up();
        
        // Verify the custom table was created, not the default
        expect(Schema::hasTable($customTableName))->toBeTrue();
        expect(Schema::hasTable('redirects'))->toBeFalse();
        
        // Verify the table structure
        expect(Schema::hasColumn($customTableName, 'id'))->toBeTrue();
        expect(Schema::hasColumn($customTableName, 'source'))->toBeTrue();
        expect(Schema::hasColumn($customTableName, 'destination'))->toBeTrue();
        expect(Schema::hasColumn($customTableName, 'status_code'))->toBeTrue();
        expect(Schema::hasColumn($customTableName, 'created_at'))->toBeTrue();
        expect(Schema::hasColumn($customTableName, 'updated_at'))->toBeTrue();
        
        // Test down migration
        $migration->down();
        expect(Schema::hasTable($customTableName))->toBeFalse();
    });

    test('migration down method drops the correct configured table', function () {
        $customTableName = 'custom_redirects_for_dropping';
        config(['redirects.table' => $customTableName]);
        
        // Run the migration
        $migrationPath = __DIR__ . '/../../database/migrations/2025_05_08_100000_create_redirects_table.php';
        require_once $migrationPath;
        
        $migration = new CreateRedirectsTable();
        
        // Clean up and create the table
        Schema::dropIfExists($customTableName);
        $migration->up();
        
        // Verify table exists
        expect(Schema::hasTable($customTableName))->toBeTrue();
        
        // Now change config to different table name
        $differentTableName = 'different_table';
        config(['redirects.table' => $differentTableName]);
        
        // Create the different table manually
        Schema::create($differentTableName, function ($table) {
            $table->id();
            $table->string('test');
        });
        
        // Run down migration - it should drop the table based on current config
        $migration->down();
        
        // The table specified in current config should be dropped
        expect(Schema::hasTable($differentTableName))->toBeFalse();
        
        // But the original table should still exist (since config changed)
        expect(Schema::hasTable($customTableName))->toBeTrue();
        
        // Clean up
        Schema::dropIfExists($customTableName);
    });

    test('multiple migrations with different table configurations work correctly', function () {
        $table1 = 'redirects_migration_test_1';
        $table2 = 'redirects_migration_test_2';
        
        // Run migration with first table config
        config(['redirects.table' => $table1]);
        
        $migrationPath = __DIR__ . '/../../database/migrations/2025_05_08_100000_create_redirects_table.php';
        require_once $migrationPath;
        
        $migration1 = new CreateRedirectsTable();
        
        // Clean up
        Schema::dropIfExists($table1);
        Schema::dropIfExists($table2);
        
        $migration1->up();
        expect(Schema::hasTable($table1))->toBeTrue();
        
        // Change config and run with second table config
        config(['redirects.table' => $table2]);
        $migration2 = new CreateRedirectsTable();
        $migration2->up();
        
        // Both tables should exist
        expect(Schema::hasTable($table1))->toBeTrue();
        expect(Schema::hasTable($table2))->toBeTrue();
        
        // Verify they have the same structure
        $table1Columns = Schema::getColumnListing($table1);
        $table2Columns = Schema::getColumnListing($table2);
        
        expect($table1Columns)->toEqual($table2Columns);
        expect($table1Columns)->toContain('id', 'source', 'destination', 'status_code', 'created_at', 'updated_at');
        
        // Clean up
        config(['redirects.table' => $table1]);
        $migration1->down();
        expect(Schema::hasTable($table1))->toBeFalse();
        
        config(['redirects.table' => $table2]);
        $migration2->down();
        expect(Schema::hasTable($table2))->toBeFalse();
    });
});