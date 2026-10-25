import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';
import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePage, installPlugins } from './setup';

/**
 * Inertia renders an outer wrapper whose innerHTML is produced by calling
 * render() again, synchronously, for the page app. renderToString is async, so
 * the nested call returns a placeholder, replaced once the page HTML is ready.
 */
function twoPassRenderer() {
    const placeholder = '<!--inertia-ssr-body-->';
    let outerStarted = false;
    let inner = Promise.resolve('');
    return (app) => {
        if (!outerStarted) {
            outerStarted = true;
            return renderToString(app).then(async (outer) => outer.replace(placeholder, await inner));
        }
        inner = renderToString(app);
        return placeholder;
    };
}

// Server-side rendering: crawlers and slow connections receive full HTML.
createServer((page) =>
    createInertiaApp({
        page,
        render: twoPassRenderer(),
        title: (title) => title,
        resolve: resolvePage,
        setup({ App, props, plugin }) {
            const ziggy = { ...page.props.ziggy, location: new URL(page.props.ziggy.location) };
            return installPlugins(createSSRApp({ render: () => h(App, props) }).use(plugin), { initialPage: { props: { ziggy } } });
        },
    }),
);
