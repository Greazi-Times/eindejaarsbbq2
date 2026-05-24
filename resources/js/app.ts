import { createInertiaApp } from '@inertiajs/vue3';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Home':
                return null;
        }
    },
    progress: {
        color: '#4B5563',
    },
});
