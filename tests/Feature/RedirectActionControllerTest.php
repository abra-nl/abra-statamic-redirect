<?php

use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Statamic\Facades\User;

beforeEach(function (): void {
    $this->redirectRepository = Mockery::mock(RedirectRepository::class);
    $this->app->instance(RedirectRepository::class, $this->redirectRepository);

    $this->actingAs(User::make()
        ->email('test@example.com')
        ->makeSuper()
        ->save(),
    );

    $this->redirects = [
        ['id' => '123', 'host' => '', 'source' => '/old-page', 'destination' => '/new-page', 'status_code' => 301],
        ['id' => '456', 'host' => '', 'source' => '/another-old-page', 'destination' => '/another-new-page', 'status_code' => 302],
    ];
});

describe('RedirectActionController', function (): void {
    test('bulk actions list includes delete action for a selected redirect', function (): void {
        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($this->redirects);

        $response = $this->post(cp_route('abra-statamic-redirects.actions.list'), [
            'selections' => ['123'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['handle' => 'delete_redirect']);
    });

    test('delete action removes a single selected redirect', function (): void {
        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($this->redirects);

        $this->redirectRepository
            ->shouldReceive('delete')
            ->with('123')
            ->once()
            ->andReturn(true);

        $response = $this->post(cp_route('abra-statamic-redirects.actions.run'), [
            'action' => 'delete_redirect',
            'selections' => ['123'],
            'values' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(fn ($json) => $json
            ->where('success', true)
            ->etc(),
        );
    });

    test('delete action removes multiple selected redirects in bulk', function (): void {
        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($this->redirects);

        $this->redirectRepository
            ->shouldReceive('delete')
            ->with('123')
            ->once()
            ->andReturn(true);

        $this->redirectRepository
            ->shouldReceive('delete')
            ->with('456')
            ->once()
            ->andReturn(true);

        $response = $this->post(cp_route('abra-statamic-redirects.actions.run'), [
            'action' => 'delete_redirect',
            'selections' => ['123', '456'],
            'values' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(fn ($json) => $json
            ->where('success', true)
            ->etc(),
        );
    });

    test('delete action reports failure when the repository fails to delete', function (): void {
        $this->redirectRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($this->redirects);

        $this->redirectRepository
            ->shouldReceive('delete')
            ->with('123')
            ->once()
            ->andReturn(false);

        $response = $this->post(cp_route('abra-statamic-redirects.actions.run'), [
            'action' => 'delete_redirect',
            'selections' => ['123'],
            'values' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(fn ($json) => $json
            ->where('success', false)
            ->etc(),
        );
    });
});
