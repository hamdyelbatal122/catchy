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

**Laravel Catchy** converts standard Laravel applications into high-performance SPAs using **Alpine.js** and **`@alpinejs/morph`**. No complex JS builds, 100% SEO-friendly, dynamic meta updates, logical LTR/RTL support, and instant navigation.

---

## ⚡ Core Features

- **HTML-over-the-wire**: Only layout changes are sent over the network, saving bandwidth.
- **Dynamic SEO/Head Merging**: Seamlessly synchronizes meta descriptions, titles, scripts, and styles.
- **Form Interception**: Automatically intercepts GET & POST forms (handles CSRF & methods spoofing).
- **RTL/LTR Support**: UI Components use Tailwind logical properties (`start`, `end`, `ms-`, `me-`).
- **Data Syncing (`x-catchy-sync`)**: Real-time two-way backend syncing (ideal for live search and auto-saving).
- **Graceful Degradation**: Automatically falls back to standard page requests on connection errors.

---

## 🚀 Installation & Setup

### 1. Install Package
```bash
composer require hamzi/catchy
```

### 2. Run Installation Command
This will publish the configuration file, compiled assets, and set up everything:
```bash
php artisan catchy:install
```

And you are done! Catchy's middleware is automatically registered to the `web` group, and the SPA routing scripts are automatically injected before the closing `</body>` tag on all standard HTML responses.

---

## 🎨 Styling Presets & Customization

---

## 🎨 Styling Presets & Customization

Catchy is completely styling-agnostic. It features built-in style presets for **Tailwind CSS**, **Bootstrap 5**, and **Vanilla/Custom CSS**, controllable via a single configuration option.

### 1. Choosing a Style Preset
You can set your preferred styling framework in the published `config/catchy.php`:

```php
// config/catchy.php
'preset' => 'tailwind', // Options: 'tailwind', 'bootstrap', 'vanilla', or 'custom'
```

- **tailwind**: Component templates compile with standard Tailwind classes.
- **bootstrap**: Component templates compile with Bootstrap 5 utility classes and structure.
- **vanilla**: Component templates use standard CSS class prefixes (e.g. `catchy-btn`, `catchy-card`). Ideal if you write your own stylesheets.
- **custom**: Disables all default styles, allowing you to supply custom class names for each element.

### 2. Register Tailwind Content Path
To prevent Tailwind from purging classes used inside Catchy components, add the vendor folder to the `content` array of your `tailwind.config.js`:
```javascript
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./vendor/hamzi/catchy/resources/views/**/*.blade.php", // <-- Add this line
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

### 3. Customizing Styles Globally
All component styles are fully decoupled. You can override visual styles (colors, sizing, padding, active states) by editing the `styles` array inside your published `config/catchy.php`. This allows Catchy components to integrate seamlessly with your project's custom design system:

```php
// config/catchy.php
'styles' => [
    'button' => [
        'base' => 'inline-flex items-center justify-center font-semibold rounded-lg...',
        'variants' => [
            'primary' => 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500...',
            // Change color classes to match your brand (e.g., bg-blue-600, bg-primary-600)
        ],
    ],
    // Customize styles for alert, badge, card, input, dropdown, modal, etc.
]
```

---

## 📦 Built-in Blade UI Components

Catchy includes 19 pre-styled, translatable, and RTL-safe UI components:

| Component Tag | Description & Primary Features | Key Attributes |
| :--- | :--- | :--- |
| `<x-catchy-button>` | Interactive button with spinner auto-loading on form submits. | `type`, `variant`, `size`, `loading` |
| `<x-catchy-input>` | Input field with labels, helper texts, and validation errors. | `name`, `label`, `type`, `placeholder`, `required`, `helper` |
| `<x-catchy-textarea>` | Textarea with auto-grow and validation errors. | `name`, `label`, `placeholder`, `rows`, `required`, `auto-grow` |
| `<x-catchy-select>` | Styled dropdown select with arrow helper. | `name`, `label`, `options`, `selected`, `multiple`, `placeholder` |
| `<x-catchy-upload>` | Drag & drop file/image uploader with previews. | `name`, `multiple`, `accept`, `label`, `helpText` |
| `<x-catchy-modal>` | Keyboard closeable, dynamic backdrop blurred modal. | `id`, `title`, `size`, `closeOnOutsideClick` |
| `<x-catchy-offcanvas>` | Slide-in drawer supporting 6 logical entry directions. | `id`, `title`, `direction`, `closeOnOutsideClick` |
| `<x-catchy-toast>` | Global notifier displaying flash and real-time session updates. | `position`, `duration` |
| `<x-catchy-alert>` | Dismissible inline information or warning banner. | `type`, `dismissible` |
| `<x-catchy-badge>` | Visual pill or label displaying metadata statuses. | `variant`, `size`, `rounded` |
| `<x-catchy-dropdown>` | Relative overlay dropdown with outside click closing. | `align`, `width` |
| `<x-catchy-progress>` | Progress bar tracking global page navigation or file uploads. | `for`, `color`, `height`, `showPercent` |
| `<x-catchy-lazy>` | Lazy loader rendering components on load or viewport intersection. | `src`, `trigger` |
| `<x-catchy-skeleton>` | Placeholder skeleton loaders (text lines, circles, cards). | `type`, `lines`, `animate` |
| `<x-catchy-spinner>` | Standard loading SVG animation helper. | `size`, `color` |
| `<x-catchy-error>` | Validation feedback field rendering backend validation errors. | `field` |
| `<x-catchy-fade>` | Subtle transition wrapper to fade in content. | `duration` |
| `<x-catchy-form>` | Form wrapper implementing SPA interception, callbacks, and CSRF. | `action`, `method`, `beforesend`, `success`, `error` |

---

## 🛠️ Advanced Dynamic APIs

### 1. Real-time Backend Syncing (`x-catchy-sync`)
Sync input values or whole forms with your backend asynchronously. Extremely useful for live-search or auto-saving:

```html
<!-- Live Search: fires key-up query (debounced) and morphs results container -->
<input type="text" name="q" x-catchy-sync.input.debounce.300ms.target.results-box="/search">

<div id="results-box">
    <!-- Results list will render here -->
</div>
```
**Modifiers**: `.input` (keystroke trigger), `.debounce.Xms` (delay), `.form` (serialize parent form), `.target.element-id` (response target container).

### 2. Declarative Event Triggers
You can chain multiple actions on form success or error triggers without writing JavaScript.
Use `data-catchy-on-success` or `data-catchy-on-error` with actions separated by semicolons:

- **Format**: `data-catchy-on-[success|error]="action:component:id"`
- **Available Actions**: `open:modal:id`, `close:modal:id`, `open:offcanvas:id`, `close:offcanvas:id`, `reload:lazy-component-id`, `toast:message`, `reset` (clear form).

```html
<!-- Automatically resets inputs, triggers a toast alert, and reloads comments list on success -->
<x-catchy-form action="/comments" method="POST" 
    data-catchy-on-success="reset;toast:Comment posted!;reload:comment-section">
    <textarea name="comment" required></textarea>
    <button type="submit">Submit Comment</button>
</x-catchy-form>

<x-catchy-lazy id="comment-section" src="/comments/list" />
```

### 3. Action Confirmations
Protect destructive actions from accidental clicks by displaying native or custom confirmations:

```html
<!-- Native prompt -->
<a href="/delete" data-catchy-confirm="Are you sure you want to delete this?">Delete</a>

<!-- Custom Styled Modal confirm -->
<x-catchy-form action="/purge" method="POST" data-catchy-confirm-modal="confirm-modal">
    <button type="submit">Purge Data</button>
</x-catchy-form>

<x-catchy-modal id="confirm-modal" title="Confirm Purge">
    <p>Are you absolutely sure?</p>
    <div class="mt-4 flex gap-3">
        <button type="button" @click="close()">Cancel</button>
        <button type="button" data-catchy-confirm-button class="bg-red-600 text-white">Yes, Purge</button>
    </div>
</x-catchy-modal>
```

### 4. NPM / Vite Bundler Mode (Alternative Integration)
If you prefer packaging Catchy inside your `resources/js/app.js` using Vite instead of `@catchyScripts`:

1. Install Alpine dependencies via NPM:
```bash
npm install alpinejs @alpinejs/morph
```
2. Bundle inside `resources/js/app.js` (importing the published Catchy asset):
```javascript
import Alpine from 'alpinejs';
import morph from '@alpinejs/morph';
import Catchy from '../../public/vendor/catchy/catchy.js';

Alpine.plugin(morph);
Alpine.plugin(Catchy);

window.Alpine = Alpine;
Alpine.start();
```

---

## 📄 License
The MIT License (MIT). Please see [License File](LICENSE) for more details.
