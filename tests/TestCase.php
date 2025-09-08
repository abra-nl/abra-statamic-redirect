<?php

namespace Abra\AbraStatamicRedirect\tests;

use Abra\AbraStatamicRedirect\ServiceProvider;
use Statamic\Testing\AddonTestCase;

class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}