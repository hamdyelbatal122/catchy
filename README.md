<h1 align="center">Catchy</h1>

<p align="center">
  <strong>A featherweight Single Page Application (SPA) adapter for Laravel 11, 12 & 13</strong>
</p>

<p align="center">
  <a href="https://github.com/hamdyelbatal122/catchy/releases"><img src="https://img.shields.io/github/v/release/hamdyelbatal122/catchy?style=flat-square&color=blue" alt="Latest Version"></a>
  <a href="https://github.com/hamdyelbatal122/catchy/actions/workflows/run-tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/hamdyelbatal122/catchy/run-tests.yml?branch=main&style=flat-square&label=tests" alt="GitHub Tests Action Status"></a>
  <a href="https://packagist.org/packages/hamzi/catchy"><img src="https://img.shields.io/packagist/dt/hamzi/catchy?style=flat-square&color=goldenrod" alt="Total Downloads"></a>
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
- **Deferred / Lazy Component Loading**: Lazy load heavy components (`<x-catchy-lazy>`) when they enter the viewport using `IntersectionObserver`.
- **Intelligent Viewport Prefetching**: Prefetch links dynamically in the background as they enter the viewport using `data-catchy-prefetch="viewport"`.
- **Scroll Retention Control**: Easily bypass the default scroll-to-top behavior on transitions using `data-catchy-scroll="keep"`.
- **Inline Validation Error Management**: Render dynamic field error elements (`<x-catchy-error>`) that display Laravel validation messages on the fly.
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

### 7. Validation Error Indicator (`<x-catchy-error />`)
Automatically shows dynamic validation messages inline when validation fails on a form submit without full page reloads:
```html
<label for="email">Email Address</label>
<input type="email" name="email" id="email">
<!-- Displays error message for the 'email' input on form validation failure -->
<x-catchy-error field="email" class="text-rose-500 text-xs mt-1" />
```

### 8. Lazy Component Loader (`<x-catchy-lazy />`)
Allows you to load heavy elements (e.g. dynamic cards, charts, dashboards) asynchronously.
```html
<!-- Renders placeholder, then immediately loads contents from /widgets/activity -->
<x-catchy-lazy src="/widgets/activity" />

<!-- Lazy loads content only when the element is scrolled into view (using IntersectionObserver) -->
<x-catchy-lazy src="/widgets/recent-stats" trigger="intersect" />

<!-- With custom placeholder -->
<x-catchy-lazy src="/widgets/orders" trigger="intersect">
    <x-slot:placeholder>
        <div class="p-4 bg-gray-100 rounded-lg text-center animate-pulse">Loading orders...</div>
    </x-slot:placeholder>
</x-catchy-lazy>
```

### 9. Off-Canvas Drawer (`<x-catchy-offcanvas />`)
A slide-in sidebar panel that enters from viewport edges (left, right, start, end, top, bottom), styled with modern Tailwind CSS:
```html
<!-- Place once in your layout -->
<x-catchy-offcanvas id="my-drawer" title="Filters" direction="right" />

<!-- Trigger via SPA link -->
<a href="/filters" data-catchy-offcanvas="my-drawer">Open Filters Drawer</a>
```
Options:
- `id` (string): The DOM element ID. Defaults to `catchy-offcanvas`.
- `title` (string): Header text.
- `direction` (`left` | `right` | `start` | `end` | `top` | `bottom`): Viewport entry direction (RTL/LTR logical property safe).
- `closeOnOutsideClick` (boolean): Closes when clicking the backdrop. Defaults to `true`.

### 10. Button (`<x-catchy-button />`)
A highly customizable button featuring color variants, size variants, hover transitions, and automatic loading spinner integration:
```html
<x-catchy-button variant="primary" size="md">Save Changes</x-catchy-button>
<x-catchy-button variant="danger" size="sm" :loading="false">Delete</x-catchy-button>
```
Options:
- `type` (`button` | `submit`): HTML button type. Defaults to `button`.
- `variant` (`primary` | `secondary` | `success` | `danger` | `outline` | `ghost`): Defaults to `primary`.
- `size` (`sm` | `md` | `lg`): Defaults to `md`.
- `loading` (boolean): If `true`, the button inherits loading state from the parent intercepting form and automatically shows a spinner during SPA submission transitions. Defaults to `true`.

### 11. Card (`<x-catchy-card />`)
A structured content container featuring optional hover scaling and transitions, fully dark-mode friendly:
```html
<x-catchy-card hoverable>
    <x-slot:header>
        <h3 class="font-semibold text-slate-900 dark:text-white">Card Title</h3>
    </x-slot:header>
    
    <p class="text-slate-600 dark:text-slate-400">This is the main card body content.</p>
    
    <x-slot:footer>
        <span class="text-xs text-slate-400">Last updated 5m ago</span>
    </x-slot:footer>
</x-catchy-card>
```
Options:
- `hoverable` (boolean): Adds scale-up hover animation and card border highlight. Defaults to `false`.

### 12. Alert Banner (`<x-catchy-alert />`)
A dismissible feedback banner for success, warning, or error information with Alpine-based fade transitions:
```html
<x-catchy-alert type="success" :dismissible="true">
    Your profile has been updated successfully!
</x-catchy-alert>
```
Options:
- `type` (`success` | `danger` | `warning` | `info`): Defaults to `info`.
- `dismissible` (boolean): Shows a close button to fade out the alert. Defaults to `true`.

### 13. Badge (`<x-catchy-badge />`)
A small tag component for status indicators and metadata labels:
```html
<x-catchy-badge variant="success" rounded>Active</x-catchy-badge>
<x-catchy-badge variant="danger" size="sm">Failed</x-catchy-badge>
```
Options:
- `variant` (`primary` | `secondary` | `success` | `danger` | `warning` | `info`): Defaults to `primary`.
- `size` (`sm` | `md`): Defaults to `md`.
- `rounded` (boolean): Uses fully rounded (pill) border-radius if set to `true`. Defaults to `false`.

### 14. Dropdown Menu (`<x-catchy-dropdown />`)
An Alpine.js-powered dropdown wrapper that handles toggling, outside clicks, and logical direction configurations:
```html
<x-catchy-dropdown align="end" width="w-48">
    <x-slot:trigger>
        <button class="px-4 py-2 bg-slate-100 rounded-lg">Options</button>
    </x-slot:trigger>
    
    <x-slot:content>
        <a href="/profile" class="block px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">Profile</a>
        <a href="/settings" class="block px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">Settings</a>
    </x-slot:content>
</x-catchy-dropdown>
```
Options:
- `align` (`left` | `right` | `start` | `end`): Position of dropdown menu relative to the trigger. Defaults to `start` (RTL/LTR direction logical property friendly).
- `width` (string): Tailwind width class. Defaults to `w-48`.

### 15. Input Field (`<x-catchy-input />`)
A standard form text input component equipped with modern styling, labels, and automatic inline validation error messages:
```html
<x-catchy-input 
    name="email" 
    type="email" 
    label="Email Address" 
    placeholder="you@example.com" 
    required 
    helper="We'll never share your email." 
/>
```
Options:
- `name` (string, required): The input field name and ID.
- `label` (string): Text for the field label. Shows a red `*` symbol if `required` is true.
- `type` (string): The input type (e.g. `text`, `email`, `password`, `number`). Defaults to `text`.
- `placeholder` (string): Placeholder text.
- `value` (string): Initial field value.
- `required` (boolean): Marks input field as required. Defaults to `false`.
- `helper` (string): Additional helper text printed underneath the input field.
- **Note**: This component automatically embeds the `<x-catchy-error :field="$name" />` inline warning tag, meaning any Laravel validation failure on this field will display the error instantly.

---

## Advanced Options & APIs

### Declarative Trigger Actions (Modal & Drawer Actions)
Catchy allows you to open or close Modals and Off-Canvas Drawers declaratively from click actions or upon form submissions success/failure without writing any Alpine or custom JS.

#### 1. On Click Actions
Add these attributes to buttons, links, or any clickable element to toggle modals or drawers:
- `data-catchy-open-modal="modal-id"`: Opens the specified modal.
- `data-catchy-close-modal="modal-id"`: Closes the specified modal.
- `data-catchy-open-offcanvas="drawer-id"`: Opens the specified drawer.
- `data-catchy-close-offcanvas="drawer-id"`: Closes the specified drawer.

Example:
```html
<!-- Opens a modal on click -->
<button data-catchy-open-modal="auth-modal" class="btn">Login</button>

<!-- Closes the modal from inside or outside -->
<button data-catchy-close-modal="auth-modal">Cancel</button>
```

#### 2. On Form Success / Error Actions
Apply these attributes directly to form tags (e.g. `<x-catchy-form>` or a regular form) to trigger components automatically depending on the request outcome:
- `data-catchy-success-open-modal="modal-id"`: Opens modal when form submits successfully.
- `data-catchy-success-close-modal="modal-id"`: Closes modal when form submits successfully (ideal for editing forms inside modals).
- `data-catchy-success-open-offcanvas="drawer-id"` / `data-catchy-success-close-offcanvas="drawer-id"`: Triggers off-canvas on successful form submit.
- `data-catchy-error-open-modal="modal-id"`: Opens error details modal on failure.

Example:
```html
<!-- Form inside a modal that automatically closes the modal on success -->
<x-catchy-form action="/update-profile" method="POST" data-catchy-success-close-modal="edit-profile-modal">
    <input type="text" name="name" required>
    <button type="submit">Update</button>
</x-catchy-form>
```

### Scroll Position Control
By default, Catchy scrolls the viewport to top on page transition. You can keep the current scroll position by adding `data-catchy-scroll="keep"` to links or form elements:
```html
<a href="/tab/2" data-catchy-scroll="keep">Open Tab 2</a>
```

### Viewport Prefetching
Catchy can prefetch pages dynamically when links enter the viewport (similar to modern static-site generators). Add `data-catchy-prefetch="viewport"` to enable viewport-level prefetching on an anchor:
```html
<a href="/heavy-page" data-catchy-prefetch="viewport">Heavy Dashboard</a>
```

### Programmatic Loader Controls
You can programmatically trigger the global loading progress bar using Alpine's exposed global space:
```javascript
// Start the loader
Alpine.catchy.startLoading();

// Stop/finish the loader
Alpine.catchy.stopLoading();
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
