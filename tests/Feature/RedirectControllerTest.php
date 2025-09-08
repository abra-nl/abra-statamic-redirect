<?php

use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Abra\AbraStatamicRedirect\Http\Controllers\RedirectController;
use Statamic\Facades\User;

beforeEach(function () {
    // Mock the RedirectRepository
    $this->redirectRepository = Mockery::mock(RedirectRepository::class);
    $this->app->instance(RedirectRepository::class, $this->redirectRepository);
    
    // Create and authenticate a superuser for CP access
    $this->actingAs(User::make()
        ->email('test@example.com')
        ->makeSuper()
        ->save()
    );
    
    // Sample redirect data
    $this->sampleRedirect = [
        'id' => '123',
        'source' => '/old-page',
        'destination' => '/new-page',
        'status_code' => 301
    ];
    
    $this->validRedirectData = [
        'source' => '/test-source',
        'destination' => '/test-destination',
        'status_code' => 301
    ];
});

describe('RedirectController', function () {
    test('index displays list of redirects', function () {
        $redirects = [
            $this->sampleRedirect,
            [
                'id' => '456',
                'source' => '/another-old-page',
                'destination' => '/another-new-page',
                'status_code' => 302
            ]
        ];

        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($redirects);

        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $response = $this->get(cp_route('abra-statamic-redirects.index'));

        $response->assertStatus(200);
        $response->assertViewIs('abra-redirects::index');
        $response->assertViewHas('redirects', $redirects);
        $response->assertViewHas('statusCodes', [301 => 'Permanent', 302 => 'Temporary']);
    });

    test('create displays create form', function () {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $response = $this->get(cp_route('abra-statamic-redirects.create'));

        $response->assertStatus(200);
        $response->assertViewIs('abra-redirects::create');
        $response->assertViewHas('statusCodes', [301 => 'Permanent', 302 => 'Temporary']);
    });

    test('store creates new redirect successfully', function () {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $this->redirectRepository
            ->shouldReceive('exists')
            ->with($this->validRedirectData['source'])
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

    test('store fails when source already exists', function () {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $this->redirectRepository
            ->shouldReceive('exists')
            ->with($this->validRedirectData['source'])
            ->once()
            ->andReturn(true);

        $response = $this->post(cp_route('abra-statamic-redirects.store'), $this->validRedirectData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['source' => 'A redirect with this source URL already exists.']);
    });

    test('store validates required fields', function () {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $response = $this->post(cp_route('abra-statamic-redirects.store'));

        $response->assertSessionHasErrors(['source', 'destination', 'status_code']);
    });

    test('store validates status code is in allowed list', function () {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $invalidData = array_merge($this->validRedirectData, ['status_code' => 999]);

        $response = $this->post(cp_route('abra-statamic-redirects.store'), $invalidData);

        $response->assertSessionHasErrors(['status_code']);
    });

    test('edit displays edit form for existing redirect', function () {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $redirects = [$this->sampleRedirect];

        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($redirects);

        $response = $this->get(cp_route('abra-statamic-redirects.edit', ['id' => '123']));

        $response->assertStatus(200);
        $response->assertViewIs('abra-redirects::edit');
        $response->assertViewHas('redirect', $this->sampleRedirect);
        $response->assertViewHas('statusCodes', [301 => 'Permanent', 302 => 'Temporary']);
    });

    test('edit redirects when redirect not found', function () {
        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn([]);

        $response = $this->get(cp_route('abra-statamic-redirects.edit', ['id' => 'nonexistent']));

        $response->assertRedirect(cp_route('abra-statamic-redirects.index'));
        $response->assertSessionHas('error', 'Redirect not found.');
    });

    test('update modifies existing redirect successfully', function () {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $updatedData = [
            'source' => '/updated-source',
            'destination' => '/updated-destination',
            'status_code' => 302
        ];

        $this->redirectRepository
            ->shouldReceive('exists')
            ->with($updatedData['source'], '123')
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

    test('update fails when source already exists for different redirect', function () {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $this->redirectRepository
            ->shouldReceive('exists')
            ->with($this->validRedirectData['source'], '123')
            ->once()
            ->andReturn(true);

        $response = $this->patch(cp_route('abra-statamic-redirects.update', ['id' => '123']), $this->validRedirectData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['source' => 'A redirect with this source URL already exists.']);
    });

    test('update validates required fields', function () {
        config(['redirects.status_codes' => [301 => 'Permanent', 302 => 'Temporary']]);

        $response = $this->patch(cp_route('abra-statamic-redirects.update', ['id' => '123']));

        $response->assertSessionHasErrors(['source', 'destination', 'status_code']);
    });

    test('destroy deletes redirect successfully', function () {
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