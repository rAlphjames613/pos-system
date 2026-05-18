/**
 * Pusher real-time integration
 * Keys must be loaded from server-side (PHP) — never hardcode here.
 * Usage: include this file after Pusher SDK and passing PUSHER_KEY, PUSHER_CLUSTER globals.
 */

let pusherInstance = null;

function initPusher(key, cluster) {
    if (!key || key === 'your_key') return;
    pusherInstance = new Pusher(key, { cluster });
    return pusherInstance;
}

function subscribeToChannel(channelName) {
    if (!pusherInstance) return null;
    return pusherInstance.subscribe(channelName);
}
