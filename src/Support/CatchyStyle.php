<?php

declare(strict_types=1);

namespace Hamzi\Catchy\Support;

/**
 * Class CatchyStyle
 *
 * Resolves component CSS classes dynamically based on the configured preset
 * (tailwind, bootstrap, vanilla, custom) and optional config overrides.
 */
class CatchyStyle
{
    /**
     * Resolve the styling class(es) for a component and key.
     *
     * @param  string  $key  Component and key path (e.g. 'button.base')
     * @param  mixed  $default  Fallback value if not found
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // 1. Check if user configured a custom override in config('catchy.styles.*')
        $override = config("catchy.styles.{$key}");
        if ($override !== null) {
            return $override;
        }

        // 2. Resolve preset
        $preset = config('catchy.preset', 'tailwind');

        if ($preset === 'custom') {
            return $default;
        }

        return static::resolvePresetClass($preset, $key, $default);
    }

    /**
     * Cached preset dictionaries to avoid rebuilding on every call.
     *
     * @var array<string, array<string, string>>|null
     */
    protected static ?array $cachedPresets = null;

    /**
     * Resolve class mapping from preset dictionaries.
     */
    protected static function resolvePresetClass(string $preset, string $key, mixed $default = null): mixed
    {
        // Build and cache preset dictionaries on first access
        if (static::$cachedPresets === null) {
            static::$cachedPresets = static::buildPresets();
        }

        return static::$cachedPresets[$preset][$key] ?? $default;
    }

    /**
     * Build the preset dictionaries for Tailwind, Bootstrap 5, and Vanilla CSS.
     *
     * @return array<string, array<string, string>>
     */
    protected static function buildPresets(): array
    {
        return [
            'tailwind' => [
                'alert.base' => 'flex p-4 rounded-xl border',
                'alert.dismiss_btn' => 'inline-flex rounded-lg p-1.5 hover:bg-black/5 dark:hover:bg-white/5 focus:outline-none transition-colors',
                'alert.variants.success' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30',
                'alert.variants.danger' => 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30',
                'alert.variants.warning' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30',
                'alert.variants.info' => 'bg-sky-50 text-sky-800 border-sky-200 dark:bg-sky-950/20 dark:text-sky-400 dark:border-sky-900/30',
                'alert.icons.success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'alert.icons.danger' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                'alert.icons.warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                'alert.icons.info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',

                'badge.base' => 'inline-flex items-center font-medium border transition-colors',
                'badge.sizes.sm' => 'px-1.5 py-0.5 text-xs',
                'badge.sizes.md' => 'px-2.5 py-0.5 text-sm',
                'badge.variants.primary' => 'bg-indigo-50 text-indigo-700 border-indigo-200/50 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/30',
                'badge.variants.secondary' => 'bg-slate-50 text-slate-700 border-slate-200/50 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/30',
                'badge.variants.success' => 'bg-emerald-50 text-emerald-700 border-emerald-200/50 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30',
                'badge.variants.danger' => 'bg-rose-50 text-rose-700 border-rose-200/50 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30',
                'badge.variants.warning' => 'bg-amber-50 text-amber-700 border-amber-200/50 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30',
                'badge.variants.info' => 'bg-sky-50 text-sky-700 border-sky-200/50 dark:bg-sky-950/20 dark:text-sky-400 dark:border-sky-900/30',

                'button.base' => 'inline-flex items-center justify-center font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none active:scale-[0.98]',
                'button.sizes.sm' => 'px-3 py-1.5 text-xs',
                'button.sizes.md' => 'px-4 py-2 text-sm',
                'button.sizes.lg' => 'px-5 py-2.5 text-base',
                'button.variants.primary' => 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500 border border-transparent shadow-sm',
                'button.variants.secondary' => 'bg-slate-600 hover:bg-slate-700 text-white focus:ring-slate-500 border border-transparent shadow-sm',
                'button.variants.success' => 'bg-emerald-600 hover:bg-emerald-700 text-white focus:ring-emerald-500 border border-transparent shadow-sm',
                'button.variants.danger' => 'bg-rose-600 hover:bg-rose-700 text-white focus:ring-rose-500 border border-transparent shadow-sm',
                'button.variants.outline' => 'border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50 text-slate-700 dark:text-slate-300 focus:ring-indigo-500 shadow-sm',
                'button.variants.ghost' => 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/50 focus:ring-slate-500',

                'card.base' => 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 rounded-xl shadow-sm overflow-hidden transition-all duration-300',
                'card.hoverable' => 'hover:shadow-md hover:scale-[1.005] hover:border-indigo-500/30',
                'card.header' => 'border-b border-slate-100 dark:border-slate-800/80 px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50',
                'card.body' => 'px-6 py-5',
                'card.footer' => 'border-t border-slate-100 dark:border-slate-800/80 px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50',

                'dropdown.wrapper' => 'relative inline-block text-start',
                'dropdown.trigger' => 'cursor-pointer',
                'dropdown.menu' => 'absolute z-50 mt-2 rounded-xl shadow-lg ring-1 ring-black/5 dark:ring-white/10 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 py-1 focus:outline-none',
                'dropdown.inner' => 'rounded-xl py-1 bg-white dark:bg-slate-900',

                'error.base' => 'text-sm text-red-600 dark:text-red-400 mt-1 font-medium',

                'input.wrapper' => 'space-y-1',
                'input.label' => 'block text-sm font-medium text-slate-700 dark:text-slate-300',
                'input.required' => 'text-rose-500',
                'input.input_wrapper' => 'relative rounded-lg shadow-sm',
                'input.input' => 'block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-colors disabled:opacity-50',
                'input.helper' => 'text-xs text-slate-500 dark:text-slate-400',
                'input.error' => 'text-rose-500 text-xs mt-1',

                'textarea.wrapper' => 'space-y-1',
                'textarea.label' => 'block text-sm font-medium text-slate-700 dark:text-slate-300',
                'textarea.required' => 'text-rose-500',
                'textarea.input_wrapper' => 'relative rounded-lg shadow-sm',
                'textarea.textarea' => 'block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-colors disabled:opacity-50 resize-y',
                'textarea.helper' => 'text-xs text-slate-500 dark:text-slate-400',
                'textarea.error' => 'text-rose-500 text-xs mt-1',

                'select.wrapper' => 'space-y-1',
                'select.label' => 'block text-sm font-medium text-slate-700 dark:text-slate-300',
                'select.required' => 'text-rose-500',
                'select.input_wrapper' => 'relative rounded-lg shadow-sm',
                'select.select' => 'block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-colors disabled:opacity-50',
                'select.arrow_wrapper' => 'pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500 dark:text-slate-400',
                'select.helper' => 'text-xs text-slate-500 dark:text-slate-400',
                'select.error' => 'text-rose-500 text-xs mt-1',

                'lazy.error' => 'text-sm text-rose-600 dark:text-rose-400 p-4 border border-rose-200 dark:border-rose-900/40 rounded-lg bg-rose-50 dark:bg-rose-950/20',

                'modal.base' => 'fixed inset-0 z-50 overflow-y-auto',
                'modal.backdrop' => 'fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity',
                'modal.wrapper' => 'flex min-h-screen items-center justify-center p-4 text-center sm:p-0',
                'modal.content' => 'relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-start shadow-2xl transition-all w-full flex flex-col max-h-[90vh]',
                'modal.sizes.sm' => 'sm:max-w-sm',
                'modal.sizes.md' => 'sm:max-w-md',
                'modal.sizes.lg' => 'sm:max-w-lg',
                'modal.sizes.xl' => 'sm:max-w-xl',
                'modal.sizes.2xl' => 'sm:max-w-2xl',
                'modal.sizes.3xl' => 'sm:max-w-3xl',
                'modal.sizes.4xl' => 'sm:max-w-4xl',
                'modal.sizes.5xl' => 'sm:max-w-5xl',
                'modal.sizes.full' => 'sm:max-w-full m-4',
                'modal.header' => 'flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 px-6 py-4',
                'modal.title' => 'text-lg font-semibold text-slate-900 dark:text-slate-100',
                'modal.close_btn' => 'rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                'modal.body' => 'flex-1 overflow-y-auto px-6 py-4 text-slate-600 dark:text-slate-300',
                'modal.footer' => 'border-t border-slate-100 dark:border-slate-800/80 px-6 py-4 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3',

                'offcanvas.base' => 'fixed inset-0 z-50 overflow-hidden',
                'offcanvas.backdrop' => 'fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity',
                'offcanvas.header' => 'flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 px-6 py-4',
                'offcanvas.title' => 'text-lg font-semibold text-slate-900 dark:text-slate-100',
                'offcanvas.close_btn' => 'rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                'offcanvas.body' => 'flex-1 overflow-y-auto px-6 py-4 text-slate-600 dark:text-slate-300',
                'offcanvas.footer' => 'border-t border-slate-100 dark:border-slate-800/80 px-6 py-4 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3',

                'progress.wrapper' => 'w-full space-y-2',
                'progress.percent_wrapper' => 'flex justify-between items-center text-xs font-semibold text-gray-700 dark:text-gray-300',
                'progress.bar_track' => 'w-full bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner border border-gray-300/30 dark:border-gray-750/30',
                'progress.bar_base' => 'rounded-full transition-all duration-300 ease-out shadow-sm',
                'progress.colors.primary' => 'bg-indigo-600 dark:bg-indigo-500',
                'progress.colors.accent' => 'bg-cyan-500 dark:bg-cyan-400',
                'progress.colors.success' => 'bg-emerald-500 dark:bg-emerald-400',
                'progress.colors.warning' => 'bg-amber-500 dark:bg-amber-400',
                'progress.colors.danger' => 'bg-rose-500 dark:bg-rose-400',
                'progress.colors.gradient' => 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500',

                'skeleton.wrapper' => 'space-y-3',
                'skeleton.circle' => 'rounded-full bg-gray-200 dark:bg-slate-700 h-12 w-12',
                'skeleton.title' => 'h-6 bg-gray-200 dark:bg-slate-700 rounded-md w-2/3',
                'skeleton.card' => 'rounded-lg bg-gray-200 dark:bg-slate-700 h-32 w-full',
                'skeleton.line' => 'h-4 bg-gray-200 dark:bg-slate-700 rounded-md',

                'spinner.base' => 'animate-spin',
                'spinner.sizes.xs' => 'h-3.5 w-3.5',
                'spinner.sizes.sm' => 'h-4 w-4',
                'spinner.sizes.md' => 'h-6 w-6',
                'spinner.sizes.lg' => 'h-8 w-8',
                'spinner.sizes.xl' => 'h-12 w-12',
                'spinner.colors.primary' => 'text-indigo-600 dark:text-indigo-400',
                'spinner.colors.accent' => 'text-cyan-500 dark:text-cyan-400',
                'spinner.colors.white' => 'text-white',
                'spinner.colors.gray' => 'text-gray-400 dark:text-gray-500',

                'toast.wrapper' => 'fixed z-[99998] flex flex-col gap-3 min-w-80 max-w-md',
                'toast.types.success' => 'bg-emerald-50/95 dark:bg-emerald-950/90 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200',
                'toast.types.error' => 'bg-rose-50/95 dark:bg-rose-950/90 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200',
                'toast.types.warning' => 'bg-amber-50/95 dark:bg-amber-950/90 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200',
                'toast.types.info' => 'bg-sky-50/95 dark:bg-sky-950/90 border-sky-200 dark:border-sky-800 text-sky-800 dark:text-sky-200',
                'toast.positions.top-right' => 'top-5 end-5',
                'toast.positions.top-left' => 'top-5 start-5',
                'toast.positions.bottom-right' => 'bottom-5 end-5',
                'toast.positions.bottom-left' => 'bottom-5 start-5',
                'toast.positions.top-center' => 'top-5 start-1/2 -translate-x-1/2',
                'toast.positions.bottom-center' => 'bottom-5 start-1/2 -translate-x-1/2',
                'toast.item_base' => 'flex items-start gap-3 px-4 py-3 rounded-xl shadow-xl backdrop-blur-lg border transition-all duration-300',
                'toast.dismiss_btn' => 'shrink-0 rounded-lg p-1 opacity-60 hover:opacity-100 transition-opacity focus:outline-none',

                'upload.wrapper' => 'w-full',
                'upload.drop_zone' => 'relative flex flex-col items-center justify-center border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-300 ease-in-out group outline-none focus-within:ring-2 focus-within:ring-indigo-500',
                'upload.drop_zone_active' => 'border-indigo-500 bg-indigo-50/10 dark:bg-indigo-950/20 shadow-md scale-[1.01]',
                'upload.drop_zone_inactive' => 'border-gray-300 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-500 bg-gray-50/50 dark:bg-gray-900/50',
                'upload.icon_wrapper' => 'mb-4 rounded-full bg-indigo-100/80 dark:bg-indigo-950/50 p-4 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-300',
                'upload.title' => 'text-sm font-semibold text-gray-700 dark:text-gray-200',
                'upload.help' => 'mt-1 text-xs text-gray-500 dark:text-gray-400',
                'upload.preview_list' => 'mt-4 space-y-2',
                'upload.preview_item' => 'flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 shadow-sm transition-all duration-200 hover:shadow',
                'upload.thumbnail_img' => 'h-10 w-10 object-cover rounded-md border border-gray-100 dark:border-gray-850 flex-shrink-0',
                'upload.thumbnail_icon_wrapper' => 'h-10 w-10 flex items-center justify-center rounded-md bg-gray-100 dark:bg-gray-900 border border-gray-100 dark:border-gray-850 flex-shrink-0 text-gray-400 dark:text-gray-500',
                'upload.file_info' => 'min-w-0 flex-1 px-2',
                'upload.file_name' => 'text-sm font-medium text-gray-700 dark:text-gray-300 truncate',
                'upload.file_size' => 'text-xs text-gray-500 dark:text-gray-400',
                'upload.remove_btn' => 'p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-900 text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 transition-colors',
                'upload.error' => 'mt-2 text-sm text-red-600 dark:text-red-400 font-semibold',
            ],
            'bootstrap' => [
                'alert.base' => 'alert d-flex align-items-center alert-dismissible fade show p-3 border rounded',
                'alert.dismiss_btn' => 'btn-close',
                'alert.variants.success' => 'alert-success',
                'alert.variants.danger' => 'alert-danger',
                'alert.variants.warning' => 'alert-warning',
                'alert.variants.info' => 'alert-info',
                'alert.icons.success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'alert.icons.danger' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                'alert.icons.warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                'alert.icons.info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',

                'badge.base' => 'badge',
                'badge.sizes.sm' => 'badge-sm',
                'badge.sizes.md' => '',
                'badge.variants.primary' => 'bg-primary text-white',
                'badge.variants.secondary' => 'bg-secondary text-white',
                'badge.variants.success' => 'bg-success text-white',
                'badge.variants.danger' => 'bg-danger text-white',
                'badge.variants.warning' => 'bg-warning text-dark',
                'badge.variants.info' => 'bg-info text-dark',

                'button.base' => 'btn',
                'button.sizes.sm' => 'btn-sm',
                'button.sizes.md' => '',
                'button.sizes.lg' => 'btn-lg',
                'button.variants.primary' => 'btn-primary',
                'button.variants.secondary' => 'btn-secondary',
                'button.variants.success' => 'btn-success',
                'button.variants.danger' => 'btn-danger',
                'button.variants.outline' => 'btn-outline-primary',
                'button.variants.ghost' => 'btn-link',

                'card.base' => 'card',
                'card.hoverable' => 'card-hover',
                'card.header' => 'card-header',
                'card.body' => 'card-body',
                'card.footer' => 'card-footer',

                'dropdown.wrapper' => 'dropdown',
                'dropdown.trigger' => 'dropdown-toggle',
                'dropdown.menu' => 'dropdown-menu',
                'dropdown.inner' => 'dropdown-inner',

                'error.base' => 'invalid-feedback d-block',

                'input.wrapper' => 'mb-3',
                'input.label' => 'form-label',
                'input.required' => 'text-danger',
                'input.input_wrapper' => 'input-group',
                'input.input' => 'form-control',
                'input.helper' => 'form-text',
                'input.error' => 'invalid-feedback d-block',

                'textarea.wrapper' => 'mb-3',
                'textarea.label' => 'form-label',
                'textarea.required' => 'text-danger',
                'textarea.input_wrapper' => '',
                'textarea.textarea' => 'form-control',
                'textarea.helper' => 'form-text',
                'textarea.error' => 'invalid-feedback d-block',

                'select.wrapper' => 'mb-3',
                'select.label' => 'form-label',
                'select.required' => 'text-danger',
                'select.input_wrapper' => '',
                'select.select' => 'form-select',
                'select.arrow_wrapper' => 'd-none',
                'select.helper' => 'form-text',
                'select.error' => 'invalid-feedback d-block',

                'lazy.error' => 'alert alert-danger p-3 border rounded',

                'modal.base' => 'modal fade show d-block',
                'modal.backdrop' => 'modal-backdrop fade show',
                'modal.wrapper' => 'modal-dialog modal-dialog-centered',
                'modal.content' => 'modal-content',
                'modal.sizes.sm' => 'modal-sm',
                'modal.sizes.md' => '',
                'modal.sizes.lg' => 'modal-lg',
                'modal.sizes.xl' => 'modal-xl',
                'modal.sizes.full' => 'modal-fullscreen',
                'modal.header' => 'modal-header',
                'modal.title' => 'modal-title',
                'modal.close_btn' => 'btn-close',
                'modal.body' => 'modal-body',
                'modal.footer' => 'modal-footer',

                'offcanvas.base' => 'offcanvas show d-block',
                'offcanvas.backdrop' => 'offcanvas-backdrop fade show',
                'offcanvas.header' => 'offcanvas-header',
                'offcanvas.title' => 'offcanvas-title',
                'offcanvas.close_btn' => 'btn-close',
                'offcanvas.body' => 'offcanvas-body',
                'offcanvas.footer' => 'offcanvas-footer border-top p-3',

                'progress.wrapper' => 'progress-wrapper w-full mb-3',
                'progress.percent_wrapper' => 'd-flex justify-content-between align-items-center mb-1 small fw-bold text-secondary',
                'progress.bar_track' => 'progress',
                'progress.bar_base' => 'progress-bar progress-bar-striped progress-bar-animated',
                'progress.colors.primary' => 'bg-primary',
                'progress.colors.accent' => 'bg-info',
                'progress.colors.success' => 'bg-success',
                'progress.colors.warning' => 'bg-warning',
                'progress.colors.danger' => 'bg-danger',
                'progress.colors.gradient' => 'bg-primary bg-gradient',

                'skeleton.wrapper' => 'placeholder-glow space-y-2',
                'skeleton.circle' => 'placeholder rounded-circle bg-secondary',
                'skeleton.title' => 'placeholder col-6 bg-secondary',
                'skeleton.card' => 'placeholder col-12 bg-secondary',
                'skeleton.line' => 'placeholder col-12 bg-secondary',

                'spinner.base' => 'spinner-border',
                'spinner.sizes.xs' => 'spinner-border-sm',
                'spinner.sizes.sm' => 'spinner-border-sm',
                'spinner.sizes.md' => '',
                'spinner.sizes.lg' => 'spinner-border-lg',
                'spinner.sizes.xl' => 'spinner-border-lg',
                'spinner.colors.primary' => 'text-primary',
                'spinner.colors.accent' => 'text-info',
                'spinner.colors.white' => 'text-light',
                'spinner.colors.gray' => 'text-secondary',

                'toast.wrapper' => 'toast-container position-fixed p-3',
                'toast.types.success' => 'bg-success text-white',
                'toast.types.error' => 'bg-danger text-white',
                'toast.types.warning' => 'bg-warning text-dark',
                'toast.types.info' => 'bg-info text-dark',
                'toast.item_base' => 'toast show d-flex align-items-center p-2',
                'toast.dismiss_btn' => 'btn-close ms-auto me-2',

                'upload.wrapper' => 'w-full',
                'upload.drop_zone' => 'border border-2 border-dashed rounded p-5 text-center bg-light cursor-pointer',
                'upload.drop_zone_active' => 'bg-secondary-subtle border-primary',
                'upload.drop_zone_inactive' => 'bg-light',
                'upload.icon_wrapper' => 'mb-3 text-primary',
                'upload.title' => 'fw-bold',
                'upload.help' => 'text-muted small',
                'upload.preview_list' => 'list-group mt-3',
                'upload.preview_item' => 'list-group-item d-flex justify-content-between align-items-center',
                'upload.thumbnail_img' => 'rounded border',
                'upload.thumbnail_icon_wrapper' => 'rounded border bg-light text-muted d-flex align-items-center justify-content-center',
                'upload.file_info' => 'ms-2 flex-grow-1',
                'upload.file_name' => 'mb-0 fw-bold small',
                'upload.file_size' => 'text-muted small mb-0',
                'upload.remove_btn' => 'btn btn-sm btn-outline-danger border-0 rounded-circle',
                'upload.error' => 'text-danger small mt-2 fw-bold',
            ],
            'vanilla' => [
                'alert.base' => 'catchy-alert',
                'alert.dismiss_btn' => 'catchy-alert-close',
                'alert.variants.success' => 'catchy-alert-success',
                'alert.variants.danger' => 'catchy-alert-danger',
                'alert.variants.warning' => 'catchy-alert-warning',
                'alert.variants.info' => 'catchy-alert-info',
                'alert.icons.success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'alert.icons.danger' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                'alert.icons.warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                'alert.icons.info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',

                'badge.base' => 'catchy-badge',
                'badge.sizes.sm' => 'catchy-badge-sm',
                'badge.sizes.md' => 'catchy-badge-md',
                'badge.variants.primary' => 'catchy-badge-primary',
                'badge.variants.secondary' => 'catchy-badge-secondary',
                'badge.variants.success' => 'catchy-badge-success',
                'badge.variants.danger' => 'catchy-badge-danger',
                'badge.variants.warning' => 'catchy-badge-warning',
                'badge.variants.info' => 'catchy-badge-info',

                'button.base' => 'catchy-btn',
                'button.sizes.sm' => 'catchy-btn-sm',
                'button.sizes.md' => 'catchy-btn-md',
                'button.sizes.lg' => 'catchy-btn-lg',
                'button.variants.primary' => 'catchy-btn-primary',
                'button.variants.secondary' => 'catchy-btn-secondary',
                'button.variants.success' => 'catchy-btn-success',
                'button.variants.danger' => 'catchy-btn-danger',
                'button.variants.outline' => 'catchy-btn-outline',
                'button.variants.ghost' => 'catchy-btn-ghost',

                'card.base' => 'catchy-card',
                'card.hoverable' => 'catchy-card-hoverable',
                'card.header' => 'catchy-card-header',
                'card.body' => 'catchy-card-body',
                'card.footer' => 'catchy-card-footer',

                'dropdown.wrapper' => 'catchy-dropdown',
                'dropdown.trigger' => 'catchy-dropdown-trigger',
                'dropdown.menu' => 'catchy-dropdown-menu',
                'dropdown.inner' => 'catchy-dropdown-inner',

                'error.base' => 'catchy-error',

                'input.wrapper' => 'catchy-form-group',
                'input.label' => 'catchy-form-label',
                'input.required' => 'catchy-form-required',
                'input.input_wrapper' => 'catchy-input-wrapper',
                'input.input' => 'catchy-input',
                'input.helper' => 'catchy-form-helper',
                'input.error' => 'catchy-form-error',

                'textarea.wrapper' => 'catchy-form-group',
                'textarea.label' => 'catchy-form-label',
                'textarea.required' => 'catchy-form-required',
                'textarea.input_wrapper' => 'catchy-textarea-wrapper',
                'textarea.textarea' => 'catchy-textarea',
                'textarea.helper' => 'catchy-form-helper',
                'textarea.error' => 'catchy-form-error',

                'select.wrapper' => 'catchy-form-group',
                'select.label' => 'catchy-form-label',
                'select.required' => 'catchy-form-required',
                'select.input_wrapper' => 'catchy-select-wrapper',
                'select.select' => 'catchy-select',
                'select.arrow_wrapper' => 'catchy-select-arrow',
                'select.helper' => 'catchy-form-helper',
                'select.error' => 'catchy-form-error',

                'lazy.error' => 'catchy-lazy-error',

                'modal.base' => 'catchy-modal-container',
                'modal.backdrop' => 'catchy-modal-backdrop',
                'modal.wrapper' => 'catchy-modal-wrapper',
                'modal.content' => 'catchy-modal-content',
                'modal.sizes.sm' => 'catchy-modal-sm',
                'modal.sizes.md' => 'catchy-modal-md',
                'modal.sizes.lg' => 'catchy-modal-lg',
                'modal.sizes.xl' => 'catchy-modal-xl',
                'modal.sizes.full' => 'catchy-modal-full',
                'modal.header' => 'catchy-modal-header',
                'modal.title' => 'catchy-modal-title',
                'modal.close_btn' => 'catchy-modal-close',
                'modal.body' => 'catchy-modal-body',
                'modal.footer' => 'catchy-modal-footer',

                'offcanvas.base' => 'catchy-offcanvas-container',
                'offcanvas.backdrop' => 'catchy-offcanvas-backdrop',
                'offcanvas.header' => 'catchy-offcanvas-header',
                'offcanvas.title' => 'catchy-offcanvas-title',
                'offcanvas.close_btn' => 'catchy-offcanvas-close',
                'offcanvas.body' => 'catchy-offcanvas-body',
                'offcanvas.footer' => 'catchy-offcanvas-footer',

                'progress.wrapper' => 'catchy-progress-wrapper',
                'progress.percent_wrapper' => 'catchy-progress-percent-wrapper',
                'progress.bar_track' => 'catchy-progress-track',
                'progress.bar_base' => 'catchy-progress-bar',
                'progress.colors.primary' => 'catchy-progress-primary',
                'progress.colors.accent' => 'catchy-progress-accent',
                'progress.colors.success' => 'catchy-progress-success',
                'progress.colors.warning' => 'catchy-progress-warning',
                'progress.colors.danger' => 'catchy-progress-danger',
                'progress.colors.gradient' => 'catchy-progress-gradient',

                'skeleton.wrapper' => 'catchy-skeleton-wrapper',
                'skeleton.circle' => 'catchy-skeleton-circle',
                'skeleton.title' => 'catchy-skeleton-title',
                'skeleton.card' => 'catchy-skeleton-card',
                'skeleton.line' => 'catchy-skeleton-line',

                'spinner.base' => 'catchy-spinner',
                'spinner.sizes.xs' => 'catchy-spinner-xs',
                'spinner.sizes.sm' => 'catchy-spinner-sm',
                'spinner.sizes.md' => 'catchy-spinner-md',
                'spinner.sizes.lg' => 'catchy-spinner-lg',
                'spinner.sizes.xl' => 'catchy-spinner-xl',
                'spinner.colors.primary' => 'catchy-spinner-primary',
                'spinner.colors.accent' => 'catchy-spinner-accent',
                'spinner.colors.white' => 'catchy-spinner-white',
                'spinner.colors.gray' => 'catchy-spinner-gray',

                'toast.wrapper' => 'catchy-toast-container',
                'toast.types.success' => 'catchy-toast-success',
                'toast.types.error' => 'catchy-toast-error',
                'toast.types.warning' => 'catchy-toast-warning',
                'toast.types.info' => 'catchy-toast-info',
                'toast.item_base' => 'catchy-toast',
                'toast.dismiss_btn' => 'catchy-toast-close',

                'upload.wrapper' => 'catchy-upload-wrapper',
                'upload.drop_zone' => 'catchy-upload-dropzone',
                'upload.drop_zone_active' => 'catchy-upload-dropzone-active',
                'upload.drop_zone_inactive' => 'catchy-upload-dropzone-inactive',
                'upload.icon_wrapper' => 'catchy-upload-icon-wrapper',
                'upload.title' => 'catchy-upload-title',
                'upload.help' => 'catchy-upload-help',
                'upload.preview_list' => 'catchy-upload-previews',
                'upload.preview_item' => 'catchy-upload-preview-item',
                'upload.thumbnail_img' => 'catchy-upload-thumb',
                'upload.thumbnail_icon_wrapper' => 'catchy-upload-thumb-icon',
                'upload.file_info' => 'catchy-upload-file-info',
                'upload.file_name' => 'catchy-upload-file-name',
                'upload.file_size' => 'catchy-upload-file-size',
                'upload.remove_btn' => 'catchy-upload-remove-btn',
                'upload.error' => 'catchy-upload-error',
            ],
        ];
    }
}
