import { createInertiaApp } from '@inertiajs/svelte';
import type { ResolvedComponent } from '@inertiajs/svelte';
import 'sweetalert2/dist/sweetalert2.min.css';
import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laundry Management';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob<ResolvedComponent>('./Pages/**/*.svelte', { eager: true });
        const page = pages[`./Pages/${name}.svelte`];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return page;
    },
    progress: {
        color: '#ea580c',
    },
});
