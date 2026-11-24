import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePage, installPlugins } from './setup';
import { startReveal } from './reveal';

createInertiaApp({
    title: (title) => title,
    resolve: resolvePage,
    setup({ el, App, props, plugin }) {
        installPlugins(createApp({ render: () => h(App, props) }).use(plugin), props).mount(el);
        startReveal();
    },
    progress: { color: '#22D3EE', showSpinner: false },
});
