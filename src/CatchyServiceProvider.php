<?php

declare(strict_types=1);

namespace Hamzi\Catchy;

use Hamzi\Catchy\Console\InstallCommand;
use Hamzi\Catchy\Domain\Contracts\ComponentRepositoryInterface;
use Hamzi\Catchy\Domain\Contracts\ResponseExtractorInterface;
use Hamzi\Catchy\Domain\Contracts\VersionRepositoryInterface;
use Hamzi\Catchy\Http\Middleware\CatchySPAMiddleware;
use Hamzi\Catchy\Infrastructure\Extractors\HtmlResponseExtractor;
use Hamzi\Catchy\Infrastructure\Repositories\AssetVersionRepository;
use Hamzi\Catchy\Infrastructure\Repositories\ConfigComponentRepository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Class CatchyServiceProvider
 *
 * Bootstraps package services, registers configuration mappings, binds clean architecture
 * interfaces to implementations, and defines the compiler directives.
 */
class CatchyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge default configuration
        $this->mergeConfigFrom(__DIR__.'/../config/catchy.php', 'catchy');

        // Bind contracts to implementations (Dependency Inversion Principle - DIP)
        $this->app->singleton(ResponseExtractorInterface::class, HtmlResponseExtractor::class);
        $this->app->singleton(VersionRepositoryInterface::class, AssetVersionRepository::class);
        $this->app->singleton(ComponentRepositoryInterface::class, ConfigComponentRepository::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->registerMiddleware();
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'catchy');
        $this->registerViewsAndComponents();
        $this->registerDirectives();
        $this->registerPublishing();
        $this->registerCommands();

        // Auto-publish assets in local environment if missing
        if ($this->app->environment('local') && ! $this->app->runningInConsole()) {
            $this->autoPublishAssets();
        }
    }

    /**
     * Register the SPA middleware alias.
     */
    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('catchy', CatchySPAMiddleware::class);

        // Automatically append the middleware to the 'web' group for ease of installation
        if ($this->app->bound(\Illuminate\Contracts\Http\Kernel::class)) {
            $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
            $kernel->appendMiddlewareToGroup('web', CatchySPAMiddleware::class);
        }
    }

    /**
     * Load views and register custom Blade UI components using the dynamic ComponentRepository.
     */
    protected function registerViewsAndComponents(): void
    {
        // Load package views (enables custom component resolution)
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'catchy');

        // Fetch dynamic component configurations from repository
        $repository = $this->app->make(ComponentRepositoryInterface::class);

        foreach ($repository->getComponents() as $view => $alias) {
            Blade::component("catchy::components.{$view}", $alias);
        }
    }

    /**
     * Register custom Blade compiler directives.
     */
    protected function registerDirectives(): void
    {
        // Register the form custom directive
        Blade::directive('catchyForm', function ($expression) {
            return '<?php echo \\Hamzi\\Catchy\\Support\\CatchyDirective::render('.($expression ?: '[]').'); ?>';
        });

        // Register the scripts/config injection directive
        Blade::directive('catchyScripts', function () {
            return "<?php echo view('catchy::scripts', ['jsPath' => \\Hamzi\\Catchy\\CatchyServiceProvider::getJsPath()])->render(); ?>";
        });
    }

    /**
     * Get the absolute path to the package's compiled catchy.js asset.
     */
    public static function getJsPath(): string
    {
        return __DIR__.'/../resources/js/catchy.js';
    }

    /**
     * Register console publishing tasks.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/catchy.php' => config_path('catchy.php'),
            ], 'catchy-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/catchy'),
            ], 'catchy-views');

            $this->publishes([
                __DIR__.'/../resources/lang' => lang_path('vendor/catchy'),
            ], 'catchy-translations');

            $this->publishes([
                __DIR__.'/../resources/js/catchy.js' => public_path('vendor/catchy/catchy.js'),
            ], 'catchy-assets');
        }
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Auto-publish compiled assets to public directory in local dev if they do not exist.
     */
    protected function autoPublishAssets(): void
    {
        $targetPath = public_path('vendor/catchy/catchy.js');
        $sourcePath = __DIR__.'/../resources/js/catchy.js';

        if (file_exists($sourcePath)) {
            $shouldCopy = ! file_exists($targetPath) || filemtime($sourcePath) > filemtime($targetPath);

            if ($shouldCopy) {
                $dir = dirname($targetPath);
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                copy($sourcePath, $targetPath);
            }
        }
    }
}
