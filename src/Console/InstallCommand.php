<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Class InstallCommand
 *
 * Handles publishing configurations, JS assets, translation bundles,
 * and generates a boilerplate SPA starter layout.
 *
 * @package Hamzi\Catchy\Console
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catchy:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure Hamzi/Catchy Laravel SPA package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info("\n⚡ Installing Hamzi/Catchy — SPA Package ⚡");

        // 1. Publish Configuration
        $this->comment('Publishing Catchy configuration file...');
        $this->call('vendor:publish', [
            '--provider' => 'Hamzi\Catchy\CatchyServiceProvider',
            '--tag' => 'catchy-config',
            '--force' => true,
        ]);

        // 2. Publish Assets
        $this->comment('Publishing compiled JavaScript assets...');
        $this->call('vendor:publish', [
            '--provider' => 'Hamzi\Catchy\CatchyServiceProvider',
            '--tag' => 'catchy-assets',
            '--force' => true,
        ]);

        // 3. Optional Views publishing
        if ($this->confirm('Do you want to publish the Blade views and components to customize their templates?', false)) {
            $this->comment('Publishing Blade components...');
            $this->call('vendor:publish', [
                '--provider' => 'Hamzi\Catchy\CatchyServiceProvider',
                '--tag' => 'catchy-views',
            ]);
        }

        // 4. Optional Translation publishing
        if ($this->confirm('Do you want to publish the translation files to customize languages?', false)) {
            $this->comment('Publishing translation files...');
            $this->call('vendor:publish', [
                '--provider' => 'Hamzi\Catchy\CatchyServiceProvider',
                '--tag' => 'catchy-translations',
            ]);
        }

        // 5. Generate SPA starter layout
        if ($this->confirm('Do you want to generate a pre-configured SPA layouts template?', true)) {
            $this->generateLayout();
        }

        $this->info("\n Hamzi/Catchy has been installed successfully!");
        $this->info("Make sure to register CatchySPAMiddleware in bootstrap/app.php or apply the 'catchy' middleware group to your routes.");
        
        $this->comment("\n💡 JavaScript Integration Options:");
        $this->line("  1. [Standalone Mode]: `@catchyScripts` will load the pre-compiled asset directly.");
        $this->line("  2. [Vite/NPM Mode]: If you want to bundle Catchy inside your app.js, run:");
        $this->info("     npm install");
        $this->line("     And register the plugin in resources/js/app.js:");
        $this->comment("     import CatchyPlugin from 'hamzi-catchy';");
        $this->comment("     Alpine.plugin(CatchyPlugin);\n");

        return 0;
    }

    /**
     * Generate the starter layout file.
     *
     * @return void
     */
    protected function generateLayout(): void
    {
        $layoutPath = resource_path('views/layouts/catchy.blade.php');

        if (File::exists($layoutPath)) {
            if (!$this->confirm("The layout file [layouts/catchy.blade.php] already exists. Do you want to overwrite it?", false)) {
                $this->warn('Skipped layout generation.');
                return;
            }
        }

        $directory = dirname($layoutPath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $content = <<<'HTML'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'he', 'fa', 'ur']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel SPA') }}</title>

    <!-- Styles & Scripts compiled via Vite (NPM dependencies) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 min-h-screen antialiased">

    <!-- Top Loading Bar (Interacts with SPA visits) -->
    <x-catchy-progress color="gradient" height="h-1" :show-percent="false" />

    <!-- Global Toast Container (Shows session flash notices) -->
    <x-catchy-toast position="top-right" duration="4000" />

    <!-- Main SPA Container (Morphed on page transitions) -->
    <div id="catchy-app" class="min-h-screen flex flex-col justify-between">
        @yield('content')
    </div>

    <!-- Global Modal Dialog (Declarative SPA modals) -->
    <x-catchy-modal id="catchy-modal" size="md" />

    <!-- Injects Catchy SPA scripts & Alpine configuration -->
    @catchyScripts

</body>
</html>
HTML;

        File::put($layoutPath, $content);
        $this->info("Created starter layout file: [resources/views/layouts/catchy.blade.php]");
    }
}
