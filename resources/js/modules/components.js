/**
 * Catchy — Components Module
 *
 * Modal, Offcanvas, Toast, Lazy, Progress, Upload, Error — Alpine.data() registrations.
 * Lifecycle trigger helpers for declarative actions (open/close/reset/toast/reload).
 */

import { emit, executeScriptsInContainer } from './utils.js';

/**
 * Resolves the target modal element based on the trigger or default selectors.
 *
 * @param {HTMLElement} triggerElement
 * @returns {HTMLElement|null}
 */
export function resolveModal(triggerElement) {
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
export function resolveOffcanvas(triggerElement) {
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
 * Declaratively handles opening/closing of modals and offcanvas drawers
 * based on request success/error state.
 *
 * @param {HTMLElement} trigger
 * @param {string} type ('success' | 'error')
 */
export function handleLifecycleTriggers(trigger, type) {
    if (!trigger || typeof trigger.getAttribute !== 'function') return;

    // Parse shorthand: data-catchy-on-{type}="action:component:id"
    const shorthand = trigger.getAttribute(`data-catchy-on-${type}`);
    if (shorthand) {
        parseShorthandAction(shorthand, trigger, type);
    }

    // Legacy verbose attributes
    const openModal = trigger.getAttribute(`data-catchy-${type}-open-modal`);
    if (openModal) {
        const m = document.getElementById(openModal);
        if (m) emit('modal-open', {}, m);
    }

    const closeModal = trigger.getAttribute(`data-catchy-${type}-close-modal`);
    if (closeModal) {
        const m = document.getElementById(closeModal);
        if (m) emit('modal-close', {}, m);
    }

    const openOffcanvas = trigger.getAttribute(`data-catchy-${type}-open-offcanvas`);
    if (openOffcanvas) {
        const oc = document.getElementById(openOffcanvas);
        if (oc) emit('offcanvas-open', {}, oc);
    }

    const closeOffcanvas = trigger.getAttribute(`data-catchy-${type}-close-offcanvas`);
    if (closeOffcanvas) {
        const oc = document.getElementById(closeOffcanvas);
        if (oc) emit('offcanvas-close', {}, oc);
    }

    // Auto-reset form inputs on success
    if (type === 'success' && trigger.tagName === 'FORM' && trigger.hasAttribute('data-catchy-success-reset')) {
        trigger.reset();
    }

    // Trigger dynamic toasts
    const toastMsg = trigger.getAttribute(`data-catchy-${type}-toast`);
    if (toastMsg) {
        emit('flash', { message: toastMsg, type: type });
    }

    // Trigger dynamic lazy reloading
    const reloadId = trigger.getAttribute(`data-catchy-${type}-reload`);
    if (reloadId) {
        emit('lazy-reload', { id: reloadId });
    }
}

/**
 * Parse a shorthand action string like "close:modal:id" or "reset" or "toast:message".
 *
 * @param {string} actionStr
 * @param {HTMLElement} trigger
 * @param {string} type
 */
export function parseShorthandAction(actionStr, trigger, type) {
    const actions = actionStr.split(';').map(a => a.trim()).filter(Boolean);

    actions.forEach(action => {
        const parts = action.split(':');
        const verb = parts[0];

        switch (verb) {
            case 'open':
            case 'close': {
                const component = parts[1]; // 'modal' or 'offcanvas'
                const id = parts[2];
                if (component && id) {
                    const el = document.getElementById(id);
                    if (el) emit(`${component}-${verb}`, {}, el);
                }
                break;
            }
            case 'reset': {
                const id = parts[1];
                if (id) {
                    const el = document.getElementById(id);
                    if (el && el.tagName === 'FORM') el.reset();
                } else if (trigger && trigger.tagName === 'FORM') {
                    trigger.reset();
                }
                break;
            }
            case 'toast': {
                const message = parts.slice(1).join(':');
                if (message) emit('flash', { message, type });
                break;
            }
            case 'reload': {
                const id = parts[1];
                if (id) emit('lazy-reload', { id });
                break;
            }
            case 'click': {
                const id = parts[1];
                if (id) {
                    const el = document.getElementById(id);
                    if (el) el.click();
                }
                break;
            }
            case 'submit': {
                const id = parts[1];
                if (id) {
                    const el = document.getElementById(id);
                    if (el && el.tagName === 'FORM') {
                        el.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    }
                }
                break;
            }
            case 'toggle':
            case 'add':
            case 'remove': {
                const className = parts[1];
                const id = parts[2];
                if (className && id) {
                    const el = document.getElementById(id);
                    if (el) {
                        if (verb === 'toggle') el.classList.toggle(className);
                        else if (verb === 'add') el.classList.add(className);
                        else if (verb === 'remove') el.classList.remove(className);
                    }
                }
                break;
            }
            case 'copy': {
                const sourceId = parts[1];
                const targetId = parts[2];
                if (sourceId && targetId) {
                    const src = document.getElementById(sourceId);
                    const dest = document.getElementById(targetId);
                    if (src && dest) {
                        if ('value' in src && 'value' in dest) {
                            dest.value = src.value;
                            dest.dispatchEvent(new Event('input', { bubbles: true }));
                            dest.dispatchEvent(new Event('change', { bubbles: true }));
                        } else {
                            dest.innerHTML = src.innerHTML;
                        }
                    }
                }
                break;
            }
        }
    });
}

/**
 * Register all Alpine.data() components for Catchy UI elements.
 *
 * @param {Object} Alpine
 * @param {Object} config
 */
export function registerAlpineComponents(Alpine, config) {
    // Modal component
    Alpine.data('catchyModal', (params = {}) => ({
        isOpen: false,
        title: params.title || '',
        content: '',
        open(content = '', title = '') {
            if (content) this.content = content;
            if (title) this.title = title;
            this.isOpen = true;
            document.body.classList.add('overflow-hidden');
            this.$dispatch('catchy:modal-opened');
        },
        close() {
            this.isOpen = false;
            document.body.classList.remove('overflow-hidden');
            this.$dispatch('catchy:modal-closed');
            setTimeout(() => { if (!this.isOpen) this.content = ''; }, 300);
        }
    }));

    // Offcanvas component
    Alpine.data('catchyOffcanvas', (params = {}) => ({
        isOpen: false,
        title: params.title || '',
        content: '',
        open(content = '', title = '') {
            if (content) this.content = content;
            if (title) this.title = title;
            this.isOpen = true;
            document.body.classList.add('overflow-hidden');
            this.$dispatch('catchy:offcanvas-opened');
        },
        close() {
            this.isOpen = false;
            document.body.classList.remove('overflow-hidden');
            this.$dispatch('catchy:offcanvas-closed');
            setTimeout(() => { if (!this.isOpen) this.content = ''; }, 300);
        }
    }));

    // Toast component
    let toastCounter = 0;
    Alpine.data('catchyToast', (params = {}) => ({
        toasts: [],
        duration: params.duration || 4000,
        add(message, type = 'success') {
            const now = Date.now();
            // Prevent duplicate toasts within a short timeframe (1 second)
            const isDuplicate = this.toasts.some(t => t.message === message && t.type === type && (now - t.timestamp < 1000));
            if (isDuplicate) return;

            const id = ++toastCounter;
            this.toasts.push({ id, message, type, timestamp: now, timer: null });
            this.$nextTick(() => {
                const toast = this.toasts.find(t => t.id === id);
                if (toast) {
                    toast.timer = setTimeout(() => this.remove(id), this.duration);
                }
            });
        },
        remove(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
                clearTimeout(this.toasts[index].timer);
                this.toasts.splice(index, 1);
            }
        }
    }));

    // Lazy load component
    Alpine.data('catchyLazy', (params = {}) => ({
        loaded: false,
        error: false,
        reload() {
            this.loaded = false;
            this.error = false;
            this.load();
        },
        load() {
            if (this.loaded) return;

            emit('start', { trigger: this.$el });

            fetch(params.src, {
                headers: { 'X-Catchy-SPA': 'true' }
            })
            .then(response => {
                if (!response.ok) throw new Error('Lazy load failed');
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const fragment = doc.getElementById(config.containerId) || doc.body;

                this.$el.innerHTML = fragment.innerHTML;
                this.loaded = true;
                // Re-execute scripts within newly added content
                executeScriptsInContainer(this.$el);

                emit('end', { trigger: this.$el });
            })
            .catch(err => {
                console.error(err);
                this.error = true;
                emit('error', { error: err, trigger: this.$el });
            });
        },
        init() {
            if (params.trigger === 'intersect') {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.load();
                            observer.disconnect();
                        }
                    });
                }, { threshold: 0.1 });
                observer.observe(this.$el);
            } else {
                this.load();
            }
        }
    }));

    // Error indicator component
    Alpine.data('catchyError', (params = {}) => ({
        error: '',
        normalizeKey(key) {
            return key.replace(/\[\]/g, '').replace(/\[/g, '.').replace(/\]/g, '');
        },
        handleErrors(errors) {
            const target = this.normalizeKey(params.field || '');
            if (errors && errors[target]) {
                this.error = Array.isArray(errors[target]) ? errors[target][0] : errors[target];
            } else {
                this.error = '';
            }
        }
    }));

    // Progress bar component
    Alpine.data('catchyProgress', (params = {}) => ({
        show: false,
        progress: 0,
        init() {
            const forId = params.for || '';
            let target = null;

            if (forId) {
                target = document.getElementById(forId);
            } else {
                target = this.$el.closest('form') || window;
            }

            if (!target) return;

            const handleStart = (e) => {
                if (target === window && e.detail && e.detail.trigger && e.detail.trigger.tagName === 'FORM') {
                    return;
                }
                this.show = true;
                this.progress = 0;
            };

            const handleProgress = (e) => {
                if (target !== window && e.detail && e.detail.trigger !== target) {
                    return;
                }
                this.show = true;
                this.progress = e.detail.percent;
            };

            const handleEnd = () => {
                this.progress = 100;
                setTimeout(() => {
                    this.show = false;
                    setTimeout(() => { this.progress = 0; }, 300);
                }, 500);
            };

            const handleError = () => {
                setTimeout(() => {
                    this.show = false;
                    setTimeout(() => { this.progress = 0; }, 300);
                }, 500);
            };

            ['catchy-start', 'catchy:start'].forEach(e => target.addEventListener(e, handleStart));
            ['catchy-progress', 'catchy:progress'].forEach(e => target.addEventListener(e, handleProgress));
            ['catchy-end', 'catchy:end'].forEach(e => target.addEventListener(e, handleEnd));
            ['catchy-error', 'catchy:error'].forEach(e => target.addEventListener(e, handleError));
        }
    }));

    // Upload component
    Alpine.data('catchyUpload', (params = {}) => ({
        dragover: false,
        files: [],
        updating: false,
        error: '',
        addFiles(fileList) {
            if (this.updating) return;
            this.error = '';
            const newFiles = Array.from(fileList).map(file => {
                if (file.type.startsWith('image/')) {
                    file.previewUrl = URL.createObjectURL(file);
                }
                return file;
            });
            if (params.multiple) {
                this.files = [...this.files, ...newFiles];
            } else {
                this.files.forEach(file => {
                    if (file.previewUrl) URL.revokeObjectURL(file.previewUrl);
                });
                this.files = newFiles.slice(0, 1);
            }
            this.updateInput();
        },
        removeFile(index) {
            const file = this.files[index];
            if (file && file.previewUrl) URL.revokeObjectURL(file.previewUrl);
            this.files.splice(index, 1);
            this.updateInput();
        },
        updateInput() {
            this.updating = true;
            try {
                const dt = new DataTransfer();
                this.files.forEach(file => dt.items.add(file));
                this.$refs.fileInput.files = dt.files;
                this.$refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            } finally {
                this.updating = false;
            }
        },
        getFileSize(size) {
            if (size < 1024) return size + ' B';
            if (size < 1048576) return (size / 1024).toFixed(1) + ' KB';
            return (size / 1048576).toFixed(1) + ' MB';
        },
        isImage(file) { return file.type.startsWith('image/'); },
        getPreviewUrl(file) { return file.previewUrl || ''; },
        handleValidationErrors(event) {
            const key = (params.name || '').replace(/\[\]/g, '').replace(/\[/g, '.').replace(/\]/g, '');
            if (event.detail && event.detail[key]) {
                this.error = event.detail[key][0];
            }
        },
        destroy() {
            this.files.forEach(file => {
                if (file.previewUrl) URL.revokeObjectURL(file.previewUrl);
            });
        }
    }));
}
