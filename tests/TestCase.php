<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Tests;

use Hamzi\Catchy\CatchyServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Class TestCase
 *
 * Base test case class integrating Orchestra Testbench to bootstrap the Laravel application environment.
 */
abstract class TestCase extends Orchestra
{
    /**
     * Define the package service providers.
     *
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CatchyServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('view:clear');
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Configure default config settings for testing
        $app['config']->set('catchy.container_id', 'catchy-app');
    }
}
