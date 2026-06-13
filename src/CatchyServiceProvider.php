<?php

namespace Hamzi\Catchy;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Hamzi\Catchy\Http\Middleware\CatchySPAMiddleware;
use Hamzi\Catchy\Contracts\ResponseExtractorInterface;
use Hamzi\Catchy\Extractors\HtmlResponseExtractor;
use Hamzi\Catchy\Contracts\VersionProviderInterface;
use Hamzi\Catchy\Providers\AssetVersionProvider;

/**
 * Class CatchyServiceProvider
 *
 * Bootstraps package services, registers configuration mappings, binds clean architecture
 * interfaces to implementations, and defines the compiler directives.
 *
 * @package Hamzi\Catchy
 */
class CatchyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge default configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/catchy.php', 'catchy');

        // Bind contracts to implementations to satisfy Dependency Inversion (DIP)
        $this->app->bind(ResponseExtractorInterface::class, HtmlResponseExtractor::class);
        $this->app->singleton(VersionProviderInterface::class, AssetVersionProvider::class);
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerMiddleware();
        $this->registerViewsAndComponents();
        $this->registerDirectives();
        $this->registerPublishing();
    }

    /**
     * Register the SPA middleware alias.
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('catchy', CatchySPAMiddleware::class);
    }

    /**
     * Load views and register custom Blade UI components.
     *
     * @return void
     */
    protected function registerViewsAndComponents(): void
    {
        // Load package views (enables custom component resolution)
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'catchy');

        // Register custom Blade component tags dynamically
        $components = [
            'spinner'  => 'catchy-spinner',
            'skeleton' => 'catchy-skeleton',
            'fade'     => 'catchy-fade',
            'form'     => 'catchy-form',
            'modal'    => 'catchy-modal',
            'toast'    => 'catchy-toast',
        ];

        foreach ($components as $view => $alias) {
            Blade::component("catchy::components.{$view}", $alias);
        }
    }

    /**
     * Register custom Blade compiler directives.
     *
     * @return void
     */
    protected function registerDirectives(): void
    {
        // Register the form custom directive
        Blade::directive('catchyForm', function ($expression) {
            return "<?php echo \\Hamzi\\Catchy\\CatchyDirective::render(" . ($expression ?: '[]') . "); ?>";
        });

        // Register the scripts/config injection directive
        Blade::directive('catchyScripts', function () {
            $path = __DIR__ . '/../resources/js/catchy.js';
            return "<?php echo view('catchy::scripts', ['jsPath' => '{$path}'])->render(); ?>";
        });
    }

    /**
     * Register console publishing tasks.
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/catchy.php' => config_path('catchy.php'),
            ], 'catchy-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/catchy'),
            ], 'catchy-views');
        }
    }
}
