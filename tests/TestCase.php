<?php

namespace Abra\AbraStatamicRedirect\Tests;

use Abra\AbraStatamicRedirect\ServiceProvider;
use Statamic\Testing\AddonTestCase;

class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function resolveApplicationConfiguration($app): void
    {

        parent::resolveApplicationConfiguration($app);

        // Set a proper application key
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // Enable Statamic Pro features for testing
        $app['config']->set('statamic.editions.pro', true);

        // Set up test redirects configuration
        $app['config']->set('redirects.storage', 'file');
        $app['config']->set('redirects.status_codes', [
            301 => 'Permanent',
            302 => 'Temporary',
            307 => 'Temporary (preserve method)',
            308 => 'Permanent (preserve method)'
        ]);
    }
}