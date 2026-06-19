<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Tests;

use Illuminate\Support\Facades\Blade;
use Hamzi\Catchy\Domain\Contracts\ResponseExtractorInterface;
use Hamzi\Catchy\Domain\Contracts\VersionRepositoryInterface;
use Hamzi\Catchy\Domain\Contracts\ComponentRepositoryInterface;

/**
 * Class ServiceProviderTest
 *
 * Verifies that the CatchyServiceProvider boots correctly, registers middleware alias,
 * binds core interfaces, and configures compiler directives.
 *
 * @package Hamzi\Catchy\Tests
 */
class ServiceProviderTest extends TestCase
{
    private ?string $tempBackupPath = null;

    protected function setUp(): void
    {
        parent::setUp();

        $publishedPath = public_path('vendor/catchy/catchy.js');
        if (file_exists($publishedPath)) {
            $this->tempBackupPath = tempnam(sys_get_temp_dir(), 'catchy_backup');
            copy($publishedPath, $this->tempBackupPath);
            unlink($publishedPath);
        }
    }

    protected function tearDown(): void
    {
        if ($this->tempBackupPath && file_exists($this->tempBackupPath)) {
            $publishedPath = public_path('vendor/catchy/catchy.js');
            $dir = dirname($publishedPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            copy($this->tempBackupPath, $publishedPath);
            unlink($this->tempBackupPath);
        }

        parent::tearDown();
    }

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
     * Test that the 'catchy:install' console command is registered.
     */
    public function test_console_command_is_registered(): void
    {
        $commands = \Illuminate\Support\Facades\Artisan::all();

        $this->assertArrayHasKey('catchy:install', $commands);
        $this->assertInstanceOf(\Hamzi\Catchy\Console\InstallCommand::class, $commands['catchy:install']);
    }

    /**
     * Test that the @catchyScripts Blade directive renders to the inline script correctly.
     */
    public function test_blade_directive_renders_correctly(): void
    {
        $html = Blade::render('@catchyScripts');

        // Verify that the rendered HTML contains config settings, and inline plugin code
        $this->assertStringContainsString('window.CatchyConfig =', $html);
        $this->assertStringContainsString('Hamzi/Catchy - Alpine.js SPA Plugin', $html);
        $this->assertStringContainsString('CatchyPlugin', $html);
        $this->assertStringContainsString('window.history.pushState', $html);
    }

    /**
     * Test that the autoPublishAssets method copies the file if it doesn't exist.
     */
    public function test_auto_publish_assets_copies_file_in_local_env(): void
    {
        $publishedPath = public_path('vendor/catchy/catchy.js');

        // We make sure the file is removed
        if (file_exists($publishedPath)) {
            unlink($publishedPath);
        }

        $this->assertFalse(file_exists($publishedPath));

        $provider = new \Hamzi\Catchy\CatchyServiceProvider($this->app);
        
        $reflector = new \ReflectionClass(\Hamzi\Catchy\CatchyServiceProvider::class);
        $method = $reflector->getMethod('autoPublishAssets');
        $method->setAccessible(true);
        $method->invoke($provider);

        // Verify the file was copied
        $this->assertTrue(file_exists($publishedPath));
    }

    /**
     * Verify dependency injection contracts resolution from container.
     */
    public function test_contracts_are_resolvable_from_container(): void
    {
        $extractor = $this->app->make(ResponseExtractorInterface::class);
        $versionRepository = $this->app->make(VersionRepositoryInterface::class);
        $componentRepository = $this->app->make(ComponentRepositoryInterface::class);

        $this->assertInstanceOf(\Hamzi\Catchy\Infrastructure\Extractors\HtmlResponseExtractor::class, $extractor);
        $this->assertInstanceOf(\Hamzi\Catchy\Infrastructure\Repositories\AssetVersionRepository::class, $versionRepository);
        $this->assertInstanceOf(\Hamzi\Catchy\Infrastructure\Repositories\ConfigComponentRepository::class, $componentRepository);
    }
}
