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
