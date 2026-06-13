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
        "use strict";

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
         * Re-evaluates and executes any script tags inside the morphed container.
         *
         * @param {HTMLElement} container
         */
        function executeScriptsInContainer(container) {
            if (!container) return;
            const scripts = container.querySelectorAll('script');
            scripts.forEach(oldScript => {
                if (oldScript.hasAttribute('data-catchy-ignore')) return;

                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => {
                    newScript.setAttribute(attr.name, attr.value);
                });
                newScript.textContent = oldScript.textContent;
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        }

        /**
         * Merges head metadata, styles, and scripts from incoming document to current document.
         *
         * @param {HTMLHeadElement} incomingHead
         */
        function mergeHead(incomingHead) {
            if (!incomingHead) return;

            // Merge meta tags (by name, property, or http-equiv)
            const currentMetaTags = Array.from(document.head.querySelectorAll('meta'));
            const incomingMetaTags = Array.from(incomingHead.querySelectorAll('meta'));

            incomingMetaTags.forEach(incomingMeta => {
                const name = incomingMeta.getAttribute('name');
                const property = incomingMeta.getAttribute('property');
                const httpEquiv = incomingMeta.getAttribute('http-equiv');

                let existingMeta = null;
                if (name) {
                    existingMeta = currentMetaTags.find(m => m.getAttribute('name') === name);
                } else if (property) {
                    existingMeta = currentMetaTags.find(m => m.getAttribute('property') === property);
                } else if (httpEquiv) {
                    existingMeta = currentMetaTags.find(m => m.getAttribute('http-equiv') === httpEquiv);
                }

                if (existingMeta) {
                    if (existingMeta.getAttribute('content') !== incomingMeta.getAttribute('content')) {
                        existingMeta.setAttribute('content', incomingMeta.getAttribute('content'));
                    }
                } else {
                    document.head.appendChild(incomingMeta.cloneNode(true));
                }
            });

            // Merge link tags (stylesheets, fonts, etc., by href)
            const currentLinks = Array.from(document.head.querySelectorAll('link'));
            const incomingLinks = Array.from(incomingHead.querySelectorAll('link'));

            incomingLinks.forEach(incomingLink => {
                const href = incomingLink.getAttribute('href');
                const rel = incomingLink.getAttribute('rel');
                if (href) {
                    const exists = currentLinks.some(l => l.getAttribute('href') === href && l.getAttribute('rel') === rel);
                    if (!exists) {
                        document.head.appendChild(incomingLink.cloneNode(true));
                    }
                }
            });
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
                    if (response.status === 409 || response.status === 429) {
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

                    const titleHeader = response.headers.get('X-Catchy-Title');
                    let title = '';
                    if (titleHeader) {
                        try {
                            const binaryString = atob(titleHeader);
                            const bytes = new Uint8Array(binaryString.length);
                            for (let i = 0; i < binaryString.length; i++) {
                                bytes[i] = binaryString.charCodeAt(i);
                            }
                            title = new TextDecoder('utf-8').decode(bytes);
                        } catch (e) {}
                    }

                    const cacheEntry = {
                        html,
                        version,
                        title,
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
         * Declaratively handles opening/closing of modals and offcanvas drawers 
         * based on request success/error state.
         *
         * @param {HTMLElement} trigger
         * @param {string} type ('success' | 'error')
         */
        function handleLifecycleTriggers(trigger, type) {
            if (!trigger || typeof trigger.getAttribute !== 'function') return;

            const openModal = trigger.getAttribute(`data-catchy-${type}-open-modal`);
            if (openModal) {
                const m = document.getElementById(openModal);
                if (m) m.dispatchEvent(new CustomEvent('catchy:modal-open', { bubbles: true }));
            }

            const closeModal = trigger.getAttribute(`data-catchy-${type}-close-modal`);
            if (closeModal) {
                const m = document.getElementById(closeModal);
                if (m) m.dispatchEvent(new CustomEvent('catchy:modal-close', { bubbles: true }));
            }

            const openOffcanvas = trigger.getAttribute(`data-catchy-${type}-open-offcanvas`);
            if (openOffcanvas) {
                const oc = document.getElementById(openOffcanvas);
                if (oc) oc.dispatchEvent(new CustomEvent('catchy:offcanvas-open', { bubbles: true }));
            }

            const closeOffcanvas = trigger.getAttribute(`data-catchy-${type}-close-offcanvas`);
            if (closeOffcanvas) {
                const oc = document.getElementById(closeOffcanvas);
                if (oc) oc.dispatchEvent(new CustomEvent('catchy:offcanvas-close', { bubbles: true }));
            }
        }

        /**
         * Helper to wrap XHR in a Promise resembling a fetch Response.
         *
         * @param {string} url
         * @param {Object} options
         * @returns {Promise<Object>}
         */
        function xhrRequest(url, options = {}) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open(options.method || 'GET', url);

                // Set headers
                if (options.headers) {
                    Object.entries(options.headers).forEach(([key, val]) => {
                        xhr.setRequestHeader(key, val);
                    });
                }

                // Add progress tracking
                if (xhr.upload && options.trigger) {
                    xhr.upload.addEventListener('progress', (e) => {
                        const percent = e.lengthComputable ? Math.round((e.loaded / e.total) * 100) : 0;
                        const progressDetail = { loaded: e.loaded, total: e.total, percent, trigger: options.trigger };
                        
                        options.trigger.dispatchEvent(new CustomEvent('catchy:progress', {
                            bubbles: true,
                            detail: progressDetail
                        }));
                        options.trigger.dispatchEvent(new CustomEvent('catchy-progress', {
                            bubbles: true,
                            detail: progressDetail
                        }));
                    });
                }

                xhr.onload = () => {
                    const headersMap = new Map();
                    const rawHeaders = xhr.getAllResponseHeaders();
                    rawHeaders.split('\r\n').forEach(line => {
                        const parts = line.split(': ');
                        const header = parts.shift().toLowerCase();
                        const value = parts.join(': ');
                        if (header) {
                            headersMap.set(header, value);
                        }
                    });

                    const responseLike = {
                        status: xhr.status,
                        ok: xhr.status >= 200 && xhr.status < 300,
                        url: xhr.responseURL || url,
                        redirected: xhr.responseURL && xhr.responseURL !== url,
                        headers: {
                            get: (name) => headersMap.get(name.toLowerCase()) || null
                        },
                        text: () => Promise.resolve(xhr.responseText)
                    };
                    resolve(responseLike);
                };

                xhr.onerror = () => {
                    reject(new Error('Catchy: XHR Request failed'));
                };

                xhr.send(options.body || null);
            });
        }

        /**
         * Resolves the target modal element based on the trigger or default selectors.
         *
         * @param {HTMLElement} triggerElement
         * @returns {HTMLElement|null}
         */
        function resolveModal(triggerElement) {
            const modalAttr = triggerElement && typeof triggerElement.getAttribute === 'function' ? triggerElement.getAttribute('data-catchy-modal') : null;
            if (modalAttr && modalAttr !== '' && modalAttr !== 'true') {
                const specificModal = document.getElementById(modalAttr);
                if (specificModal) return specificModal;
            }
            if (triggerElement && typeof triggerElement.closest === 'function') {
                const closestModal = triggerElement.closest('[catchy-modal]') || triggerElement.closest('#catchy-modal');
                if (closestModal) return closestModal;
            }
            return document.querySelector('[catchy-modal]') || document.getElementById('catchy-modal');
        }

        /**
         * Resolves the target offcanvas element based on the trigger or default selectors.
         *
         * @param {HTMLElement} triggerElement
         * @returns {HTMLElement|null}
         */
        function resolveOffcanvas(triggerElement) {
            const offcanvasAttr = triggerElement && typeof triggerElement.getAttribute === 'function' ? triggerElement.getAttribute('data-catchy-offcanvas') : null;
            if (offcanvasAttr && offcanvasAttr !== '' && offcanvasAttr !== 'true') {
                const specificOffcanvas = document.getElementById(offcanvasAttr);
                if (specificOffcanvas) return specificOffcanvas;
            }
            if (triggerElement && typeof triggerElement.closest === 'function') {
                const closestOffcanvas = triggerElement.closest('[catchy-offcanvas]') || triggerElement.closest('#catchy-offcanvas');
                if (closestOffcanvas) return closestOffcanvas;
            }
            return document.querySelector('[catchy-offcanvas]') || document.getElementById('catchy-offcanvas');
        }

        /**
         * Fetch a page and update the DOM container.
         *
         * @param {string} url
         * @param {Object} options
         * @param {boolean} updateHistory
         */
        async function visit(url, options = {}, updateHistory = true) {
            const oldPathname = window.location.pathname;
            // Save current scroll coordinates in history state before navigating away
            try {
                window.history.replaceState({
                    ...window.history.state,
                    scrollX: window.scrollX,
                    scrollY: window.scrollY
                }, '');
            } catch (e) { }

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
            const startEventHyphen = new CustomEvent('catchy-start', {
                bubbles: true,
                cancelable: true,
                detail: { url, options, trigger }
            });

            if (!trigger.dispatchEvent(startEvent) || !trigger.dispatchEvent(startEventHyphen)) {
                return;
            }

            // Find and disable submit button, showing an inline SVG spinner loader
            let submitBtn = null;
            if (trigger && (trigger.tagName === 'FORM' || trigger instanceof HTMLFormElement) && !trigger.hasAttribute('data-catchy-no-loader')) {
                submitBtn = trigger.querySelector('[type="submit"]') || trigger.querySelector('button:not([type="button"])');
                if (submitBtn && !submitBtn.dataset.originalHtml) {
                    submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.classList.add('pointer-events-none');
                    const spinnerHtml = `<svg class="animate-spin -ms-1 me-2 h-4 w-4 text-current inline-block align-text-bottom" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="vertical-align: middle;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> `;
                    submitBtn.innerHTML = spinnerHtml + submitBtn.innerHTML;
                }
            }

            function restoreSubmitButton() {
                if (submitBtn && submitBtn.dataset.originalHtml) {
                    submitBtn.innerHTML = submitBtn.dataset.originalHtml;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('pointer-events-none');
                    delete submitBtn.dataset.originalHtml;
                }
            }

            startLoading();

            try {
                let html = '';
                let finalUrl = url;
                let version = '';
                let response = null;

                // 1. Try to resolve from cache (only for GET requests)
                const isGet = !options.method || options.method.toUpperCase() === 'GET';
                const cached = isGet ? getCachedResponse(url) : null;

                if (cached) {
                    html = cached.html;
                    finalUrl = cached.finalUrl;
                    version = cached.version;
                    if (cached.title) {
                        document.title = cached.title;
                    }
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
                        if (responseData.title) {
                            document.title = responseData.title;
                        }
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

                        if (options.method && options.method.toUpperCase() !== 'GET') {
                            response = await xhrRequest(url, fetchOptions);
                        } else {
                            response = await fetch(url, fetchOptions);
                        }

                        // If version mismatch (409 Conflict), force immediate hard reload of target URL
                        if (response.status === 409) {
                            window.location.href = url;
                            return;
                        }

                        if (!response.ok) {
                            if (response.status === 422 || response.status === 400) {
                                const contentType = response.headers.get('content-type');
                                if (contentType && contentType.includes('application/json')) {
                                    try {
                                        const json = JSON.parse(await response.text());
                                        if (json.errors) {
                                            const errDetail = json.errors;
                                            window.dispatchEvent(new CustomEvent('catchy:validation-errors', { detail: errDetail }));
                                            window.dispatchEvent(new CustomEvent('catchy-validation-errors', { detail: errDetail }));
                                            if (trigger) {
                                                trigger.dispatchEvent(new CustomEvent('catchy:validation-errors', { bubbles: true, detail: errDetail }));
                                                trigger.dispatchEvent(new CustomEvent('catchy-validation-errors', { bubbles: true, detail: errDetail }));
                                            }
                                        }
                                    } catch (e) {}
                                }
                            }
                            throw new Error(`Catchy: Request failed with status ${response.status}`);
                        }

                        // Check if the response contains a redirect header
                        const redirectUrl = response.headers.get('X-Catchy-Redirect');
                        if (redirectUrl) {
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
                                    window.dispatchEvent(new CustomEvent('catchy-flash', { detail: flash }));

                                    if (flash.validation_errors) {
                                        const errDetail = flash.validation_errors;
                                        window.dispatchEvent(new CustomEvent('catchy:validation-errors', { detail: errDetail }));
                                        window.dispatchEvent(new CustomEvent('catchy-validation-errors', { detail: errDetail }));
                                        if (trigger) {
                                            trigger.dispatchEvent(new CustomEvent('catchy:validation-errors', { bubbles: true, detail: errDetail }));
                                            trigger.dispatchEvent(new CustomEvent('catchy-validation-errors', { bubbles: true, detail: errDetail }));
                                        }
                                    }
                                } catch (e) {
                                    console.error('Catchy: Failed to decode X-Catchy-Flash header', e);
                                }
                            }

                            visit(redirectUrl, { trigger, targetId: config.containerId }, updateHistory);
                            return;
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
                                window.dispatchEvent(new CustomEvent('catchy-flash', { detail: flash }));

                                if (flash.validation_errors) {
                                    const errDetail = flash.validation_errors;
                                    window.dispatchEvent(new CustomEvent('catchy:validation-errors', { detail: errDetail }));
                                    window.dispatchEvent(new CustomEvent('catchy-validation-errors', { detail: errDetail }));
                                    if (trigger) {
                                        trigger.dispatchEvent(new CustomEvent('catchy:validation-errors', { bubbles: true, detail: errDetail }));
                                        trigger.dispatchEvent(new CustomEvent('catchy-validation-errors', { bubbles: true, detail: errDetail }));
                                    }
                                }
                            } catch (e) {
                                console.error('Catchy: Failed to decode X-Catchy-Flash header', e);
                            }
                        }

                        // Decode and set title if present in headers
                        let title = '';
                        const titleHeader = response.headers.get('X-Catchy-Title');
                        if (titleHeader) {
                            try {
                                const binaryString = atob(titleHeader);
                                const bytes = new Uint8Array(binaryString.length);
                                for (let i = 0; i < binaryString.length; i++) {
                                    bytes[i] = binaryString.charCodeAt(i);
                                }
                                title = new TextDecoder('utf-8').decode(bytes);
                                if (title) {
                                    document.title = title;
                                }
                            } catch (e) {
                                console.error('Catchy: Failed to decode X-Catchy-Title header', e);
                            }
                        }

                        // Cache GET requests
                        if (isGet) {
                            cache.set(url, {
                                html,
                                version,
                                title,
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

                if (doc.head) {
                    mergeHead(doc.head);
                }

                // Check for offcanvas routing
                const isOffcanvasTarget = options.offcanvas || (trigger && typeof trigger.hasAttribute === 'function' && trigger.hasAttribute('data-catchy-offcanvas'));

                if (isOffcanvasTarget) {
                    const incomingContent = doc.getElementById(targetId) || doc.getElementById(config.containerId) || doc.body;
                    const offcanvas = resolveOffcanvas(trigger);
                    if (offcanvas) {
                        offcanvas.dispatchEvent(new CustomEvent('catchy:offcanvas-load', {
                            bubbles: true,
                            detail: {
                                html: incomingContent.innerHTML,
                                title: doc.title || ''
                            }
                        }));
                        offcanvas.dispatchEvent(new CustomEvent('catchy-offcanvas-load', {
                            bubbles: true,
                            detail: {
                                html: incomingContent.innerHTML,
                                title: doc.title || ''
                            }
                        }));
                        stopLoading();

                        executeCallback(trigger, 'data-catchy-success', { url: finalUrl, trigger });
                        handleLifecycleTriggers(trigger, 'success');
                        restoreSubmitButton();

                        trigger.dispatchEvent(new CustomEvent('catchy:end', {
                            bubbles: true,
                            detail: { url: finalUrl, trigger }
                        }));
                        trigger.dispatchEvent(new CustomEvent('catchy-end', {
                            bubbles: true,
                            detail: { url: finalUrl, trigger }
                        }));
                        return;
                    }
                }

                // Check for modal routing
                const isModalTarget = options.modal || (trigger && typeof trigger.hasAttribute === 'function' && trigger.hasAttribute('data-catchy-modal'));

                if (isModalTarget) {
                    const incomingContent = doc.getElementById(targetId) || doc.getElementById(config.containerId) || doc.body;
                    const modal = resolveModal(trigger);
                    if (modal) {
                        modal.dispatchEvent(new CustomEvent('catchy:modal-load', {
                            bubbles: true,
                            detail: {
                                html: incomingContent.innerHTML,
                                title: doc.title || ''
                            }
                        }));
                        modal.dispatchEvent(new CustomEvent('catchy-modal-load', {
                            bubbles: true,
                            detail: {
                                html: incomingContent.innerHTML,
                                title: doc.title || ''
                            }
                        }));
                        stopLoading();

                        executeCallback(trigger, 'data-catchy-success', { url: finalUrl, trigger });
                        handleLifecycleTriggers(trigger, 'success');
                        restoreSubmitButton();

                        trigger.dispatchEvent(new CustomEvent('catchy:end', {
                            bubbles: true,
                            detail: { url: finalUrl, trigger }
                        }));
                        trigger.dispatchEvent(new CustomEvent('catchy-end', {
                            bubbles: true,
                            detail: { url: finalUrl, trigger }
                        }));
                        return;
                    }
                }

                // Check if trigger itself was inside an active offcanvas
                const isTriggerInOffcanvas = trigger && typeof trigger.closest === 'function' && (trigger.closest('[catchy-offcanvas]') || trigger.closest('#catchy-offcanvas'));

                if (isTriggerInOffcanvas && options.method && options.method.toUpperCase() !== 'GET') {
                    // Form inside offcanvas submitted successfully -> close offcanvas
                    const offcanvas = resolveOffcanvas(trigger);
                    if (offcanvas) {
                        offcanvas.dispatchEvent(new CustomEvent('catchy:offcanvas-close', { bubbles: true }));
                        offcanvas.dispatchEvent(new CustomEvent('catchy-offcanvas-close', { bubbles: true }));
                    }
                }

                // Check if trigger itself was inside an active modal
                const isTriggerInModal = trigger && typeof trigger.closest === 'function' && (trigger.closest('[catchy-modal]') || trigger.closest('#catchy-modal'));

                if (isTriggerInModal && options.method && options.method.toUpperCase() !== 'GET') {
                    // Form inside modal submitted successfully -> close modal & morph the main layout container
                    const modal = resolveModal(trigger);
                    if (modal) {
                        modal.dispatchEvent(new CustomEvent('catchy:modal-close', {
                            bubbles: true
                        }));
                        modal.dispatchEvent(new CustomEvent('catchy-modal-close', {
                            bubbles: true
                        }));
                    }

                    const mainContainer = document.getElementById(config.containerId);
                    const incomingMain = doc.getElementById(config.containerId) || doc.body;

                    if (mainContainer && incomingMain) {
                        if (doc.title) document.title = doc.title;

                        trigger.dispatchEvent(new CustomEvent('catchy:morphing', {
                            bubbles: true,
                            detail: { url: finalUrl, html, element: mainContainer, trigger }
                        }));
                        trigger.dispatchEvent(new CustomEvent('catchy-morphing', {
                            bubbles: true,
                            detail: { url: finalUrl, html, element: mainContainer, trigger }
                        }));

                        if (!Alpine.morph) {
                            console.error('Catchy: Alpine.morph is not defined. Ensure @alpinejs/morph is loaded and registered.');
                            window.location.href = finalUrl;
                            return;
                        }
                        Alpine.morph(mainContainer, incomingMain.outerHTML);
                        executeScriptsInContainer(mainContainer);
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
                    trigger.dispatchEvent(new CustomEvent('catchy-morphing', {
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
                    executeScriptsInContainer(appContainer);
                }

                // Manage History Updates
                const shouldUpdateHistory = updateHistory &&
                    (isGet || (response && response.redirected)) &&
                    (!trigger || typeof trigger.getAttribute !== 'function' || trigger.getAttribute('data-catchy-history') !== 'false') &&
                    (!trigger || typeof trigger.hasAttribute !== 'function' || (!trigger.hasAttribute('data-catchy-modal') && !trigger.hasAttribute('data-catchy-offcanvas')));

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
                    const keepScroll = trigger && typeof trigger.getAttribute === 'function' && trigger.getAttribute('data-catchy-scroll') === 'keep';
                    
                    if (keepScroll) {
                        // Do not change scroll position
                    } else if (finalURLObj.hash) {
                        const el = document.querySelector(finalURLObj.hash);
                        if (el) {
                            el.scrollIntoView();
                        }
                    } else {
                        // Scroll to top only for GET visits that target the main container (new page view)
                        // Skip scrolling if the visit was triggered by a form submission (keep focus on form context) unless the path changed
                        const isFormSubmit = trigger && typeof trigger.tagName === 'string' && trigger.tagName.toUpperCase() === 'FORM';
                        if (isGet && targetId === config.containerId && (!isFormSubmit || finalURLObj.pathname !== oldPathname)) {
                            window.scrollTo({ top: 0, behavior: 'instant' });
                        }
                    }
                }

                stopLoading();

                executeCallback(trigger, 'data-catchy-success', { url: finalUrl, trigger });
                handleLifecycleTriggers(trigger, 'success');
                restoreSubmitButton();

                trigger.dispatchEvent(new CustomEvent('catchy:end', {
                    bubbles: true,
                    detail: { url: finalUrl, trigger }
                }));
                trigger.dispatchEvent(new CustomEvent('catchy-end', {
                    bubbles: true,
                    detail: { url: finalUrl, trigger }
                }));

            } catch (error) {
                resetLoading();
                console.error('Catchy: AJAX request error, falling back to full load.', error);

                executeCallback(trigger, 'data-catchy-error', { url, error, trigger });
                handleLifecycleTriggers(trigger, 'error');
                restoreSubmitButton();

                trigger.dispatchEvent(new CustomEvent('catchy:error', {
                    bubbles: true,
                    detail: { url, error, trigger }
                }));
                trigger.dispatchEvent(new CustomEvent('catchy-error', {
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
            const target = event.target;
            if (!target || typeof target.closest !== 'function') return;

            // Handle Modal open/close trigger attributes
            const openModalEl = target.closest('[data-catchy-open-modal]');
            if (openModalEl) {
                const modalId = openModalEl.getAttribute('data-catchy-open-modal');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.dispatchEvent(new CustomEvent('catchy:modal-open', { bubbles: true }));
                }
            }

            const closeModalEl = target.closest('[data-catchy-close-modal]');
            if (closeModalEl) {
                const modalId = closeModalEl.getAttribute('data-catchy-close-modal');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.dispatchEvent(new CustomEvent('catchy:modal-close', { bubbles: true }));
                }
            }

            // Handle Offcanvas open/close trigger attributes
            const openOffcanvasEl = target.closest('[data-catchy-open-offcanvas]');
            if (openOffcanvasEl) {
                const offcanvasId = openOffcanvasEl.getAttribute('data-catchy-open-offcanvas');
                const offcanvas = document.getElementById(offcanvasId);
                if (offcanvas) {
                    offcanvas.dispatchEvent(new CustomEvent('catchy:offcanvas-open', { bubbles: true }));
                }
            }

            const closeOffcanvasEl = target.closest('[data-catchy-close-offcanvas]');
            if (closeOffcanvasEl) {
                const offcanvasId = closeOffcanvasEl.getAttribute('data-catchy-close-offcanvas');
                const offcanvas = document.getElementById(offcanvasId);
                if (offcanvas) {
                    offcanvas.dispatchEvent(new CustomEvent('catchy:offcanvas-close', { bubbles: true }));
                }
            }

            // Normal link SPA routing
            const link = target.closest('a');
            if (link && !shouldIgnoreLink(link, event)) {
                event.preventDefault();
                visit(link.href, { trigger: link });
            }
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

        // Initialize Viewport Prefetching
        function initViewportPrefetching() {
            if (typeof IntersectionObserver === 'undefined') return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const link = entry.target;
                        if (link.href && !shouldIgnoreLink(link, null)) {
                            prefetch(link.href);
                        }
                        observer.unobserve(link);
                    }
                });
            }, { rootMargin: '50px' });

            const observeLinks = (rootNode = document) => {
                const links = rootNode.querySelectorAll('a[data-catchy-prefetch="viewport"]');
                links.forEach(link => observer.observe(link));
            };

            observeLinks();

            const mutationObserver = new MutationObserver((mutations) => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.tagName === 'A' && node.getAttribute('data-catchy-prefetch') === 'viewport') {
                                observer.observe(node);
                            } else {
                                observeLinks(node);
                            }
                        }
                    });
                });
            });

            mutationObserver.observe(document.body, { childList: true, subtree: true });
        }

        initViewportPrefetching();

        // Expose public API
        Alpine.catchy = { visit, prefetch, cache, startLoading, stopLoading };
    };
}));
