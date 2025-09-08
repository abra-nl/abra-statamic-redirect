<?php

use Abra\AbraStatamicRedirect\Repository\DatabaseRedirectRepository;
use Illuminate\Support\Facades\Schema;
use Statamic\Facades\User;

beforeEach(function () {
    // Create and authenticate a superuser for CP access
    $this->actingAs(User::make()
        ->email('test@example.com')
        ->makeSuper()
        ->save()
    );
});

describe('Database Table Integration', function () {
    test('full integration test with custom table name from config to controller', function () {
        $customTableName = 'integration_test_redirects';
        
        // Configure custom table name
        config([
            'redirects.table' => $customTableName,
            'redirects.storage' => 'database',
            'redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']
        ]);

        // Run migration to create custom table
        $migrationPath = __DIR__ . '/../../database/migrations/2025_05_08_100000_create_redirects_table.php';
        require_once $migrationPath;
        
        $migration = new CreateRedirectsTable();
        
        // Clean up any existing table
        Schema::dropIfExists($customTableName);
        $migration->up();
        
        // Verify custom table was created
        expect(Schema::hasTable($customTableName))->toBeTrue();

        // Test through controller - create a redirect
        $redirectData = [
            'source' => '/integration-test-source',
            'destination' => '/integration-test-destination', 
            'status_code' => 301
        ];

        $response = $this->post(cp_route('abra-statamic-redirects.store'), $redirectData);
        $response->assertRedirect(cp_route('abra-statamic-redirects.index'));

        // Verify the redirect was stored in the custom table
        $tableData = \Illuminate\Support\Facades\DB::table($customTableName)->first();
        expect($tableData)->not->toBeNull();
        expect($tableData->source)->toBe('/integration-test-source');
        expect($tableData->destination)->toBe('/integration-test-destination');
        expect($tableData->status_code)->toBe(301);

        // Test through controller - display redirects
        $response = $this->get(cp_route('abra-statamic-redirects.index'));
        $response->assertStatus(200);
        $response->assertSee('/integration-test-source');
        $response->assertSee('/integration-test-destination');

        // Test through controller - edit redirect  
        $redirectId = $tableData->id;
        $response = $this->get(cp_route('abra-statamic-redirects.edit', $redirectId));
        $response->assertStatus(200);

        // Test through controller - update redirect
        $updateData = [
            'source' => '/integration-test-source',
            'destination' => '/updated-destination',
            'status_code' => 302
        ];

        $response = $this->patch(cp_route('abra-statamic-redirects.update', $redirectId), $updateData);
        $response->assertRedirect(cp_route('abra-statamic-redirects.index'));

        // Verify the update in custom table
        $updatedData = \Illuminate\Support\Facades\DB::table($customTableName)->where('id', $redirectId)->first();
        expect($updatedData->destination)->toBe('/updated-destination');
        expect($updatedData->status_code)->toBe(302);

        // Test through controller - delete redirect
        $response = $this->delete(cp_route('abra-statamic-redirects.destroy', $redirectId));
        $response->assertRedirect(cp_route('abra-statamic-redirects.index'));

        // Verify deletion from custom table
        $deletedData = \Illuminate\Support\Facades\DB::table($customTableName)->where('id', $redirectId)->first();
        expect($deletedData)->toBeNull();

        // Clean up
        $migration->down();
        expect(Schema::hasTable($customTableName))->toBeFalse();
    });

});