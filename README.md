# Catchy: HTML-over-the-wire SPA for Laravel

`Hamzi/Catchy` is a premium, featherweight Single Page Application (SPA) adapter for Laravel, powered strictly by **Alpine.js** and the **`@alpinejs/morph`** plugin. It intercepts link clicks and form submissions, fetches HTML updates via AJAX, and dynamically morphs your DOM—bringing the lightning-fast feel of an SPA to standard Blade layouts without the complexity or weight of heavy JavaScript frameworks.

---

## Features

- **Featherweight footprint**: Pure Alpine.js plugin and a thin Laravel middleware.
- **HTML-over-the-wire**: Only transfers layout fragments over the network, dramatically saving bandwidth.
- **Form submission interception**: Intercepts `GET` and `POST` forms natively (including CSRF tokens and Laravel `_method` spoofing).
- **Graceful degradation**: Seamlessly falls back to regular page loads on server errors, external redirects, or slow connections.
- **Developer-friendly custom events**: Hook into the navigation lifecycle (`catchy:start`, `catchy:morphing`, `catchy:end`, `catchy:error`) to integrate loading indicators like NProgress.
- **Native browser navigation**: Full support for back/forward buttons (History API) and anchor scroll offsets.

---

## Installation

Install the package via Composer (ensure your local repository path is configured or require it directly):

```bash
composer require hamzi/catchy
```

*Note: In Laravel 11.x, 12.x, and 13.x, the package's service provider registers automatically.*

---

## Configuration

### 1. Register the Middleware
You need to apply the `catchy` middleware to the routes you want to behave as an SPA. 

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
If you wish to change the default HTML container wrapper ID (`catchy-app`), publish the config file:

```bash
php artisan vendor:publish --tag=catchy-config
```

This will create `config/catchy.php`:

```php
return [
    'container_id' => 'catchy-app',
];
```

---

## Setup & Frontend Integration

Catchy can be loaded dynamically via Blade directives (CDN mode) or bundled locally using modern assets managers like Vite (NPM mode).

### Method A: CDN/Blade Directive (Zero Configuration)
If you prefer not to compile assets via NPM, simply add the `@catchyScripts` Blade directive before the closing `</body>` tag in your layout. By default, it will automatically inject `@alpinejs/morph` from UNPKG CDN and initialize the plugin.

Ensure your layout has Alpine.js loaded:
```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

### Method B: NPM/Vite Bundler (Recommended for Production)
If you want to bundle Catchy inside your JavaScript build:

1. Turn off CDN morph auto-injection in `config/catchy.php`:
```php
'include_morph' => false,
```

2. Install the frontend asset repository using NPM:
```bash
npm install ../catchy
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

### Step 2: Prepare Your Layout
Wrap the dynamic page-changing content inside the matching container element (e.g., `<main id="catchy-app">`) in your parent layout (e.g., `layouts/app.blade.php`), and add the `@catchyScripts` directive.

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900">

    <header class="bg-white shadow">
        <nav class="max-w-7xl mx-auto px-4 py-4 flex gap-4">
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/contact">Contact</a>
        </nav>
    </header>

    <!-- The Catchy App Wrapper -->
    <main id="catchy-app">
        @yield('content')
    </main>

    <!-- Inject the Catchy Alpine.js Plugin -->
    @catchyScripts
</body>
</html>
```

---

## Usage Guide

Once registered, **Catchy** intercepts all same-origin link clicks and form submissions automatically.

### Excluding Specific Links or Forms
To disable Catchy routing for a specific link or form (causing a full page reload instead), add the `data-catchy-ignore` attribute:

```html
<!-- This link will trigger a standard browser page load -->
<a href="/logout" data-catchy-ignore>Log Out</a>

<!-- This form will post with a standard page reload -->
<form action="/upload" method="POST" enctype="multipart/form-data" data-catchy-ignore>
    ...
</form>
```

### Programmatic Navigation
You can trigger programmatic SPA transitions using the global Alpine helper:

```javascript
Alpine.catchy.visit('/some-path');
```

---

## Reusable Blade UI Components

Catchy includes professionally styled, fully customizable Blade components for common UI patterns.

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
Responsive modal with backdrop blur, scale transitions, and keyboard closing:
```html
<!-- Place once in your layout -->
<x-catchy-modal id="my-modal" size="lg" />

<!-- Trigger via SPA link -->
<a href="/users/1/edit" data-catchy-modal>Edit User</a>
```
Props: `id`, `title`, `size` (`sm`–`5xl`, `full`), `closeOnOutsideClick`

### 6. Toast Notifications (`<x-catchy-toast />`)
Displays session flash messages and dynamic SPA notifications:
```html
<!-- Place once in your layout -->
<x-catchy-toast position="top-right" duration="4000" />
```
Props: `position` (`top-right`, `top-left`, `bottom-right`, `bottom-left`), `duration`

Laravel session flash messages (`success`, `error`, `warning`, `info`, `status`) display automatically. During SPA navigation, flash messages are forwarded via the `X-Catchy-Flash` response header.

---

## Advanced Features

### `@catchyForm` Blade Directive
Apply lifecycle callbacks to any standard form:
```html
<form action="/submit" method="POST" @catchyForm(['beforesend' => 'onBefore', 'success' => 'onSuccess', 'error' => 'onError'])>
    @csrf
    <!-- fields -->
</form>
```

### Dynamic Container Targeting
Morph a different container instead of the default:
```html
<a href="/sidebar-content" data-catchy-target="sidebar">Load in Sidebar</a>
```

### Disable History Updates
```html
<a href="/live-preview" data-catchy-history="false">Preview</a>
```

### Modal Routing
```html
<a href="/users/create" data-catchy-modal>New User</a>
```
Forms submitted inside the modal automatically close it and morph the main page container on success.

### Form Callback Attributes
```html
<form action="/submit" method="POST"
    data-catchy-beforesend="showLoading()"
    data-catchy-success="hideLoading()"
    data-catchy-error="handleError()">
    @csrf
</form>
```

---

## Developer Experience (DX) & Custom Events

| Event | When | Cancelable |
|---|---|---|
| `catchy:start` | Navigation begins | ✅ |
| `catchy:morphing` | About to morph the DOM | ❌ |
| `catchy:end` | Navigation completed | ❌ |
| `catchy:error` | An error occurred | ❌ |
| `catchy:flash` | Flash messages received (on `window`) | ❌ |

### Integrating NProgress
```javascript
import NProgress from 'nprogress';
document.addEventListener('catchy:start', () => NProgress.start());
document.addEventListener('catchy:end', () => NProgress.done());
document.addEventListener('catchy:error', () => NProgress.done());
```

### Cancel Navigation
```javascript
document.addEventListener('catchy:start', (event) => {
    if (hasUnsavedChanges && !confirm('Abandon changes?')) {
        event.preventDefault();
    }
});
```

---

## Publishing & Customization

```bash
# Publish config
php artisan vendor:publish --tag=catchy-config

# Publish views (to customize components)
php artisan vendor:publish --tag=catchy-views
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

