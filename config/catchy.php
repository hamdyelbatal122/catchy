<?php

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
    | empty to let the AssetVersionProvider automatically hash the production
    | Vite build/manifest.json file. Set to null to disable version checks.
    |
    | Default: '' (Auto-resolve Vite build manifests)
    |
    */

    'version' => '',

    /*
    |--------------------------------------------------------------------------
    | Include Alpine Morph CDN Script
    |--------------------------------------------------------------------------
    |
    | When set to true, the @catchyScripts Blade directive will automatically
    | inject the @alpinejs/morph plugin CDN script. If you already bundle
    | the Morph plugin locally in your JavaScript bundle (e.g. via Vite),
    | set this to false to avoid loading the CDN script.
    |
    | Default: true
    |
    */

    'include_morph' => true,

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
    | Built-in CSS Viewport Progress Loader
    |--------------------------------------------------------------------------
    |
    | Configure the built-in, premium viewport loading progress bar.
    |
    */

    'loading_bar' => [
        'enabled' => true,
        'height' => '3px',   // Loading bar thickness
        'color' => 'linear-gradient(to right, #4f46e5, #06b6d4)', // CSS color/gradient
    ],

];
