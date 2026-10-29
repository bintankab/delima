import './bootstrap';
import '../css/app.css';
import 'primeicons/primeicons.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, Fragment, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import { delimaTheme } from './theme';
import GlobalLoadingOverlay from './Components/GlobalLoadingOverlay.vue';
import GlobalValidationToast from './Components/GlobalValidationToast.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Delima';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(Fragment, [h(App, props), h(GlobalLoadingOverlay), h(GlobalValidationToast)]) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: delimaTheme,
                    options: {
                        darkModeSelector: '.app-dark',
                        cssLayer: {
                            name: 'primevue',
                            order: 'tailwind-base, primevue, tailwind-utilities',
                        },
                    },
                },
            })
            .use(ToastService)
            .use(ConfirmationService)
            .mount(el);
    },
    progress: {
        color: '#4f46e5',
    },
});
