import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
});

// const echo = new Echo({
//     broadcaster: 'reverb',
//       key: import.meta.env.VITE_REVERB_APP_KEY, // Your Reverb app key from .env
//       wsHost: import.meta.env.VITE_REVERB_HOST, // Your Reverb host
//       wsPort: import.meta.env.VITE_REVERB_PORT ?? 6001, // Your Reverb port
//       // wssPort: 6001, // Your Reverb port for secure connections
//       enabledTransports: ['ws'],
//       forceTLS: false,
//       // forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
// });

export default echo;
