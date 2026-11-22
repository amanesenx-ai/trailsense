/* TrailSense shared map helpers (AC1.12, ADR-016). Defined before DOMContentLoaded so hike.js can use them. */
window.TS = window.TS || {};
/** Standard / Topographic base layers + waymarked hiking routes overlay. Returns the default base layer. */
window.TS.layers = function (map) {
    const opts = (extra) => Object.assign({ referrerPolicy: 'strict-origin-when-cross-origin', crossOrigin: false }, extra);
    const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', opts({ maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors' }));
    const topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', opts({ maxZoom: 17, subdomains: 'abc',
        attribution: 'Map: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (CC-BY-SA), data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' }));
    const hiking = L.tileLayer('https://tile.waymarkedtrails.org/hiking/{z}/{x}/{y}.png', opts({ maxZoom: 18, opacity: 0.8,
        attribution: 'Hiking routes: &copy; <a href="https://waymarkedtrails.org">Waymarked Trails</a> (CC-BY-SA)' }));
    osm.addTo(map);
    L.control.layers({ 'Standard': osm, 'Topographic': topo }, { 'Waymarked hiking routes': hiking }, { position: 'topleft', collapsed: true }).addTo(map);
    return osm;
};

// TrailSense — progressive enhancement only; every flow works without JavaScript.
document.addEventListener('DOMContentLoaded', () => {
    // F9 AC9.3: copy briefing (textarea stays selectable without JS)
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.querySelector(button.dataset.copy);
            const status = document.querySelector('.copy-status');
            if (!target) return;
            try {
                await navigator.clipboard.writeText(target.value);
            } catch (e) {
                target.select();
                document.execCommand('copy');
            }
            if (status) status.textContent = 'Briefing copied. Paste it in your group chat.';
        });
    });

    // AC2.1: duration follows the chosen trail's estimate unless the user typed one
    const trailSelect = document.querySelector('[data-duration-source]');
    const duration = document.getElementById('duration_min');
    if (trailSelect && duration) {
        let touched = duration.value !== '';
        duration.addEventListener('input', () => { touched = true; });
        trailSelect.addEventListener('change', () => {
            const opt = trailSelect.selectedOptions[0];
            if (!touched && opt && opt.dataset.duration) duration.value = opt.dataset.duration;
        });
    }

    // F1.2 interactive map (Leaflet + OSM). Without Leaflet the SVG fallback inside stays visible (AC1.8).
    document.querySelectorAll('[data-map]').forEach((box) => {
        if (!window.L) return;
        let data;
        try { data = JSON.parse(box.dataset.map); } catch (e) { return; }
        const fallback = box.innerHTML;
        box.innerHTML = '';
        box.classList.add('map-live');
        const map = L.map(box, { scrollWheelZoom: false, tap: true });
        const tiles = TS.layers(map);
        let tileErrors = 0, tileOk = false;
        tiles.on('tileload', () => { tileOk = true; });
        tiles.on('tileerror', () => {           // tiles blocked/offline → show the drawing again
            if (!tileOk && ++tileErrors === 4) { map.remove(); box.classList.remove('map-live'); box.innerHTML = fallback; }
        });
        const route = data.route;
        L.polyline(route, { color: '#0b2219', weight: 8, opacity: 0.45 }).addTo(map);
        const line = L.polyline(route, { color: '#ff6a00', weight: 4.5 }).addTo(map);
        const start = route[0], end = route[route.length - 1];
        const pin = (cls, label) => L.divIcon({ className: 'pin ' + cls, html: '<span>' + label + '</span>', iconSize: null });
        if (data.loop) {
            L.marker(start, { icon: pin('pin-start', 'Start / finish'), title: 'Start and finish', keyboard: true }).addTo(map);
        } else {
            L.marker(end, { icon: pin('pin-end', 'End'), title: 'End', keyboard: true }).addTo(map);
            L.marker(start, { icon: pin('pin-start', 'Start'), title: 'Start', keyboard: true }).addTo(map);
        }
        map.fitBounds(line.getBounds(), { padding: [24, 24] });
    });

    // AC1.19 share a trail or a group hike: native share sheet, else copy the link
    document.querySelectorAll('[data-share]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const link = new URL(btn.dataset.url, location.href).toString();
            const out = document.querySelector('[data-share-status]');
            try {
                if (navigator.share) { await navigator.share({ title: btn.dataset.title, url: link }); return; }
                await navigator.clipboard.writeText(link);
                if (out) out.textContent = 'Link copied: ' + link;
            } catch (e) { if (out) out.textContent = link; }
        });
    });

    // AC8.6 forms show a busy state while submitting (prevents double submits)
    document.querySelectorAll('form[method="post"]').forEach((form) => {
        form.addEventListener('submit', () => {
            const b = form.querySelector('button[type="submit"]');
            if (b && !b.disabled) { b.dataset.label = b.textContent; b.setAttribute('aria-busy', 'true'); setTimeout(() => { b.disabled = true; }, 0); b.textContent = 'Working…'; }
        });
    });

    // ADR-020: register the offline worker; signing out removes saved pages (AC13.15)
    if ('serviceWorker' in navigator) {
        if (document.body.classList.contains('signed-out')) {
            navigator.serviceWorker.getRegistration().then((r) => r && r.active && r.active.postMessage('clear-pages')).catch(() => {});
            try { Object.keys(localStorage).filter((k) => k.startsWith('ts-offline-') || k === 'ts-pending-hike').forEach((k) => localStorage.removeItem(k)); } catch (e) { /* ignore */ }
        } else {
            navigator.serviceWorker.register('sw.js').catch(() => {});
        }
    }
    // AC13.14 a hike finished without signal waits on the phone
    try {
        const pending = JSON.parse(localStorage.getItem('ts-pending-hike') || 'null');
        const slot = document.querySelector('main');
        if (pending && navigator.onLine && slot && !document.body.classList.contains('hike-mode') && !location.search.includes('r=publish')) {
            const n = document.createElement('div');
            n.className = 'flash flash-info'; n.setAttribute('role', 'status');
            n.innerHTML = 'You finished <strong></strong> offline (' + pending.km + ' km, ' + pending.minutes + ' min). <a href="#">Publish it now</a>';
            n.querySelector('strong').textContent = pending.name;
            n.querySelector('a').href = pending.url;
            n.querySelector('a').addEventListener('click', () => { try { localStorage.removeItem('ts-pending-hike'); } catch (e) { /* ignore */ } });
            slot.prepend(n);
        }
    } catch (e) { /* ignore */ }

    // Dropdown menus (account menu): close on outside click or Escape
    document.querySelectorAll('[data-menu]').forEach((m) => {
        document.addEventListener('click', (e) => { if (m.open && !m.contains(e.target)) m.open = false; });
        m.addEventListener('keydown', (e) => { if (e.key === 'Escape') { m.open = false; m.querySelector('summary').focus(); } });
    });
    if (location.hash === '#password') { const pw = document.getElementById('password'); if (pw) pw.open = true; }

    // AC4.12 checklist ticks are kept only on this device (light per-viewer convenience)
    document.querySelectorAll('[data-checklist]').forEach((list) => {
        const key = 'ts-check-' + list.dataset.checklist;
        let ticked = [];
        try { ticked = JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) { ticked = []; }
        list.querySelectorAll('input[type=checkbox]').forEach((box) => {
            box.checked = ticked.includes(box.value);
            box.addEventListener('change', () => {
                const now = [...list.querySelectorAll('input:checked')].map((b) => b.value);
                try { localStorage.setItem(key, JSON.stringify(now)); } catch (e) { /* storage unavailable */ }
            });
        });
    });

    // Photos that fail to load are hidden (AC1.11)
    document.querySelectorAll('img[data-fallback]').forEach((img) => {
        img.addEventListener('error', () => { const fig = img.closest('figure'); if (fig) fig.hidden = true; });
    });

    // Confirm destructive actions
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (ev) => {
            if (!window.confirm(form.dataset.confirm)) ev.preventDefault();
        });
    });
});

/* Landing section menu: highlight the section being read and keep its link visible (22 Nov). */
(function () {
  var nav = document.querySelector('[data-lp-nav]');
  if (!nav || !('IntersectionObserver' in window)) return;
  var links = {};
  nav.querySelectorAll('a').forEach(function (a) { links[a.getAttribute('href').slice(1)] = a; });
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (!en.isIntersecting) return;
      Object.keys(links).forEach(function (k) { links[k].removeAttribute('aria-current'); });
      var a = links[en.target.id];
      if (a) { a.setAttribute('aria-current', 'true'); nav.scrollTo({ left: a.offsetLeft - 16, behavior: 'smooth' }); }
    });
  }, { rootMargin: '-35% 0px -60% 0px' });
  Object.keys(links).forEach(function (id) { var s = document.getElementById(id); if (s) io.observe(s); });
})();
