<?php

namespace Hamzi\Catchy\Tests;

use Hamzi\Catchy\CatchyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Class TestCase
 *
 * Base test case class integrating Orchestra Testbench to bootstrap the Laravel application environment.
 *
 * @package Hamzi\Catchy\Tests
 */
abstract class TestCase extends Orchestra
{
    /**
     * Define the package service providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
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
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Configure default config settings for testing
        $app['config']->set('catchy.container_id', 'catchy-app');
    }
}
