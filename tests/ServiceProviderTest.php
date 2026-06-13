<?php

namespace Hamzi\Catchy\Tests;

use Illuminate\Support\Facades\Blade;

/**
 * Class ServiceProviderTest
 *
 * Verifies that the CatchyServiceProvider boots correctly, registers middleware alias,
 * and configures the @catchyScripts Blade directive properly.
 *
 * @package Hamzi\Catchy\Tests
 */
class ServiceProviderTest extends TestCase
{
    /**
     * Test that the 'catchy' middleware alias is registered in the router.
     */
    public function test_middleware_alias_is_registered(): void
    {
        $router = $this->app['router'];
        
        $middleware = $router->getMiddleware();

        $this->assertArrayHasKey('catchy', $middleware);
        $this->assertEquals(\Hamzi\Catchy\Http\Middleware\CatchySPAMiddleware::class, $middleware['catchy']);
    }

    /**
     * Test that the @catchyScripts Blade directive renders to the inline script correctly.
     */
    public function test_blade_directive_renders_correctly(): void
    {
        $html = Blade::render('@catchyScripts');

        // Verify that the rendered HTML contains the morph script, config settings, and inline plugin code
        $this->assertStringContainsString('https://cdn.jsdelivr.net/npm/@alpinejs/morph', $html);
        $this->assertStringContainsString('window.CatchyConfig =', $html);
        $this->assertStringContainsString('Hamzi/Catchy - Alpine.js SPA Plugin', $html);
        $this->assertStringContainsString('CatchyPlugin', $html);
        $this->assertStringContainsString('window.history.pushState', $html);
    }
}
