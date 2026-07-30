const CACHE_NAME = 'crabai-shell-v5';
const scopeUrl = new URL(self.registration.scope);
const scopePath = scopeUrl.pathname;
const scopedUrl = (path) => new URL(path, self.registration.scope).toString();
const SHELL = ['./', 'offline', 'manifest.webmanifest', 'favicon.png', 'pwa-icon-192.png', 'pwa-icon-512.png'].map(scopedUrl);

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))));
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  const appPath = url.pathname.startsWith(scopePath) ? `/${url.pathname.slice(scopePath.length)}` : url.pathname;
  if (event.request.method !== 'GET' || appPath.startsWith('/recognition') || appPath.startsWith('/dashboard') || appPath.startsWith('/admin')) return;
  event.respondWith(fetch(event.request).then((response) => {
    if (response.ok && ['style', 'script', 'image', 'font'].includes(event.request.destination)) {
      const copy = response.clone();
      caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
    }
    return response;
  }).catch(() => caches.match(event.request).then((cached) => cached || caches.match(scopedUrl('offline')))));
});
