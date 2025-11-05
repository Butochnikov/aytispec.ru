import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;

if (key) {
    const host =
        import.meta.env.VITE_REVERB_HOST && import.meta.env.VITE_REVERB_HOST !== ''
            ? import.meta.env.VITE_REVERB_HOST
            : window.location.hostname;

    const port = Number(import.meta.env.VITE_REVERB_PORT ?? 80);
    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port || 80,
        wssPort: port || 443,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
    });
} else {
    console.warn('VITE_REVERB_APP_KEY is missing. Echo will not connect to Reverb.');
}
