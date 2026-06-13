# Changelog

All notable changes to `Laravel Catchy` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - 2026-06-13

### Changed
- Moved `alpinejs` and `@alpinejs/morph` from `peerDependencies` to `dependencies` in `package.json` to ensure they are automatically installed as transient dependencies by the host application.

## [1.0.2] - 2026-06-13

### Changed
- Switched README badges from Packagist to GitHub-based and static badges to prevent "not found" status messages.

## [1.0.1] - 2026-06-13

### Fixed
- Fixed `.gitattributes` export-ignore rule that was excluding the `config/catchy.php` configuration file from Composer archive downloads.

## [1.0.0] - 2026-06-13

### Added
- Initial release of Laravel Catchy.
- SPA-like page transitions and morphing using Alpine.js and `@alpinejs/morph`.
- Form submission interceptor (`@catchyForm` directive).
- Viewport loader loading bar.
- Dynamic layout extraction middleware (`CatchySPAMiddleware`).
- Session flash message handling with `<x-catchy-toast>`.
- Customizable loading indicator, modal component, and transitions.
