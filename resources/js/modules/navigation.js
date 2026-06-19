/**
 * Catchy — Navigation Module
 *
 * Core visit() function for SPA page transitions.
 * Fully refactored to support SWR caching, targeted element reloads,
 * advanced scroll restoration, lifecycle event hooks, and optimistic UI class toggling.
 */

import { decodeBase64Utf8, emit, executeScriptsInContainer, focusAutofocusElements, executeCallback } from './utils.js';
import { getCachedResponse, setCachedResponse } from './cache.js';
import { startLoading, stopLoading, resetLoading } from './loader.js';
import { mergeHead, mergeHeadFromHeader } from './head-merge.js';
import { xhrRequest } from './forms.js';
import { getActiveRequests } from './prefetch.js';
import { resolveModal, resolveOffcanvas, handleLifecycleTriggers } from './components.js';

let currentVersion = '';

/**
 * Get the current asset version string.
 * @returns {string}
 */
export function getCurrentVersion() {
    return currentVersion;
}

/**
 * Set the current asset version string.
 * @param {string} version
 */
export function setCurrentVersion(version) {
    currentVersion = version;
}

/**
 * Fetch a page and update the DOM container via Alpine.morph.
 *
 * @param {string} url
 * @param {Object} options
 * @param {boolean} updateHistory
 * @param {Object} config
 * @param {Object} Alpine
 */
export async function visit(url, options = {}, updateHistory = true, config = {}, Alpine = null) {
    if (navigator.onLine === false) {
        emit('flash', { message: 'Cannot navigate. You are currently offline.', type: 'warning' });
        return;
    }

    const oldPathname = window.location.pathname;

    // Cache current scroll coordinates before navigating away
    try {
        window.history.replaceState({
            ...window.history.state,
            scrollX: window.scrollX,
            scrollY: window.scrollY
        }, '');
    } catch (e) {}

    const trigger = options.trigger || document;

    // 1. Dispatch catchy:before-visit event, cancel if prevented by user
    if (!emit('before-visit', { url, options, trigger }, trigger, { cancelable: true })) {
        return;
    }

    if (executeCallback(trigger, 'data-catchy-beforesend', { url, options, trigger }) === false) {
        return;
    }

    if (!emit('start', { url, options, trigger }, trigger, { cancelable: true })) {
        return;
    }

    // 2. Optimistic UI Updates / Spinner Loader
    const submitBtn = setupSubmitSpinner(trigger);
    const optimisticClasses = trigger && typeof trigger.getAttribute === 'function'
        ? trigger.getAttribute('data-catchy-optimistic-class')
        : null;

    if (optimisticClasses && trigger) {
        trigger.classList.add(...optimisticClasses.split(' ').filter(Boolean));
    }

    const cleanUpUi = () => {
        restoreSubmitButton(submitBtn);
        if (optimisticClasses && trigger) {
            trigger.classList.remove(...optimisticClasses.split(' ').filter(Boolean));
        }
    };

    const isGet = !options.method || options.method.toUpperCase() === 'GET';
    const targetId = getTargetContainerId(trigger, options, config);

    // 3. Stale-While-Revalidate (SWR) Cache check
    const cached = isGet && config.swr ? getCachedResponse(url, config.cacheTTL) : null;

    if (cached) {
        try {
            renderResponseData(cached, targetId, config, Alpine, trigger);
            applyScroll(trigger, targetId, cached.finalUrl, oldPathname, options, config);

            cleanUpUi();
            emit('end', { url: cached.finalUrl, trigger, fromCache: true }, trigger);
            emit('after-visit', { url: cached.finalUrl, trigger, fromCache: true }, trigger);
        } catch (e) {
            console.error('Catchy: SWR instant render failed, falling back to network.', e);
        }

        // Revalidate silently in the background
        fetchFreshContent(url, options, targetId, config, Alpine, trigger, cleanUpUi, false);
        return;
    }

    // No SWR cache found -> Full fetch visit
    startLoading();
    fetchFreshContent(url, options, targetId, config, Alpine, trigger, cleanUpUi, updateHistory);
}

/**
 * Perform network request to fetch fresh HTML page.
 */
async function fetchFreshContent(url, options, targetId, config, Alpine, trigger, cleanUpUi, updateHistory) {
    const isGet = !options.method || options.method.toUpperCase() === 'GET';
    const oldPathname = window.location.pathname;

    try {
        let response = null;

        // Check if there is an active prefetch running for this URL
        const activeRequests = getActiveRequests();
        let responseData = null;
        if (isGet && activeRequests.has(url)) {
            responseData = await activeRequests.get(url);
        }

        let html, finalUrl, version, headContent, title;

        if (responseData) {
            html = responseData.html;
            finalUrl = responseData.finalUrl;
            version = responseData.version;
            headContent = responseData.head || null;
            title = responseData.title || '';
        } else {
            // Setup headers, appending dynamic targets
            const fetchHeaders = {
                ...(options.headers || {}),
                'X-Catchy-SPA': 'true',
                'X-Catchy-Target': targetId
            };
            if (currentVersion) {
                fetchHeaders['X-Catchy-Version'] = currentVersion;
            }

            const fetchOptions = { ...options, headers: fetchHeaders };

            if (options.method && options.method.toUpperCase() !== 'GET') {
                response = await xhrRequest(url, fetchOptions);
            } else {
                response = await fetch(url, fetchOptions);
            }

            if (response.status === 409) {
                window.location.href = url;
                return;
            }

            if (!response.ok) {
                handleFetchError(response, trigger);
                throw new Error(`Catchy: Request failed with status ${response.status}`);
            }

            const redirectUrl = response.headers.get('X-Catchy-Redirect');
            if (redirectUrl) {
                handleRedirect(redirectUrl, trigger, config, Alpine, updateHistory);
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
            headContent = response.headers.get('X-Catchy-Head') || null;

            processFlashHeader(response, trigger);

            const titleHeader = response.headers.get('X-Catchy-Title');
            title = titleHeader ? decodeBase64Utf8(titleHeader) : '';
        }

        const dataToRender = { html, version, title, head: headContent, finalUrl };

        if (isGet) {
            setCachedResponse(url, dataToRender);
        }

        if (version) currentVersion = version;

        // Render fresh updates
        renderResponseData(dataToRender, targetId, config, Alpine, trigger);

        if (updateHistory) {
            manageHistory(finalUrl, trigger, isGet, response);
        }

        applyScroll(trigger, targetId, finalUrl, oldPathname, options, config);

        stopLoading();
        executeCallback(trigger, 'data-catchy-success', { url: finalUrl, trigger });
        handleLifecycleTriggers(trigger, 'success');
        cleanUpUi();

        emit('end', { url: finalUrl, trigger }, trigger);
        emit('after-visit', { url: finalUrl, trigger }, trigger);

    } catch (error) {
        resetLoading();
        cleanUpUi();
        console.error('Catchy: AJAX request error, falling back to full load.', error);

        executeCallback(trigger, 'data-catchy-error', { url, error, trigger });
        handleLifecycleTriggers(trigger, 'error');
        emit('error', { url, error, trigger }, trigger);

        if (isGet) {
            window.location.href = url;
        }
    }
}

/**
 * Render HTML fragment, handling Modals, Offcanvas, and standard morphing.
 */
function renderResponseData(data, targetId, config, Alpine, trigger) {
    if (data.title) {
        document.title = data.title;
    }

    if (data.head) {
        mergeHeadFromHeader(data.head);
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(data.html, 'text/html');

    if (doc.head) {
        mergeHead(doc.head);
    }

    const isModalTarget = trigger && typeof trigger.hasAttribute === 'function' && trigger.hasAttribute('data-catchy-modal');
    if (isModalTarget) {
        const incomingContent = doc.getElementById(targetId) || doc.getElementById(config.containerId) || doc.body;
        const modal = resolveModal(trigger);
        if (modal) {
            emit('modal-load', { html: incomingContent.innerHTML, title: doc.title || '' }, modal);
            return;
        }
    }

    const isOffcanvasTarget = trigger && typeof trigger.hasAttribute === 'function' && trigger.hasAttribute('data-catchy-offcanvas');
    if (isOffcanvasTarget) {
        const incomingContent = doc.getElementById(targetId) || doc.getElementById(config.containerId) || doc.body;
        const offcanvas = resolveOffcanvas(trigger);
        if (offcanvas) {
            emit('offcanvas-load', { html: incomingContent.innerHTML, title: doc.title || '' }, offcanvas);
            return;
        }
    }

    const isTriggerInOffcanvas = trigger && typeof trigger.closest === 'function' && (trigger.closest('[catchy-offcanvas]') || trigger.closest('#catchy-offcanvas'));
    const isFormPost = trigger && trigger.tagName === 'FORM' && trigger.getAttribute('method')?.toUpperCase() !== 'GET';
    if (isTriggerInOffcanvas && isFormPost) {
        const offcanvas = resolveOffcanvas(trigger);
        if (offcanvas) emit('offcanvas-close', {}, offcanvas);
    }

    const isTriggerInModal = trigger && typeof trigger.closest === 'function' && (trigger.closest('[catchy-modal]') || trigger.closest('#catchy-modal'));
    if (isTriggerInModal && isFormPost) {
        const modal = resolveModal(trigger);
        if (modal) emit('modal-close', {}, modal);
    }

    // Standard DOM Morphing target
    const appContainer = document.getElementById(targetId);
    if (!appContainer) {
        window.location.href = data.finalUrl;
        return;
    }

    const incomingApp = doc.getElementById(targetId) || doc.getElementById(config.containerId);
    if (!incomingApp) {
        window.location.href = data.finalUrl;
        return;
    }

    // Emit catchy:before-morph
    emit('before-morph', { url: data.finalUrl, element: appContainer, trigger }, trigger);
    emit('morphing', { url: data.finalUrl, html: data.html, element: appContainer, trigger }, trigger);

    if (!Alpine.morph) {
        console.error('Catchy: Alpine.morph is not defined. Ensure @alpinejs/morph is loaded.');
        window.location.href = data.finalUrl;
        return;
    }

    Alpine.morph(appContainer, incomingApp.outerHTML);
    executeScriptsInContainer(appContainer);
    focusAutofocusElements(appContainer);

    // Emit catchy:after-morph
    emit('after-morph', { url: data.finalUrl, element: appContainer, trigger }, trigger);
}

/**
 * Apply scroll positions based on trigger attributes or options.
 */
function applyScroll(trigger, targetId, finalUrl, oldPathname, options, config) {
    if (options.state && typeof options.state.scrollX === 'number' && typeof options.state.scrollY === 'number') {
        window.scrollTo({ left: options.state.scrollX, top: options.state.scrollY, behavior: 'instant' });
        return;
    }

    const scrollSetting = trigger && typeof trigger.getAttribute === 'function'
        ? trigger.getAttribute('data-catchy-scroll')
        : null;

    if (scrollSetting === 'preserve' || options.scroll === 'preserve') {
        return;
    }

    if (scrollSetting === 'top' || options.scroll === 'top') {
        window.scrollTo({ top: 0, behavior: 'instant' });
        return;
    }

    const finalURLObj = new URL(finalUrl);
    if (finalURLObj.hash) {
        const el = document.querySelector(finalURLObj.hash);
        if (el) el.scrollIntoView();
        return;
    }

    const isFormSubmit = trigger && trigger.tagName === 'FORM';
    const isGet = !options.method || options.method.toUpperCase() === 'GET';
    if (isGet && targetId === config.containerId && (!isFormSubmit || finalURLObj.pathname !== oldPathname)) {
        window.scrollTo({ top: 0, behavior: 'instant' });
    }
}

/**
 * Append redirects internally.
 */
function handleRedirect(redirectUrl, trigger, config, Alpine, updateHistory) {
    try {
        const targetUrl = new URL(redirectUrl, window.location.href);
        if (targetUrl.origin !== window.location.origin) {
            window.location.href = redirectUrl;
            return;
        }
    } catch (e) {
        window.location.href = redirectUrl;
        return;
    }

    visit(redirectUrl, { trigger, targetId: config.containerId }, updateHistory, config, Alpine);
}

/**
 * Parse errors on failed requests.
 */
function handleFetchError(response, trigger) {
    if (response.status === 422 || response.status === 400) {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            response.text().then(text => {
                try {
                    const json = JSON.parse(text);
                    if (json.errors) {
                        emit('validation-errors', json.errors);
                        if (trigger) emit('validation-errors', json.errors, trigger);
                    }
                } catch (e) {}
            });
        }
    }
}

/**
 * Process base64 encoded flash headers.
 */
function processFlashHeader(response, trigger) {
    const flashHeader = response.headers.get('X-Catchy-Flash');
    if (!flashHeader) return;

    try {
        const flashJson = decodeBase64Utf8(flashHeader);
        const flash = JSON.parse(flashJson);
        emit('flash', flash);

        if (flash.validation_errors) {
            emit('validation-errors', flash.validation_errors);
            if (trigger) emit('validation-errors', flash.validation_errors, trigger);
        }
    } catch (e) {
        console.error('Catchy: Failed to decode X-Catchy-Flash header', e);
    }
}

/**
 * Setup inline submit SVG spin animation.
 */
function setupSubmitSpinner(trigger) {
    if (trigger && (trigger.tagName === 'FORM' || trigger instanceof HTMLFormElement) && !trigger.hasAttribute('data-catchy-no-loader')) {
        const submitBtn = trigger.querySelector('[type="submit"]') || trigger.querySelector('button:not([type="button"])');
        if (submitBtn && !submitBtn.dataset.originalHtml) {
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.classList.add('pointer-events-none');
            const spinnerHtml = `<svg class="animate-spin -ms-1 me-2 h-4 w-4 text-current inline-block align-text-bottom" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="vertical-align: middle;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> `;
            submitBtn.innerHTML = spinnerHtml + submitBtn.innerHTML;
            return submitBtn;
        }
    }
    return null;
}

/**
 * Remove spinner from submit button.
 */
function restoreSubmitButton(submitBtn) {
    if (submitBtn && submitBtn.dataset.originalHtml) {
        submitBtn.innerHTML = submitBtn.dataset.originalHtml;
        submitBtn.disabled = false;
        submitBtn.classList.remove('pointer-events-none');
        delete submitBtn.dataset.originalHtml;
    }
}

function getTargetContainerId(trigger, options, config) {
    return options.targetId ||
        (trigger && typeof trigger.getAttribute === 'function' ? trigger.getAttribute('data-catchy-target') : null) ||
        config.containerId;
}

function manageHistory(finalUrl, trigger, isGet, response) {
    const shouldUpdateHistory = isGet || (response && response.redirected);
    const hasHistoryAttr = trigger && typeof trigger.getAttribute === 'function' && trigger.getAttribute('data-catchy-history') === 'false';
    const isModalOrOffcanvas = trigger && typeof trigger.hasAttribute === 'function' && (trigger.hasAttribute('data-catchy-modal') || trigger.hasAttribute('data-catchy-offcanvas'));

    if (shouldUpdateHistory && !hasHistoryAttr && !isModalOrOffcanvas) {
        window.history.pushState({ catchy: true, url: finalUrl }, '', finalUrl);
    }
}
