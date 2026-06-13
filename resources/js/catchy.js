/**
 * Hamzi/Catchy - Alpine.js SPA Plugin
 *
 * HTML-over-the-wire navigation utilizing @alpinejs/morph,
 * built-in viewport loaders, intelligent prefetching, and asset version protection.
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.CatchyPlugin = factory();
        // Auto-register in browser globals
        if (root.Alpine) {
            root.Alpine.plugin(root.CatchyPlugin);
        } else {
            document.addEventListener('alpine:init', () => {
                root.Alpine.plugin(root.CatchyPlugin);
            });
        }
    }
}(typeof self !== 'undefined' ? self : this, function () {
    /**
     * The Catchy Alpine.js Plugin definition.
     *
     * @param {Object} Alpine
     */
    return function CatchyPlugin(Alpine) {

        // Defer morph plugin warning check to prevent race conditions during CDN/bundle loads
        setTimeout(() => {
            if (!Alpine.morph) {
                console.error(
                    'Catchy: The Alpine.js Morph plugin is required but not loaded.\n' +
                    'Please ensure `@alpinejs/morph` is imported and registered:\n' +
                    'https://alpinejs.dev/plugins/morph'
                );
            }
        }, 50);

        // Configuration settings with defaults
        const config = {
            containerId: window.CatchyConfig?.containerId || 'catchy-app',
            ignoreAttribute: window.CatchyConfig?.ignoreAttribute || 'data-catchy-ignore',
            prefetch: window.CatchyConfig?.prefetch !== false, // Active by default
            prefetchDelay: window.CatchyConfig?.prefetchDelay || 75, // Hover debounce milliseconds
            cacheTTL: window.CatchyConfig?.cacheTTL || 30000, // Cache TTL (30 seconds)
            loadingBar: window.CatchyConfig?.loadingBar !== false, // Built-in progress loader active by default
            loadingBarHeight: window.CatchyConfig?.loadingBarHeight || '3px',
            loadingBarColor: window.CatchyConfig?.loadingBarColor || 'linear-gradient(to right, #4f46e5, #06b6d4)',
        };

        // Internal State tracking
        const cache = new Map();
        const activeRequests = new Map();
        let currentVersion = '';
        let hoverTimeout = null;

        // Built-in top loading bar
        let loaderElement = null;
        let loaderTimer = null;
        let progressInterval = null;

        if (config.loadingBar) {
            const style = document.createElement('style');
            style.textContent = `
                #catchy-loader {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 0%;
                    height: ${config.loadingBarHeight};
                    background: ${config.loadingBarColor};
                    z-index: 99999;
                    transition: width 0.2s cubic-bezier(0.1, 0.8, 0.29, 1), opacity 0.4s ease;
                    opacity: 0;
                    pointer-events: none;
                }
            `;
            document.head.appendChild(style);

            loaderElement = document.createElement('div');
            loaderElement.id = 'catchy-loader';
            document.body.appendChild(loaderElement);
        }

        /**
         * Triggers the CSS loader animation if the request takes more than 120ms.
         */
        function startLoading() {
            if (!loaderElement) return;
            clearTimeout(loaderTimer);
            clearInterval(progressInterval);

            // Prevent flickering on fast or cached page loads
            loaderTimer = setTimeout(() => {
                loaderElement.style.width = '0%';
                loaderElement.style.opacity = '1';
                
                let width = 0;
                progressInterval = setInterval(() => {
                    if (width < 88) {
                        width += (90 - width) * 0.08; // asymptotic transition towards 90%
                        loaderElement.style.width = `${width}%`;
                    }
                }, 150);
            }, 120);
        }

        /**
         * Fills progress loader to 100% and fades out.
         */
        function stopLoading() {
            if (!loaderElement) return;
            clearTimeout(loaderTimer);
            clearInterval(progressInterval);

            loaderElement.style.width = '100%';
            
            setTimeout(() => {
                loaderElement.style.opacity = '0';
                setTimeout(() => {
                    loaderElement.style.width = '0%';
                }, 400);
            }, 100);
        }

        /**
         * Instantly resets the loader status.
         */
        function resetLoading() {
            if (!loaderElement) return;
            clearTimeout(loaderTimer);
            clearInterval(progressInterval);
            loaderElement.style.opacity = '0';
            loaderElement.style.width = '0%';
        }

        /**
         * Inspects client cache map for validated response cache.
         *
         * @param {string} url
         * @returns {Object|null}
         */
        function getCachedResponse(url) {
            const entry = cache.get(url);
            if (!entry) return null;

            if (Date.now() - entry.timestamp > config.cacheTTL) {
                cache.delete(url);
                return null;
            }

            return entry;
        }

        /**
         * Checks if a link should be ignored by the Catchy navigation router.
         *
         * @param {HTMLAnchorElement} link
         * @param {MouseEvent} event
         * @returns {boolean}
         */
        function shouldIgnoreLink(link, event) {
            const href = link.getAttribute('href');
            if (!href) return true;

            if (href.startsWith('#') || href.startsWith('javascript:')) return true;
            if (link.hasAttribute(config.ignoreAttribute)) return true;
            if (link.target && link.target.toLowerCase() !== '_self') return true;
            if (link.hasAttribute('download')) return true;
            if (event && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) return true;

            try {
                const url = new URL(link.href, window.location.href);
                if (url.origin !== window.location.origin) return true;

                if (
                    url.pathname === window.location.pathname &&
                    url.search === window.location.search &&
                    url.hash !== ''
                ) {
                    return true;
                }
            } catch (e) {
                return true;
            }

            return false;
        }

        /**
         * Checks if a form submission should be ignored by Catchy.
         *
         * @param {HTMLFormElement} form
         * @returns {boolean}
         */
        function shouldIgnoreForm(form) {
            if (form.hasAttribute(config.ignoreAttribute)) return true;
            if (form.getAttribute('method')?.toLowerCase() === 'dialog') return true;

            const action = form.getAttribute('action') || window.location.href;
            try {
                const url = new URL(action, window.location.href);
                if (url.origin !== window.location.origin) return true;
            } catch (e) {
                return true;
            }

            if (form.target && form.target.toLowerCase() !== '_self') return true;

            return false;
        }

        /**
         * Performs background prefetch fetches for same-origin links.
         *
         * @param {string} url
         */
        async function prefetch(url) {
            if (getCachedResponse(url) || activeRequests.has(url)) return;

            const promise = (async () => {
                try {
                    const headers = {
                        'X-Catchy-SPA': 'true',
                    };
                    if (currentVersion) {
                        headers['X-Catchy-Version'] = currentVersion;
                    }

                    const response = await fetch(url, { headers });
                    
                    // Handle 409 conflict due to version change: trigger hard reload immediately
                    if (response.status === 409) {
                        window.location.href = url;
                        return null;
                    }

                    if (!response.ok) return null;

                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('text/html')) return null;

                    const html = await response.text();
                    const version = response.headers.get('X-Catchy-Version') || '';
                    if (version) {
                        currentVersion = version;
                    }

                    const cacheEntry = {
                        html,
                        version,
                        finalUrl: response.url || url,
                        timestamp: Date.now()
                    };

                    cache.set(url, cacheEntry);
                    return cacheEntry;
                } catch (e) {
                    return null;
                } finally {
                    activeRequests.delete(url);
                }
            })();

            activeRequests.set(url, promise);
        }

        /**
         * Helper to resolve and execute data-catchy-* callback attributes.
         * Supports either a window-scoped function name or direct inline JS.
         *
         * @param {HTMLElement} element
         * @param {string} attrName
         * @param {Object} context
         * @returns {*}
         */
        function executeCallback(element, attrName, context) {
            if (!element || typeof element.getAttribute !== 'function') return;
            const callback = element.getAttribute(attrName);
            if (!callback) return;

            try {
                if (typeof window[callback] === 'function') {
                    return window[callback](context);
                }
                const fn = new Function('event', `with(window) { ${callback} }`);
                return fn(context);
            } catch (e) {
                console.error(`Catchy: Error in ${attrName} callback execution:`, e);
            }
        }

        /**
         * Fetch a page and update the DOM container.
         *
         * @param {string} url
         * @param {Object} options
         * @param {boolean} updateHistory
         */
        async function visit(url, options = {}, updateHistory = true) {
            // Save current scroll coordinates in history state before navigating away
            try {
                window.history.replaceState({
                    ...window.history.state,
                    scrollX: window.scrollX,
                    scrollY: window.scrollY
                }, '');
            } catch (e) {}

            const trigger = options.trigger || document;

            // Execute beforesend callback hook if defined
            if (executeCallback(trigger, 'data-catchy-beforesend', { url, options, trigger }) === false) {
                return;
            }

            const startEvent = new CustomEvent('catchy:start', {
                bubbles: true,
                cancelable: true,
                detail: { url, options, trigger }
            });

            if (!trigger.dispatchEvent(startEvent)) {
                return;
            }

            startLoading();

            try {
                let html = '';
                let finalUrl = url;
                let version = '';

                // 1. Try to resolve from cache (only for GET requests)
                const isGet = !options.method || options.method.toUpperCase() === 'GET';
                const cached = isGet ? getCachedResponse(url) : null;

                if (cached) {
                    html = cached.html;
                    finalUrl = cached.finalUrl;
                    version = cached.version;
                } else {
                    // Check if there is an active prefetch running for this URL
                    let responseData = null;
                    if (isGet && activeRequests.has(url)) {
                        responseData = await activeRequests.get(url);
                    }

                    if (responseData) {
                        html = responseData.html;
                        finalUrl = responseData.finalUrl;
                        version = responseData.version;
                    } else {
                        // Perform live fetch
                        const fetchHeaders = {
                            ...(options.headers || {}),
                            'X-Catchy-SPA': 'true'
                        };
                        if (currentVersion) {
                            fetchHeaders['X-Catchy-Version'] = currentVersion;
                        }

                        const fetchOptions = {
                            ...options,
                            headers: fetchHeaders
                        };

                        const response = await fetch(url, fetchOptions);

                        // If version mismatch (409 Conflict), force immediate hard reload of target URL
                        if (response.status === 409) {
                            window.location.href = url;
                            return;
                        }

                        if (!response.ok) {
                            throw new Error(`Catchy: Request failed with status ${response.status}`);
                        }

                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('text/html')) {
                            window.location.href = response.url || url;
                            return;
                        }

                        html = await response.text();
                        finalUrl = response.url || url;
                        version = response.headers.get('X-Catchy-Version') || '';
                        
                        // Extract dynamic flash messages from the header
                        const flashHeader = response.headers.get('X-Catchy-Flash');
                        if (flashHeader) {
                            try {
                                const binaryString = atob(flashHeader);
                                const bytes = new Uint8Array(binaryString.length);
                                for (let i = 0; i < binaryString.length; i++) {
                                    bytes[i] = binaryString.charCodeAt(i);
                                }
                                const flashJson = new TextDecoder('utf-8').decode(bytes);
                                const flash = JSON.parse(flashJson);
                                window.dispatchEvent(new CustomEvent('catchy:flash', { detail: flash }));
                            } catch (e) {
                                console.error('Catchy: Failed to decode X-Catchy-Flash header', e);
                            }
                        }

                        // Cache GET requests
                        if (isGet) {
                            cache.set(url, {
                                html,
                                version,
                                finalUrl,
                                timestamp: Date.now()
                            });
                        }
                    }
                }

                // Update version pointer
                if (version) {
                    currentVersion = version;
                }

                // Resolve the target container ID (support dataset overriding)
                const targetId = options.targetId || (trigger && typeof trigger.getAttribute === 'function' ? trigger.getAttribute('data-catchy-target') : null) || config.containerId;

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Check for modal routing
                const isModalTarget = options.modal || (trigger && typeof trigger.hasAttribute === 'function' && trigger.hasAttribute('data-catchy-modal'));

                if (isModalTarget) {
                    const incomingContent = doc.getElementById(targetId) || doc.getElementById(config.containerId) || doc.body;
                    const modal = document.querySelector('[catchy-modal]') || document.getElementById('catchy-modal');
                    if (modal) {
                        modal.dispatchEvent(new CustomEvent('catchy:modal-load', {
                            detail: {
                                html: incomingContent.innerHTML,
                                title: doc.title || ''
                            }
                        }));
                        stopLoading();

                        executeCallback(trigger, 'data-catchy-success', { url: finalUrl, trigger });

                        trigger.dispatchEvent(new CustomEvent('catchy:end', {
                            bubbles: true,
                            detail: { url: finalUrl, trigger }
                        }));
                        return;
                    }
                }

                // Check if trigger itself was inside an active modal
                const isTriggerInModal = trigger && typeof trigger.closest === 'function' && (trigger.closest('[catchy-modal]') || trigger.closest('#catchy-modal'));

                if (isTriggerInModal && options.method && options.method.toUpperCase() !== 'GET') {
                    // Form inside modal submitted successfully -> close modal & morph the main layout container
                    const modal = document.querySelector('[catchy-modal]') || document.getElementById('catchy-modal');
                    if (modal) {
                        modal.dispatchEvent(new CustomEvent('catchy:modal-close'));
                    }

                    const mainContainer = document.getElementById(config.containerId);
                    const incomingMain = doc.getElementById(config.containerId) || doc.body;

                    if (mainContainer && incomingMain) {
                        if (doc.title) document.title = doc.title;

                        trigger.dispatchEvent(new CustomEvent('catchy:morphing', {
                            bubbles: true,
                            detail: { url: finalUrl, html, element: mainContainer, trigger }
                        }));

                        if (!Alpine.morph) {
                            console.error('Catchy: Alpine.morph is not defined. Ensure @alpinejs/morph is loaded and registered.');
                            window.location.href = finalUrl;
                            return;
                        }
                        Alpine.morph(mainContainer, incomingMain.outerHTML);
                    }
                } else {
                    // Standard container morph
                    const appContainer = document.getElementById(targetId);
                    if (!appContainer) {
                        window.location.href = finalUrl;
                        return;
                    }

                    const incomingApp = doc.getElementById(targetId) || doc.getElementById(config.containerId);
                    if (!incomingApp) {
                        window.location.href = finalUrl;
                        return;
                    }

                    if (doc.title) document.title = doc.title;

                    trigger.dispatchEvent(new CustomEvent('catchy:morphing', {
                        bubbles: true,
                        detail: { url: finalUrl, html, element: appContainer, trigger }
                    }));

                    if (!Alpine.morph) {
                        console.error(
                            'Catchy: Alpine.morph is not defined. Ensure @alpinejs/morph is loaded and registered.'
                        );
                        window.location.href = finalUrl;
                        return;
                    }
                    Alpine.morph(appContainer, incomingApp.outerHTML);
                }

                // Manage History Updates
                const shouldUpdateHistory = updateHistory && 
                    (!trigger || typeof trigger.getAttribute !== 'function' || trigger.getAttribute('data-catchy-history') !== 'false') &&
                    (!trigger || typeof trigger.hasAttribute !== 'function' || !trigger.hasAttribute('data-catchy-modal'));

                if (shouldUpdateHistory) {
                    window.history.pushState({ catchy: true, url: finalUrl }, '', finalUrl);
                }

                // Handle anchor scroll or scroll position restoration
                if (options.state && typeof options.state.scrollX === 'number' && typeof options.state.scrollY === 'number') {
                    window.scrollTo({
                        left: options.state.scrollX,
                        top: options.state.scrollY,
                        behavior: 'instant'
                    });
                } else {
                    const finalURLObj = new URL(finalUrl);
                    if (finalURLObj.hash) {
                        const el = document.querySelector(finalURLObj.hash);
                        if (el) {
                            el.scrollIntoView();
                        }
                    } else {
                        window.scrollTo({ top: 0, behavior: 'instant' });
                    }
                }

                stopLoading();

                executeCallback(trigger, 'data-catchy-success', { url: finalUrl, trigger });

                trigger.dispatchEvent(new CustomEvent('catchy:end', {
                    bubbles: true,
                    detail: { url: finalUrl, trigger }
                }));

            } catch (error) {
                resetLoading();
                console.error('Catchy: AJAX request error, falling back to full load.', error);

                executeCallback(trigger, 'data-catchy-error', { url, error, trigger });

                trigger.dispatchEvent(new CustomEvent('catchy:error', {
                    bubbles: true,
                    detail: { url, error, trigger }
                }));

                const isGet = !options.method || options.method.toUpperCase() === 'GET';
                if (isGet) {
                    window.location.href = url;
                }
            }
        }

        /**
         * Submits a form using AJAX and morphs the response.
         *
         * @param {HTMLFormElement} form
         */
        function submitForm(form) {
            const action = form.getAttribute('action') || window.location.href;
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            const url = new URL(action, window.location.href);

            if (method === 'GET') {
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);

                for (const [key, value] of params.entries()) {
                    url.searchParams.set(key, value);
                }

                visit(url.toString(), { trigger: form });
            } else {
                const formData = new FormData(form);
                const options = {
                    method: 'POST', // Always POST, laravel routes handle method spoofing
                    body: formData,
                    headers: {},
                    trigger: form
                };

                const token = document.querySelector('meta[name="csrf-token"]');
                if (token) {
                    options.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
                }

                visit(url.toString(), options);
            }
        }

        // Global Event: Click interceptor
        document.addEventListener('click', (event) => {
            const link = event.target && typeof event.target.closest === 'function' ? event.target.closest('a') : null;
            if (!link || shouldIgnoreLink(link, event)) return;

            event.preventDefault();
            visit(link.href, { trigger: link });
        });

        // Global Event: Hover (prefetch) interceptor
        if (config.prefetch) {
            document.addEventListener('mouseenter', (event) => {
                const link = event.target && typeof event.target.closest === 'function' ? event.target.closest('a') : null;
                if (!link || shouldIgnoreLink(link, null)) return;

                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    prefetch(link.href);
                }, config.prefetchDelay);
            }, true);

            document.addEventListener('mouseleave', (event) => {
                const link = event.target && typeof event.target.closest === 'function' ? event.target.closest('a') : null;
                if (link) {
                    clearTimeout(hoverTimeout);
                }
            }, true);
        }

        // Global Event: Form submit interceptor
        document.addEventListener('submit', (event) => {
            const form = event.target && typeof event.target.closest === 'function' ? event.target.closest('form') : null;
            if (!form || shouldIgnoreForm(form)) return;

            event.preventDefault();
            submitForm(form);
        });

        // Global Event: Popstate handling
        window.addEventListener('popstate', (event) => {
            const state = event.state;
            visit(window.location.href, { state }, false);
        });

        // Expose public API
        Alpine.catchy = { visit, prefetch, cache };
    };
}));
