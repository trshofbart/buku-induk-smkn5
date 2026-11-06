/* Service worker sederhana untuk PWA Buku Induk SMKN 5.
   Strategi: cache-first untuk aset statis, network-first untuk halaman.
   Versi cache dinaikkan setiap kali ada perubahan aset. */
const CACHE_NAME = 'buku-induk-smkn5-v1';
const ASET_STATIS = ['/', '/manifest.json', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASET_STATIS)).catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);
  if (url.origin !== location.origin) return;
  event.respondWith(
    fetch(event.request)
      .then((res) => {
        const salinan = res.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, salinan)).catch(() => {});
        return res;
      })
      .catch(() => caches.match(event.request))
  );
});
