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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-flash-success]').forEach((el) => {
        Alpine.store('notifications').success(el.getAttribute('data-flash-success'));
        el.remove();
    });
    document.querySelectorAll('[data-flash-error]').forEach((el) => {
        Alpine.store('notifications').error(el.getAttribute('data-flash-error'));
        el.remove();
    });
});
