import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// Pinia - estado global
import { createPinia } from 'pinia';

// FormKit - formularios dinámicos
import { plugin as formkitPlugin, defaultConfig } from '@formkit/vue';
import '@formkit/themes/genesis'; // Tema Genesis (opcional, puedes personalizar)

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Instancias
        const pinia = createPinia();

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .use(formkitPlugin, defaultConfig) // FormKit con configuración por defecto
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});