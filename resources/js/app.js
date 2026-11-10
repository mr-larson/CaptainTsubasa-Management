import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import GameBanknote from './Components/GameBanknote.vue';
import TeamLogoImage from './Components/TeamLogoImage.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Golden Eleven';

createInertiaApp({
    title: (title) => title && title !== appName ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .component('GameBanknote', GameBanknote)
            .component('TeamLogoImage', TeamLogoImage)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },

});
