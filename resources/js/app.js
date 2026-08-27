import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';

import VueApexCharts from 'vue3-apexcharts';

import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faArrowRight,
    faEye,
    faEyeSlash,
    faListCheck,
    faCheckDouble,
    faPenToSquare,
    faImage,
} from '@fortawesome/free-solid-svg-icons';

library.add(faArrowRight, faEye, faEyeSlash, faListCheck, faCheckDouble, faPenToSquare, faImage);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueApexCharts)
            .component('font-awesome-icon', FontAwesomeIcon)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

import { initCookieConsent } from './cookieConsent';

initCookieConsent();

import('./vitals').then(({ initVitals }) => initVitals(router));
