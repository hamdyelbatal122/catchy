<?php

use Hamzi\Catchy\Http\Middleware\Pipeline\AppendResponseHeaders;
use Hamzi\Catchy\Http\Middleware\Pipeline\ExtractResponseContainer;
use Hamzi\Catchy\Http\Middleware\Pipeline\HandleRedirectResponse;
use Hamzi\Catchy\Http\Middleware\Pipeline\VerifyAssetVersion;

return [

    /*
    |--------------------------------------------------------------------------
    | Catchy Container ID
    |--------------------------------------------------------------------------
    |
    | This option defines the default DOM element ID that wraps the dynamic
    | page content of your application. The middleware will extract this
    | element when an SPA request is detected, and the Alpine.js plugin
    | will morph this element's contents/attributes on the frontend.
    |
    | Default: 'catchy-app'
    |
    */

    'container_id' => 'catchy-app',

    /*
    |--------------------------------------------------------------------------
    | Catchy Asset Version
    |--------------------------------------------------------------------------
    |
    | Under the hood, Catchy can trace application build differences (similar to
    | Inertia.js). If you update your CSS/JS assets, Catchy can detect a mismatch
    | between client and server versions, forcing a clean browser page reload
    | to load the latest builds.
    |
    | You can define a static string here (like a release number), or leave it
    | empty to let the AssetVersionRepository automatically hash the production
    | Vite build/manifest.json file. Set to null to disable version checks.
    |
    | Default: '' (Auto-resolve Vite build manifests)
    |
    */

    'version' => '',

    /*
    |--------------------------------------------------------------------------
    | Hover Prefetch Settings
    |--------------------------------------------------------------------------
    |
    | Catchy can prefetch link contents in the background when the user hovers
    | over a link, providing instantaneous page loads on click.
    |
    */

    'prefetch' => [
        'enabled' => true,
        'delay' => 75,       // Hover delay in milliseconds to verify user intent
        'ttl' => 30000,      // Cache lifetime in milliseconds (default: 30s)
    ],

    /*
    |--------------------------------------------------------------------------
    | Stale-While-Revalidate (SWR) Caching
    |--------------------------------------------------------------------------
    |
    | When enabled, Catchy will serve cached pages instantly and fetch fresh
    | copies in the background, morphing the DOM when updates complete.
    |
    */

    'swr' => true,

    /*
    |--------------------------------------------------------------------------
    | Built-in CSS Viewport Progress Loader
    |--------------------------------------------------------------------------
    |
    | Configure the built-in viewport loading progress bar.
    |
    */

    'loading_bar' => [
        'enabled' => true,
        'height' => '3px',   // Loading bar thickness
        'color' => 'linear-gradient(to right, #4f46e5, #06b6d4)', // CSS color/gradient
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Pipeline Stages
    |--------------------------------------------------------------------------
    |
    | The middleware filters requests through these stages. You can customize,
    | append, or swap stages to inject custom logic (e.g. tracking, logs)
    | inside your SPA application routing cycle.
    |
    */

    'pipeline' => [
        VerifyAssetVersion::class,
        HandleRedirectResponse::class,
        AppendResponseHeaders::class,
        ExtractResponseContainer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dynamic UI Blade Components
    |--------------------------------------------------------------------------
    |
    | Map the built-in package component views to their corresponding
    | HTML tags. This allows customizing, styling, or swapping components
    | without modifying core package source files.
    |
    */

    'components' => [
        'spinner' => 'catchy-spinner',
        'skeleton' => 'catchy-skeleton',
        'fade' => 'catchy-fade',
        'form' => 'catchy-form',
        'modal' => 'catchy-modal',
        'toast' => 'catchy-toast',
        'progress' => 'catchy-progress',
        'upload' => 'catchy-upload',
        'error' => 'catchy-error',
        'lazy' => 'catchy-lazy',
        'offcanvas' => 'catchy-offcanvas',
        'button' => 'catchy-button',
        'card' => 'catchy-card',
        'alert' => 'catchy-alert',
        'badge' => 'catchy-badge',
        'dropdown' => 'catchy-dropdown',
        'input' => 'catchy-input',
        'textarea' => 'catchy-textarea',
        'select' => 'catchy-select',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Routes
    |--------------------------------------------------------------------------
    |
    | Define URI patterns that should skip SPA routing. Useful for webhook URLs,
    | stripe callback routes, payment gateways, or custom admin packages.
    |
    */

    'except' => [
        // 'api/*',
        // 'stripe/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Styling Preset
    |--------------------------------------------------------------------------
    |
    | Supported presets: "tailwind", "bootstrap", "vanilla", "custom"
    |
    | If set to "tailwind", "bootstrap", or "vanilla", components will load the
    | corresponding stylesheet class preset. Set to "custom" to manage your
    | own custom styles inside the 'styles' array below.
    |
    | Default: "tailwind"
    |
    */

    'preset' => 'tailwind',

    /*
    |--------------------------------------------------------------------------
    | Component Style Overrides
    |--------------------------------------------------------------------------
    |
    | Customize or override style classes for individual package components.
    | If you want to change colors, sizing, or spacing to match your theme,
    | you can define custom classes here, which will override the active preset.
    |
    */


];
