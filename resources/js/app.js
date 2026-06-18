import '../css/app.css';

// Initialize PWA Service Worker Network Interception
import { registerSW } from 'virtual:pwa-register';
if (typeof window !== 'undefined') {
    registerSW({ immediate: true });
}

// System-wide Transpilation Guard
if (typeof window !== 'undefined') {
    window.__publicField = (obj, key, value) => {
        if (typeof Object.defineProperty === 'function') {
            Object.defineProperty(obj, key, { enumerable: true, configurable: true, writable: true, value: value });
        } else {
            obj[key] = value;
        }
        return value;
    };
}

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'GeoSurvey';

import dayjs from 'dayjs';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Global Date Formatter
        app.config.globalProperties.$formatDate = (date) => {
            if (!date) return '-';
            return dayjs(date).format('DD-MM-YYYY');
        };

        app.use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
