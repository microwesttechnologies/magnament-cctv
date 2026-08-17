import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('shell', {
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1',
        theme: localStorage.getItem('theme') || 'system',

        init() {
            this.applyTheme();
            this.applySidebar();

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.theme === 'system') {
                    this.applyTheme();
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
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
            this.applySidebar();
        },

        toggleTheme() {
            this.theme = this.isDark() ? 'light' : 'dark';
            localStorage.setItem('theme', this.theme);
            this.applyTheme();
        },
    });
});

Alpine.start();
