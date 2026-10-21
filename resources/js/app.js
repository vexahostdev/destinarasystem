import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// State tata letak portal (buyer / mitra / admin): sidebar inset + tema.
// Layar >= 1024px: sidebar lebar/ciut (diingat). Layar kecil: sidebar jadi laci.
Alpine.data('portalShell', () => ({
    mobileSidebarOpen: false,
    desktop: window.matchMedia('(min-width: 1024px)').matches,
    sidebarOpen: (() => {
        try { return localStorage.getItem('destinara-sidebar') !== '0'; } catch (e) { return true; }
    })(),
    dark: document.documentElement.classList.contains('dark'),

    get rail() {
        return this.desktop && !this.sidebarOpen;
    },

    init() {
        window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
            this.desktop = e.matches;
            if (e.matches) this.mobileSidebarOpen = false;
        });
        this.$watch('sidebarOpen', (v) => {
            try { localStorage.setItem('destinara-sidebar', v ? '1' : '0'); } catch (e) {}
        });
    },

    toggleSidebar() {
        if (this.desktop) this.sidebarOpen = !this.sidebarOpen;
        else this.mobileSidebarOpen = !this.mobileSidebarOpen;
    },

    setTheme(mode) {
        this.dark = mode === 'dark';
        document.documentElement.classList.toggle('dark', this.dark);
        try { localStorage.setItem('destinara-theme', mode); } catch (e) {}
    },
}));

window.Alpine = Alpine;
Livewire.start();
