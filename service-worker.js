const CACHE_NAME = 'syncro-pms-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/login',
  '/manifest.json'
];

// Install Event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(ASSETS_TO_CACHE);
      })
  );
  self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', event => {
  // Only cache GET requests, ignore SSE/API/POST
  if (event.request.method !== 'GET' || event.request.url.includes('/api/') || event.request.url.includes('/notifications/stream')) {
    return;
  }
  
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        return response || fetch(event.request).catch(() => {
            // Offline fallback
            if (event.request.mode === 'navigate') {
                return caches.match('/login');
            }
        });
      })
  );
});
