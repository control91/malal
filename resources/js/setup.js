import { route as ziggyRoute } from 'ziggy-js';
import { router } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';

const pages = import.meta.glob('./Pages/**/*.vue');

/** Resolves a page and assigns its default layout from its folder. */
export async function resolvePage(name) {
    const loader = pages[`./Pages/${name}.vue`];
    if (!loader) throw new Error(`Page introuvable : ${name}`);
    const page = await loader();
    const component = page.default;
    if (component.layout === undefined) {
        // Back-office layouts are loaded on demand: public visitors never download them.
        if (name.startsWith('Admin/')) component.layout = (await import('@/Layouts/AdminLayout.vue')).default;
        else if (name.startsWith('Auth/')) component.layout = (await import('@/Layouts/AuthLayout.vue')).default;
        else component.layout = SiteLayout;
    }
    return page;
}

/**
 * Installs Ziggy's `route()` for templates and <script setup>. The route list
 * comes from each page's props (anonymous visitors only get public routes), so
 * it is refreshed on every Inertia navigation (e.g. right after logging in).
 */
export function installPlugins(app, props) {
    let config = props.initialPage.props.ziggy;
    if (typeof window !== 'undefined') {
        router.on('navigate', (event) => { config = event.detail.page.props.ziggy ?? config; });
    }
    const route = (name, params, absolute) => ziggyRoute(name, params, absolute, config);
    globalThis.route = route;
    app.config.globalProperties.route = route;
    app.provide('route', route);
    return app;
}
