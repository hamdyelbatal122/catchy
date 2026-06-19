/**
 * Catchy — Configuration Module
 *
 * Resolves and normalizes configuration from window.CatchyConfig.
 */

/**
 * Create a resolved configuration object from window.CatchyConfig.
 *
 * @returns {Object}
 */
export function resolveConfig() {
    const c = window.CatchyConfig || {};
    return {
        containerId: c.containerId || 'catchy-app',
        ignoreAttribute: c.ignoreAttribute || 'data-catchy-ignore',
        prefetch: c.prefetch !== false,
        prefetchDelay: c.prefetchDelay || 75,
        cacheTTL: c.cacheTTL || 30000,
        swr: c.swr !== false,
        loadingBar: c.loadingBar !== false,
        loadingBarHeight: c.loadingBarHeight || '3px',
        loadingBarColor: c.loadingBarColor || 'linear-gradient(to right, #4f46e5, #06b6d4)',
    };
}
