const CACHE_NAME = 'budget-guy-v1';

// Static assets to cache on install
const PRECACHE_URLS = [
    '/',
    '/offline',
    '/apple-touch-icon.png',
    '/icon-192.png',
    '/icon-512.png',
];

// Install: cache core assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_URLS);
        })
    );
    self.skipWaiting();
});

// Activate: clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Fetch: network-first strategy
// Try the network, fall back to cache, then offline page
self.addEventListener('fetch', (event) => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    // Skip non-http requests
    if (!event.request.url.startsWith('http')) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Cache successful responses for static assets
                if (response.ok && shouldCache(event.request)) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // Network failed — try cache
                return caches.match(event.request).then((cached) => {
                    if (cached) return cached;

                    // For navigation requests, show offline page
                    if (event.request.mode === 'navigate') {
                        return caches.match('/offline');
                    }
                });
            })
    );
});

function shouldCache(request) {
    const url = new URL(request.url);
    // Cache static assets (images, fonts, CSS, JS)
    return (
        url.pathname.startsWith('/build/') ||
        url.pathname.match(/\.(png|jpg|svg|ico|woff2?|css|js)$/)
    );
}
