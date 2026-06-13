<h1 align="center">Laravel Catchy</h1>

<p align="center">
  <strong>A featherweight Single Page Application (SPA) adapter for Laravel 11, 12 & 13</strong>
</p>

<p align="center">
  <a href="https://github.com/hamdyelbatal122/catchy/releases"><img src="https://img.shields.io/github/v/release/hamdyelbatal122/catchy?style=flat-square&color=blue" alt="Latest Version"></a>
  <a href="https://github.com/hamdyelbatal122/catchy/actions/workflows/run-tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/hamdyelbatal122/catchy/run-tests.yml?branch=main&style=flat-square&label=tests" alt="GitHub Tests Action Status"></a>
  <a href="https://github.com/hamdyelbatal122/catchy/releases"><img src="https://img.shields.io/github/downloads/hamdyelbatal122/catchy/total?style=flat-square&color=goldenrod" alt="Total Downloads"></a>
  <img src="https://img.shields.io/badge/php-%5E8.2%20%7C%20%5E8.3%20%7C%20%5E8.4-blue?style=flat-square" alt="PHP Version">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-green?style=flat-square" alt="License"></a>
</p>

---

**Laravel Catchy** empowers you to convert standard Laravel applications into SPAs using **Alpine.js** and the **`@alpinejs/morph`** plugin.

No complex JavaScript builds. No routing overhead. **100% SEO-friendly. Dynamic Head/Meta updates. Script tag execution. Dynamic translation and native RTL support.**

---

## Features

- **Featherweight footprint**: Pure Alpine.js plugin and a thin Laravel middleware.
- **HTML-over-the-wire**: Only transfers layout fragments over the network, dramatically saving bandwidth.
- **Dynamic SEO/Head Merging**: Automatically syncs incoming `<head>` elements (meta description, keywords, OpenGraph, dynamic styles) to maintain 100% SEO parity.
- **Script Tag Execution**: Intercepts and executes dynamic `<script>` elements found inside the morphed content automatically.
- **Form submission interception**: Intercepts `GET` and `POST` forms natively (including CSRF tokens and Laravel `_method` spoofing).
- **Logical LTR/RTL Layouts**: UI Components are designed using Tailwind logical properties (`start`, `end`, `ms-`, `me-`, `text-start`) to support both LTR and RTL directions seamlessly out-of-the-box.
- **Localization Integration**: Translatable component strings, fully customizable via standard Laravel language publishing.
- **Optimized Caching & CDN**: Offers in-memory directive caching and asset publishing to let the browser cache the script file, saving ~40KB of HTML payload per page load.
- **Graceful degradation**: Seamlessly falls back to regular page loads on server errors, external redirects, or slow connections.

---

## Installation

Install the package via Composer:

```bash
composer require hamzi/catchy
```

*Note: In Laravel 11.x, 12.x, and 13.x, the package's service provider registers automatically.*

---

## Configuration

### 1. Register the Middleware
Apply the `catchy` middleware to the routes you want to behave as an SPA. 

For Laravel 11.x/12.x/13.x, register the middleware globally or inside the `web` middleware group in `bootstrap/app.php`:

```php
use Hamzi\Catchy\Http\Middleware\CatchySPAMiddleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        CatchySPAMiddleware::class,
    ]);
})
```

Alternatively, you can apply it only to specific routes/groups using the `catchy` alias:

```php
Route::middleware('catchy')->group(function () {
    Route::get('/', [HomeController::class, 'index']);
    Route::get('/about', [AboutController::class, 'show']);
});
```

### 2. Publish Configuration (Optional)
If you wish to change the default HTML container wrapper ID (`catchy-app`) or prefetching thresholds:

```bash
php artisan vendor:publish --tag=catchy-config
```

### 3. Publish Assets for Caching (Highly Recommended)
To prevent inlining JavaScript in every page response and allow browsers to cache the core script, publish the JS asset:

```bash
php artisan vendor:publish --tag=catchy-assets
```
This serves Catchy via `<script src="/vendor/catchy/catchy.js">`, reducing the HTML size of every page request by ~40KB.

### 4. Publish Translations (Optional)
To customize or translate the component text labels (e.g. for multi-language apps):

```bash
php artisan vendor:publish --tag=catchy-translations
```

---

## Setup & Frontend Integration

Catchy can be loaded dynamically via Blade directives (CDN mode) or bundled locally using modern asset managers like Vite (NPM mode).

### Method A: CDN/Blade Directive (Zero Configuration)
If you prefer not to compile assets via NPM, simply add the `@catchyScripts` Blade directive before the closing `</body>` tag in your layout. By default, it will automatically inject `@alpinejs/morph` from UNPKG CDN and initialize the plugin.

Ensure your layout has Alpine.js loaded:
```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

### Method B: NPM/Vite Bundler (Recommended for Production)
1. Turn off CDN morph auto-injection in `config/catchy.php`:
```php
'include_morph' => false,
```

2. Install Alpine.js, the morph plugin, and Catchy via NPM:
```bash
npm install alpinejs @alpinejs/morph
npm install hamzi-catchy
```

3. Import and register the plugins in your `resources/js/app.js`:
```javascript
import Alpine from 'alpinejs';
import morph from '@alpinejs/morph';
import Catchy from 'hamzi-catchy';

Alpine.plugin(morph);
Alpine.plugin(Catchy);

window.Alpine = Alpine;
Alpine.start();
```

---

## Reusable Blade UI Components

Catchy includes professionally styled, logical RTL/LTR, translatable Blade components.

### 1. Spinner (`<x-catchy-spinner />`)
An animated SVG spinner for inline loaders or buttons:
```html
<x-catchy-spinner />
<x-catchy-spinner size="sm" color="accent" />
```
Options: `size` (`xs`, `sm`, `md`, `lg`, `xl`) · `color` (`primary`, `accent`, `white`, `gray`)

### 2. Skeleton Loader (`<x-catchy-skeleton />`)
```html
<x-catchy-skeleton type="title" />
<x-catchy-skeleton type="text" lines="3" />
<x-catchy-skeleton type="circle" />
<x-catchy-skeleton type="card" />
```

### 3. Fade-in Transition (`<x-catchy-fade />`)
```html
<x-catchy-fade duration="350">
    <div class="card">Content fades in smoothly.</div>
</x-catchy-fade>
```

### 4. SPA Form (`<x-catchy-form />`)
Automates CSRF tokens, method spoofing, and lifecycle callbacks:
```html
<x-catchy-form action="/profile" method="PUT"
    beforesend="loading = true"
    success="loading = false; alert('Saved!')"
    error="loading = false">
    <input type="text" name="name">
    <button type="submit">Save</button>
</x-catchy-form>
```

### 5. Modal Dialog (`<x-catchy-modal />`)
Responsive modal with backdrop blur, scale transitions, keyboard closing, and automatic `text-start` direction alignment:
```html
<!-- Place once in your layout -->
<x-catchy-modal id="my-modal" size="lg" />

<!-- Trigger via SPA link -->
<a href="/users/1/edit" data-catchy-modal>Edit User</a>
```

### 6. Toast Notifications (`<x-catchy-toast />`)
Displays session flash messages and dynamic SPA notifications with support for logical alignments (`top-right` resolves to top-right on LTR but top-left on RTL):
```html
<!-- Place once in your layout -->
<x-catchy-toast position="top-right" duration="4000" />
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
