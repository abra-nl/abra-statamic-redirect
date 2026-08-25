<?php

use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Inertia\Testing\AssertableInertia as Assert;
use Statamic\Facades\User;

beforeEach(function (): void {
    // Mock the RedirectRepository
    $this->redirectRepository = Mockery::mock(RedirectRepository::class);
    $this->app->instance(RedirectRepository::class, $this->redirectRepository);

    // Create and authenticate a superuser for CP access
    $this->actingAs(User::make()
        ->email('test@example.com')
        ->makeSuper()
        ->save(),
    );

    // Sample redirect data
    $this->sampleRedirect = [
        'id' => '123',
        'host' => '',
        'source' => '/old-page',
        'destination' => '/new-page',
        'status_code' => 301,
    ];

    $this->validRedirectData = [
        'source' => '/test-source',
        'destination' => '/test-destination',
        'status_code' => 301,
    ];
});

describe('RedirectController', function (): void {
    test('index renders the listing page', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $response = $this->get(cp_route('abra-statamic-redirects.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $assert): Assert => $assert
            ->component('abra-redirects::Index')
            ->where('jsonUrl', cp_route('abra-statamic-redirects.json'))
            ->where('actionUrl', cp_route('abra-statamic-redirects.actions.run'))
            ->where('statusCodes', [301 => 'Permanent', 302 => 'Temporary']),
        );
    });

    test('json returns a paginated list of redirects', function (): void {
        $redirects = array_map(
            fn (int $i): array => [
                'id' => (string) $i,
                'host' => '',
                'source' => sprintf('/old-page-%02d', $i),
                'destination' => sprintf('/new-page-%02d', $i),
                'status_code' => 301,
            ],
            range(1, 60),
        );

        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($redirects);

        config(['statamic.cp.pagination_size' => 25]);

        $response = $this->get(cp_route('abra-statamic-redirects.json', ['page' => 2]));

        $response->assertStatus(200);
        $response->assertJson(fn ($json) => $json
            ->has('data', 25)
            ->where('meta.current_page', 2)
            ->where('meta.per_page', 25)
            ->where('meta.total', 60)
            ->where('meta.last_page', 3)
            ->has('meta.columns', 4)
            ->etc(),
        );
    });

    test('json filters redirects by search', function (): void {
        $redirects = [
            $this->sampleRedirect,
            [
                'id' => '456',
                'host' => '',
                'source' => '/another-old-page',
                'destination' => '/another-new-page',
                'status_code' => 302,
            ],
        ];

        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($redirects);

        $response = $this->get(cp_route('abra-statamic-redirects.json', ['search' => 'another']));

        $response->assertStatus(200);
        $response->assertJson(fn ($json) => $json
            ->has('data', 1)
            ->where('data.0.id', '456')
            ->etc(),
        );
    });

    test('json searches redirects that have no host key at all', function (): void {
        // Legacy file-storage rows written before host scoping was introduced
        // may not have a 'host' key at all.
        $redirects = [
            [
                'id' => '789',
                'source' => '/legacy-page',
                'destination' => '/legacy-new-page',
                'status_code' => 301,
            ],
        ];

        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($redirects);

        $response = $this->get(cp_route('abra-statamic-redirects.json', ['search' => 'legacy']));

        $response->assertStatus(200);
        $response->assertJson(fn ($json) => $json
            ->has('data', 1)
            ->where('data.0.id', '789')
            ->etc(),
        );
    });

    test('json sorts redirects descending', function (): void {
        $redirects = [
            $this->sampleRedirect,
            [
                'id' => '456',
                'host' => '',
                'source' => '/zzz-page',
                'destination' => '/another-new-page',
                'status_code' => 302,
            ],
        ];

        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($redirects);

        $response = $this->get(cp_route('abra-statamic-redirects.json', ['sort' => 'source', 'order' => 'desc']));

        $response->assertStatus(200);
        $response->assertJson(fn ($json) => $json
            ->where('data.0.id', '456')
            ->where('data.1.id', '123')
            ->etc(),
        );
    });

    test('create displays create form', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $response = $this->get(cp_route('abra-statamic-redirects.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $assert): Assert => $assert
            ->component('abra-redirects::Create')
            ->where('statusCodes', [301 => 'Permanent', 302 => 'Temporary']),
        );
    });

    test('store creates new redirect successfully', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $this->redirectRepository
            ->shouldReceive('exists')
            ->with($this->validRedirectData['source'], null)
            ->once()
            ->andReturn(false);

        $this->redirectRepository
            ->shouldReceive('store')
            ->with($this->validRedirectData)
            ->once()
            ->andReturn(array_merge($this->validRedirectData, ['id' => '789']));

        $response = $this->post(cp_route('abra-statamic-redirects.store'), $this->validRedirectData);

        $response->assertRedirect(cp_route('abra-statamic-redirects.index'));
        $response->assertSessionHas('success', 'Redirect created successfully.');
    });

    test('store creates new redirect scoped to a host', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $hostScopedData = array_merge($this->validRedirectData, ['host' => 'abra.nl']);

        $this->redirectRepository
            ->shouldReceive('exists')
            ->with($hostScopedData['source'], 'abra.nl')
            ->once()
            ->andReturn(false);

        $this->redirectRepository
            ->shouldReceive('store')
            ->with($hostScopedData)
            ->once()
            ->andReturn(array_merge($hostScopedData, ['id' => '789']));

        $response = $this->post(cp_route('abra-statamic-redirects.store'), $hostScopedData);

        $response->assertRedirect(cp_route('abra-statamic-redirects.index'));
        $response->assertSessionHas('success', 'Redirect created successfully.');
    });

    test('store fails when source already exists', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $this->redirectRepository
            ->shouldReceive('exists')
            ->with($this->validRedirectData['source'], null)
            ->once()
            ->andReturn(true);

        $response = $this->post(cp_route('abra-statamic-redirects.store'), $this->validRedirectData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['source' => 'A redirect with this source URL already exists.']);
    });

    test('store validates required fields', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $response = $this->post(cp_route('abra-statamic-redirects.store'));

        $response->assertSessionHasErrors(['source', 'destination', 'status_code']);
    });

    test('store validates status code is in allowed list', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $invalidData = array_merge($this->validRedirectData, ['status_code' => 999]);

        $response = $this->post(cp_route('abra-statamic-redirects.store'), $invalidData);

        $response->assertSessionHasErrors(['status_code']);
    });

    test('edit displays edit form for existing redirect', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $redirects = [$this->sampleRedirect];

        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($redirects);

        $response = $this->get(cp_route('abra-statamic-redirects.edit', ['id' => '123']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $assert): Assert => $assert
            ->component('abra-redirects::Edit')
            ->where('redirect', $this->sampleRedirect)
            ->where('statusCodes', [301 => 'Permanent', 302 => 'Temporary']),
        );
    });

    test('edit redirects to index when redirect not found', function (): void {
        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn([]);

        $response = $this->get(cp_route('abra-statamic-redirects.edit', ['id' => 'nonexistent']));

        $response->assertRedirect(cp_route('abra-statamic-redirects.index'));
        $response->assertSessionHas('error', 'Redirect not found.');
    });

    test('update modifies existing redirect successfully', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $updatedData = [
            'source' => '/updated-source',
            'destination' => '/updated-destination',
            'status_code' => 302,
        ];

        $this->redirectRepository
            ->shouldReceive('exists')
            ->with($updatedData['source'], null, '123')
            ->once()
            ->andReturn(false);

        $this->redirectRepository
            ->shouldReceive('update')
            ->with('123', $updatedData)
            ->once()
            ->andReturn(array_merge($updatedData, ['id' => '123']));

        $response = $this->patch(cp_route('abra-statamic-redirects.update', ['id' => '123']), $updatedData);

        $response->assertRedirect(cp_route('abra-statamic-redirects.index'));
        $response->assertSessionHas('success', 'Redirect updated successfully.');
    });

    test('update fails when source already exists for different redirect', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $this->redirectRepository
            ->shouldReceive('exists')
            ->with($this->validRedirectData['source'], null, '123')
            ->once()
            ->andReturn(true);

        $response = $this->patch(cp_route('abra-statamic-redirects.update', ['id' => '123']), $this->validRedirectData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['source' => 'A redirect with this source URL already exists.']);
    });

    test('update validates required fields', function (): void {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $response = $this->patch(cp_route('abra-statamic-redirects.update', ['id' => '123']));

        $response->assertSessionHasErrors(['source', 'destination', 'status_code']);
    });

    test('destroy deletes redirect successfully', function (): void {
        $this->redirectRepository
            ->shouldReceive('delete')
            ->with('123')
            ->once()
            ->andReturn(true);

        $response = $this->delete(cp_route('abra-statamic-redirects.destroy', ['id' => '123']));

        $response->assertRedirect(cp_route('abra-statamic-redirects.index'));
        $response->assertSessionHas('success', 'Redirect deleted successfully.');
    });
});
