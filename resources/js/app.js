import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('shell', {
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1',
        mobileDrawerOpen: false,
        theme: localStorage.getItem('theme') || 'system',

        init() {
            this.applyTheme();
            this.applySidebar();

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.theme === 'system') {
                    this.applyTheme();
                }
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    this.mobileDrawerOpen = false;
                }
            });
        },

        effectiveTheme() {
            if (this.theme === 'system') {
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            return this.theme;
        },

        isDark() {
            return this.effectiveTheme() === 'dark';
        },

        applyTheme() {
            document.documentElement.classList.toggle('dark', this.isDark());
        },

        applySidebar() {
            document.documentElement.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
        },

        toggleSidebar() {
            if (window.innerWidth < 1024) {
                this.mobileDrawerOpen = !this.mobileDrawerOpen;
                return;
            }

            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
            this.applySidebar();
        },

        closeMobileDrawer() {
            this.mobileDrawerOpen = false;
        },

        toggleTheme() {
            this.theme = this.isDark() ? 'light' : 'dark';
            localStorage.setItem('theme', this.theme);
            this.applyTheme();
        },
    });

    Alpine.store('notifications', {
        items: [],
        nextId: 1,

        push({ type = 'info', message, duration = 4000 }) {
            const id = this.nextId++;
            const item = { id, type, message, duration };
            this.items.push(item);

            if (duration > 0 && type !== 'error') {
                setTimeout(() => this.dismiss(id), duration);
            } else if (type === 'error' && duration > 0) {
                setTimeout(() => this.dismiss(id), Math.max(duration, 8000));
            }
        },

        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },

        success(message, duration = 4000) {
            this.push({ type: 'success', message, duration });
        },

        error(message, duration = 8000) {
            this.push({ type: 'error', message, duration });
        },

        warning(message, duration = 5000) {
            this.push({ type: 'warning', message, duration });
        },

        info(message, duration = 5000) {
            this.push({ type: 'info', message, duration });
        },
    });

    Alpine.data('pwaInstallBanner', () => ({
        visible: false,
        promptEvent: null,
        iosHint: false,

        init() {
            const standalone = window.matchMedia('(display-mode: standalone)').matches
                || window.navigator.standalone === true;
            const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
            this.iosHint = isIos && !standalone && localStorage.getItem('pwaInstallDismissed') !== '1';

            if (localStorage.getItem('pwaInstallDismissed') === '1' || standalone) {
                return;
            }

            if (window.deferredPwaPrompt) {
                this.promptEvent = window.deferredPwaPrompt;
                this.visible = true;
            }

            window.addEventListener('pwa:installable', (event) => {
                this.promptEvent = event.detail;
                this.visible = true;
            });
        },

        dismiss() {
            this.visible = false;
            localStorage.setItem('pwaInstallDismissed', '1');
        },

        async install() {
            if (!this.promptEvent) {
                this.visible = false;
                return;
            }
            this.promptEvent.prompt();
            await this.promptEvent.userChoice;
            this.visible = false;
            localStorage.setItem('pwaInstallDismissed', '1');
        },
    }));

    Alpine.data('pushPermission', () => ({
        statusLabel: '',
        canEnable: true,

        init() {
            if (!('Notification' in window)) {
                this.statusLabel = 'Este navegador no admite notificaciones push.';
                this.canEnable = false;
                return;
            }
            if (Notification.permission === 'granted') {
                this.statusLabel = 'Las notificaciones están activas.';
                this.canEnable = false;
                return;
            }
            if (Notification.permission === 'denied') {
                this.statusLabel = 'Las notificaciones están bloqueadas en el navegador.';
                this.canEnable = false;
            }
        },

        async enable() {
            if (typeof window.requestTechnicianPush !== 'function') {
                this.statusLabel = 'No se pudo solicitar el permiso.';
                return;
            }
            const result = await window.requestTechnicianPush();
            if (result === 'granted') {
                this.statusLabel = 'Notificaciones activadas.';
                this.canEnable = false;
                return;
            }
            if (result === 'denied') {
                this.statusLabel = 'Permiso rechazado. Puedes activarlo luego en Ajustes del navegador.';
                this.canEnable = false;
                return;
            }
            this.statusLabel = 'No se activaron las notificaciones.';
        },
    }));

    Alpine.data('evidenceCapture', () => ({
        previewUrl: '',
        pngFile: null,
        uploading: false,

        async preview(event) {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }
            this.pngFile = await this.toPng(file);
            if (this.previewUrl) {
                URL.revokeObjectURL(this.previewUrl);
            }
            this.previewUrl = URL.createObjectURL(this.pngFile);
        },

        async toPng(file) {
            if (file.type === 'image/png') {
                return file;
            }

            return new Promise((resolve, reject) => {
                const image = new Image();
                image.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = image.naturalWidth || image.width;
                    canvas.height = image.naturalHeight || image.height;
                    const context = canvas.getContext('2d');
                    context.drawImage(image, 0, 0);
                    canvas.toBlob((blob) => {
                        URL.revokeObjectURL(image.src);
                        if (!blob) {
                            reject(new Error('No se pudo convertir a PNG'));
                            return;
                        }
                        resolve(new File([blob], 'evidencia.png', { type: 'image/png' }));
                    }, 'image/png');
                };
                image.onerror = () => reject(new Error('Imagen no válida'));
                image.src = URL.createObjectURL(file);
            });
        },

        prepareSubmit(event) {
            if (!this.pngFile) {
                return;
            }
            const input = event.target.querySelector('input[type="file"]');
            const transfer = new DataTransfer();
            transfer.items.add(this.pngFile);
            input.files = transfer.files;
            this.uploading = true;
        },
    }));

    Alpine.data('orderCompletionFlow', (config) => ({
        config,
        result: config.oldResult || '',
        observation: config.oldObservation || '',
        evidences: [...(config.evidences || [])],
        uploading: false,
        uploadSuccess: false,
        uploadError: '',
        evidenceError: '',
        observationError: '',
        submitting: false,

        get showFinalizeCard() {
            return this.result !== '';
        },

        get resultLabel() {
            return {
                resuelta: 'Resuelta',
                no_resuelta: 'No resuelta',
                cancelada: 'Cancelada',
            }[this.result] || '';
        },

        get submitLabel() {
            return {
                resuelta: 'Marcar como resuelta',
                no_resuelta: 'Marcar como no resuelta',
                cancelada: 'Confirmar cancelación',
            }[this.result] || 'Finalizar orden';
        },

        async toPng(file) {
            if (file.type === 'image/png') {
                return file;
            }

            return new Promise((resolve, reject) => {
                const image = new Image();
                image.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = image.naturalWidth || image.width;
                    canvas.height = image.naturalHeight || image.height;
                    const context = canvas.getContext('2d');
                    context.drawImage(image, 0, 0);
                    canvas.toBlob((blob) => {
                        URL.revokeObjectURL(image.src);
                        if (!blob) {
                            reject(new Error('No se pudo convertir a PNG'));
                            return;
                        }
                        resolve(new File([blob], 'evidencia.png', { type: 'image/png' }));
                    }, 'image/png');
                };
                image.onerror = () => reject(new Error('Imagen no válida'));
                image.src = URL.createObjectURL(file);
            });
        },

        async addPhoto(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (!file) {
                return;
            }

            this.uploading = true;
            this.uploadSuccess = false;
            this.uploadError = '';
            this.evidenceError = '';

            try {
                const pngFile = await this.toPng(file);
                const body = new FormData();
                body.append('evidence', pngFile);
                body.append('_token', this.config.csrf);

                const response = await fetch(this.config.uploadUrl, {
                    method: 'POST',
                    body,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const validationMessage = payload.errors?.evidence?.[0];
                    throw new Error(validationMessage || payload.message || 'No se pudo subir la evidencia.');
                }

                this.evidences.push(payload.evidence);
                this.uploadSuccess = true;
                window.setTimeout(() => {
                    this.uploadSuccess = false;
                }, 2500);
            } catch (error) {
                this.uploadError = error instanceof Error ? error.message : 'No se pudo subir la evidencia.';
            } finally {
                this.uploading = false;
            }
        },

        async removeEvidence(id) {
            this.evidenceError = '';
            const url = this.config.deleteUrlTemplate.replace('__EVIDENCE__', String(id));

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.config.csrf,
                    },
                });

                if (!response.ok) {
                    throw new Error('No se pudo eliminar la evidencia.');
                }

                this.evidences = this.evidences.filter((item) => item.id !== id);
            } catch (error) {
                this.evidenceError = error instanceof Error ? error.message : 'No se pudo eliminar la evidencia.';
            }
        },

        submitFinalize(event) {
            this.evidenceError = '';
            this.observationError = '';

            if (!this.result) {
                event.preventDefault();
                return;
            }

            if (this.evidences.length === 0) {
                event.preventDefault();
                this.evidenceError = 'Debes agregar al menos una evidencia fotográfica.';
                return;
            }

            if (!this.observation.trim()) {
                event.preventDefault();
                this.observationError = 'La observación es obligatoria.';
                return;
            }

            this.submitting = true;
        },
    }));
});

Alpine.start();

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function navigationDirection(fromHref, toHref) {
    try {
        const from = new URL(fromHref, window.location.origin).pathname.split('/').filter(Boolean);
        const to = new URL(toHref, window.location.origin).pathname.split('/').filter(Boolean);
        if (to.length > from.length) {
            return 'forward';
        }
        if (to.length < from.length) {
            return 'back';
        }
        return 'cross';
    } catch (error) {
        return 'cross';
    }
}

function shouldInterceptNavigation(anchor, event) {
    if (!anchor || event.defaultPrevented || event.button !== 0) {
        return false;
    }
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return false;
    }
    if (anchor.target && anchor.target !== '_self') {
        return false;
    }
    if (anchor.hasAttribute('download') || anchor.hasAttribute('data-no-motion')) {
        return false;
    }
    const href = anchor.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) {
        return false;
    }
    let url;
    try {
        url = new URL(anchor.href, window.location.href);
    } catch (error) {
        return false;
    }
    if (url.origin !== window.location.origin) {
        return false;
    }
    if (url.pathname === window.location.pathname && url.search === window.location.search) {
        return false;
    }
    if (url.pathname.includes('/pdf') || url.pathname.endsWith('.pdf')) {
        return false;
    }
    if (anchor.closest('form')) {
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-flash-success]').forEach((el) => {
        Alpine.store('notifications').success(el.getAttribute('data-flash-success'));
        el.remove();
    });
    document.querySelectorAll('[data-flash-error]').forEach((el) => {
        Alpine.store('notifications').error(el.getAttribute('data-flash-error'));
        el.remove();
    });

    const main = document.getElementById('main-content');
    if (main && !prefersReducedMotion()) {
        const direction = sessionStorage.getItem('magnament-nav-dir') || 'forward';
        sessionStorage.removeItem('magnament-nav-dir');
        main.classList.add(direction === 'back' ? 'motion-page-enter-back' : 'motion-page-enter');
        main.addEventListener('animationend', () => {
            main.classList.remove('motion-page-enter', 'motion-page-enter-back');
        }, { once: true });
    }
});

document.addEventListener('click', (event) => {
    const anchor = event.target.closest('a[href]');
    if (!shouldInterceptNavigation(anchor, event) || prefersReducedMotion()) {
        return;
    }

    const main = document.getElementById('main-content');
    if (!main || main.classList.contains('motion-page-exit')) {
        return;
    }

    event.preventDefault();
    const href = anchor.href;
    sessionStorage.setItem('magnament-nav-dir', navigationDirection(window.location.href, href));
    main.classList.add('motion-page-exit');

    window.setTimeout(() => {
        window.location.href = href;
    }, 90);
});

window.addEventListener('pageshow', (event) => {
    const main = document.getElementById('main-content');
    if (!main) {
        return;
    }
    main.classList.remove('motion-page-exit');
    if (event.persisted && !prefersReducedMotion()) {
        main.classList.add('motion-page-enter');
    }
});
