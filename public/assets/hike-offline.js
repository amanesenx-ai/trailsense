/* TrailSense hike mode — offline download (AC13.12–13.14, ADR-020).
   Saves this page, the scripts/CSS and the OSM tiles near the route (z13–16, 4 at a time, per the tile policy),
   and switches the map to the saved tiles when there is no signal. Called by hike.js with its helpers. */
window.HikeOffline = function (ctx) {
    'use strict';
    const { $, data, route, H, status, getMap, isStarted } = ctx;
    const offKey = 'ts-offline-' + data.trailId;
    const meta = () => { try { return JSON.parse(localStorage.getItem(offKey) || 'null'); } catch (e) { return null; } };
    const showMeta = () => {
        const m = meta();
        if (m) {
            $('offline-status').innerHTML = '<strong>✓ Ready offline</strong> — saved ' + new Date(m.at).toLocaleDateString() + ', ' + m.tiles + ' map tiles (' +
                (m.bytes / 1048576).toFixed(1) + ' MB). Directions, compass and progress work without signal; the map zooms to street level 16.';
            $('offline').textContent = 'Update offline copy';
            $('offline-preview').textContent = '✓ Ready offline · ' + m.tiles + ' map tiles';
        } else if (!isStarted()) {
            $('offline-drop').open = true;                              // before the hike, invite to download
        }
    };
    function setOffline(isOff) {
        $('offline-badge').hidden = !isOff;
        const map = getMap(); if (map) { map.setMaxZoom(isOff ? 16 : 19); if (isOff && map.getZoom() > 16) map.setZoom(16); }
        if (isOff) status(meta() ? 'No signal — using the map saved on this phone. GPS, directions and compass still work.'
            : 'No signal and this trail was not downloaded: no map tiles, but GPS, directions and compass still work.');
    }
    window.addEventListener('offline', () => setOffline(true));
    window.addEventListener('online', () => setOffline(false));
    if (!navigator.onLine) setOffline(true);
    showMeta();
    $('offline').addEventListener('click', async () => {
        if (!('serviceWorker' in navigator) || !window.caches) { $('offline-status').textContent = 'This browser cannot save pages for offline use.'; return; }
        if (!navigator.onLine) { $('offline-status').textContent = 'Connect to the internet to download this trail.'; return; }
        const btn = $('offline'); btn.disabled = true;
        try {
            await navigator.serviceWorker.register('sw.js'); await navigator.serviceWorker.ready;
            const pages = await caches.open('ts-pages'), shell = await caches.open('ts-shell-v2'), tilesC = await caches.open('ts-tiles');
            await pages.put(location.href, await fetch(location.href, { credentials: 'same-origin', cache: 'no-store' }));
            const assets = [...document.querySelectorAll('script[src], link[rel=stylesheet]')].map((el) => el.src || el.href);
            await Promise.all(assets.map((u) => fetch(u, { mode: 'cors' }).then((r) => r.ok && shell.put(u, r)).catch(() => null)));
            const list = H.tilesForRoute(route), total = list.length;
            let done = 0, bytes = 0, failed = 0;
            $('offline-progress').hidden = false;
            const next = async () => {
                while (list.length) {
                    const t = list.shift(), u = 'https://tile.openstreetmap.org/' + t.z + '/' + t.x + '/' + t.y + '.png';
                    try {
                        let r = await tilesC.match(u);
                        if (!r) { r = await fetch(u, { mode: 'cors', referrerPolicy: 'strict-origin-when-cross-origin' }); if (r.ok) await tilesC.put(u, r.clone()); else failed++; }
                        bytes += (await r.clone().blob()).size;
                    } catch (e) { failed++; }
                    done++;
                    $('offline-bar').style.width = Math.round(100 * done / total) + '%';
                    btn.textContent = 'Saving map… ' + done + '/' + total;
                }
            };
            await Promise.all([next(), next(), next(), next()]);         // 4 at a time, as the OSM tile policy asks
            $('offline-progress').hidden = true;
            if (failed === total) {                                     // page saved, map not: say it plainly
                $('offline-status').innerHTML = '<strong>Directions saved, map not.</strong> The route, turns, compass and progress work offline, but the map tiles could not be downloaded — try again with a better connection.';
                btn.textContent = 'Try the map again';
                return;
            }
            try { localStorage.setItem(offKey, JSON.stringify({ at: Date.now(), tiles: total - failed, bytes })); } catch (e) { /* storage full */ }
            showMeta();
            if (failed) $('offline-status').innerHTML += ' <span class="small">(' + failed + ' tiles could not be saved.)</span>';
        } catch (e) {
            $('offline-status').textContent = 'Download failed — check your connection and try again.';
        } finally { btn.disabled = false; if (!meta() && btn.textContent.startsWith('Saving')) btn.textContent = 'Download for offline'; }
    });

};
