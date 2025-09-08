<?php

namespace Abra\AbraStatamicRedirect;

use Abra\AbraStatamicRedirect\Http\Middleware\RedirectMiddleware;
use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Abra\AbraStatamicRedirect\Repository\DatabaseRedirectRepository;
use Abra\AbraStatamicRedirect\Repository\FileRedirectRepository;
use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $middlewareGroups = [
        'statamic.web' => [
            RedirectMiddleware::class,
        ],
    ];

    public function bootAddon(): void
    {
        $this->publishes([
            __DIR__.'/../config/redirects.php' => config_path('redirects.php'),
        ], 'config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ]);

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'abra-redirects');

        $this->app->bind(RedirectRepository::class, function () {
            $storage = config('redirects.storage', 'file');

            if ($storage === 'database') {
                return new DatabaseRedirectRepository;
            }

            return new FileRedirectRepository;
        });

        Nav::extend(function ($nav) {
            $nav->create('Redirects')
                ->section('Settings')
                ->route('statamic.cp.abra-statamic-redirects.index')
                ->icon('list');
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/redirects.php', 'redirects');
    }
}