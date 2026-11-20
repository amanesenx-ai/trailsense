/* TrailSense service worker (ADR-020): offline trail GPS.
   - ts-shell: app scripts/styles and Leaflet (cache first, refreshed in the background)
   - ts-pages: hike pages the user chose to download (network first, cache when offline)
   - ts-tiles: OpenStreetMap tiles saved for those routes (cache first; never fetched in bulk here) */
'use strict';
const SHELL = 'ts-shell-v1', PAGES = 'ts-pages', TILES = 'ts-tiles';
const LEAFLET = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(SHELL).then((c) => Promise.all(LEAFLET.map((u) => c.add(new Request(u, { mode: 'cors' })).catch(() => null)))));
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k.startsWith('ts-shell-') && k !== SHELL).map((k) => caches.delete(k))))
        .then(() => self.clients.claim()));
});

self.addEventListener('message', (e) => {
    if (e.data === 'clear-pages') caches.delete(PAGES);            // AC13.15 on sign-out
});

const isHikePage = (url) => url.origin === self.location.origin && url.searchParams.get('r') === 'hike';

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    if (url.hostname === 'tile.openstreetmap.org') {                // saved tiles first, otherwise the network
        e.respondWith(caches.match(req.url, { cacheName: TILES }).then((hit) => hit || fetch(req)));
        return;
    }
    if (LEAFLET.includes(req.url) || (url.origin === self.location.origin && url.pathname.includes('/assets/'))) {
        e.respondWith(caches.open(SHELL).then(async (c) => {
            const hit = await c.match(req, { ignoreSearch: true });
            const net = fetch(req).then((res) => { if (res.ok) c.put(req, res.clone()); return res; }).catch(() => null);
            return hit || (await net) || Response.error();
        }));
        return;
    }
    if (isHikePage(url)) {                                           // network first; downloaded copy when offline
        e.respondWith(fetch(req).catch(() => caches.match(req.url, { cacheName: PAGES })
            .then((hit) => hit || new Response('<h1>Offline</h1><p>This trail was not downloaded for offline use.</p>',
                { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }))));
    }
});
