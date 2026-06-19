<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Tests;

use Illuminate\Support\Facades\File;

/**
 * Class InstallCommandTest
 *
 * Verifies the catchy:install command runs successfully, publishes required assets,
 * and generates the starter layout template based on developer choice.
 *
 * @package Hamzi\Catchy\Tests
 */
class InstallCommandTest extends TestCase
{
    /**
     * Clean up generated files after each test run.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $layoutPath = resource_path('views/layouts/catchy.blade.php');
        if (File::exists($layoutPath)) {
            File::delete($layoutPath);
        }

        parent::tearDown();
    }

    /**
     * Test the installation command generates the layout file correctly.
     *
     * @return void
     */
    public function test_install_command_generates_layout(): void
    {
        $layoutPath = resource_path('views/layouts/catchy.blade.php');
        
        // Ensure layout does not exist initially
        if (File::exists($layoutPath)) {
            File::delete($layoutPath);
        }

        $this->assertFalse(File::exists($layoutPath));

        // Call the installer command and mock interactions
        $this->artisan('catchy:install')
            ->expectsConfirmation('Do you want to publish the Blade views and components to customize their templates?', 'no')
            ->expectsConfirmation('Do you want to publish the translation files to customize languages?', 'no')
            ->expectsConfirmation('Do you want to generate a pre-configured SPA layouts template?', 'yes')
            ->assertExitCode(0);

        $this->assertTrue(File::exists($layoutPath));
        $this->assertStringContainsString('id="catchy-app"', File::get($layoutPath));
        $this->assertStringContainsString('@catchyScripts', File::get($layoutPath));
        $this->assertStringContainsString('<x-catchy-progress', File::get($layoutPath));
        $this->assertStringContainsString('<x-catchy-toast', File::get($layoutPath));
        $this->assertStringContainsString('@vite', File::get($layoutPath));
        $this->assertStringNotContainsString('tailwindcss.com', File::get($layoutPath));
    }
}
