/* TrailSense hike mode (F13, ADR-015): live position, compass, progress, off-route alert.
   Nothing is sent to the server; the track lives in this tab (sessionStorage) until "Finish". */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const root = document.querySelector('[data-hike]');
    if (!root || !window.HikeCore) return;
    const H = window.HikeCore;
    const $ = (name) => root.querySelector('[data-el="' + name + '"]');
    const data = JSON.parse(root.dataset.hike);
    const route = data.route, cum = H.cumulative(route), key = 'ts-hike-' + data.trailId;
    const store = {
        load() { try { return JSON.parse(sessionStorage.getItem(key) || 'null'); } catch (e) { return null; } },
        save(v) { try { sessionStorage.setItem(key, JSON.stringify(v)); } catch (e) { /* private mode: keep in memory */ } },
        clear() { try { sessionStorage.removeItem(key); } catch (e) { /* ignore */ } },
    };
    let state = store.load() || { startedAt: null, track: [] };
    let pos = null, accuracy = null, heading = null, headingSource = '', follow = true, off = { count: 0, off: false };
    let watchId = null, wakeLock = null, map = null, me = null, meCircle = null, toRoute = null, walked = null, trail = null;
    const turnList = H.turns(route, cum), total = cum[cum.length - 1], profile = data.profile || [];
    const announced = {};
    let voice = false;

    // ---- map (AC13.1, AC1.12) ----
    if (window.L) {
        const box = $('map');
        box.innerHTML = '';
        map = L.map(box, { zoomControl: true, tap: true });
        TS.layers(map);
        L.polyline(route, { color: '#0b2219', weight: 9, opacity: 0.45 }).addTo(map);
        const line = L.polyline(route, { color: '#ff6a00', weight: 5 }).addTo(map);
        walked = L.polyline([], { color: '#7d8580', weight: 5, opacity: 0.95 }).addTo(map);     // part already done
        trail = L.polyline([], { color: '#2f80ed', weight: 3, dashArray: '2 6' }).addTo(map);  // where you actually walked
        turnList.forEach((t) => L.circleMarker(H.pointAt(route, cum, t.alongM), { radius: 4, color: '#fff', weight: 2, fillColor: '#0b2219', fillOpacity: 1 })
            .bindTooltip(t.dir.charAt(0).toUpperCase() + t.dir.slice(1)).addTo(map));
        const pin = (cls, label) => L.divIcon({ className: 'pin ' + cls, html: '<span>' + label + '</span>', iconSize: null });
        if (!data.loop) L.marker(route[route.length - 1], { icon: pin('pin-end', 'End') }).addTo(map);
        L.marker(route[0], { icon: pin('pin-start', data.loop ? 'Start / finish' : 'Start') }).addTo(map);
        map.fitBounds(line.getBounds(), { padding: [30, 30] });
        map.on('dragstart', () => setFollow(false));
    }
    function setFollow(v) { follow = v; $('follow').setAttribute('aria-pressed', String(v)); if (v && pos && map) map.setView(pos, Math.max(map.getZoom(), 17)); }
    $('follow').addEventListener('click', () => setFollow(!follow));

    // ---- compass (AC13.3) ----
    function showHeading() {
        const rose = $('rose');
        if (heading === null) { $('heading').textContent = 'tap'; return; }
        rose.style.transform = 'rotate(' + (-heading) + 'deg)';
        $('heading').textContent = Math.round(heading) + '° ' + H.compassName(heading) + (headingSource === 'gps' ? ' (GPS)' : '');
        if (me) { const a = me.getElement() && me.getElement().querySelector('.me-arrow'); if (a) a.style.transform = 'rotate(' + heading + 'deg)'; }
    }
    function onOrientation(e) {
        let h = null;
        if (typeof e.webkitCompassHeading === 'number') h = e.webkitCompassHeading;           // iPhone
        else if (e.absolute && typeof e.alpha === 'number') h = 360 - e.alpha;                // Android (absolute)
        if (h === null) return;
        const screenAngle = (screen.orientation && screen.orientation.angle) || 0;
        heading = (h + screenAngle + 360) % 360; headingSource = 'compass'; showHeading();
    }
    function enableCompass() {
        const DOE = window.DeviceOrientationEvent;
        if (!DOE) { status('This phone has no compass sensor; direction comes from GPS while you walk.'); return; }
        const listen = () => {
            window.addEventListener('deviceorientationabsolute', onOrientation, true);
            window.addEventListener('deviceorientation', onOrientation, true);
        };
        if (typeof DOE.requestPermission === 'function') {                                    // iOS 13+: needs a tap
            DOE.requestPermission().then((r) => { if (r === 'granted') listen(); else status('Compass not allowed: direction comes from GPS while you walk.'); })
                .catch(() => status('Compass not available.'));
        } else listen();
    }
    $('compass').addEventListener('click', enableCompass);
    if (window.DeviceOrientationEvent && typeof DeviceOrientationEvent.requestPermission !== 'function') enableCompass();

    // ---- trail GPS guidance (AC13.11) ----
    const fmtM = (m) => m >= 1000 ? (m / 1000).toFixed(1) + ' km' : (Math.round(m / 10) * 10) + ' m';
    function say(text) {
        if (!voice || !('speechSynthesis' in window)) return;
        try { const u = new SpeechSynthesisUtterance(text); u.lang = 'en-GB'; speechSynthesis.cancel(); speechSynthesis.speak(u); } catch (e) { /* no voice */ }
    }
    function arrow(deg) { $('nav-arrow').style.transform = 'rotate(' + Math.max(-150, Math.min(150, deg)) + 'deg)'; }
    function navTo(main, then, deg, cls) {
        $('nav').hidden = false;
        $('nav-main').innerHTML = main; $('nav-then').innerHTML = then || '';
        $('nav').className = 'nav-banner' + (cls ? ' nav-' + cls : ''); arrow(deg);
    }
    function guide(pr, n) {
        if (off.off && state.startedAt) {
            const b = H.bearing(pos, n.point), rel = heading === null ? 0 : ((b - heading + 540) % 360) - 180;
            navTo('Back to the route: <strong>' + fmtM(n.offRouteM) + '</strong> ' + H.compassName(b), 'Follow the dashed red line on the map.', rel, 'off');
            if (!announced.off) { say('You are off the route. The route is ' + Math.round(n.offRouteM) + ' metres to the ' + H.compassName(b)); announced.off = true; }
            return;
        }
        announced.off = false;
        if (!state.startedAt && n.offRouteM > 150) {          // not on the trail yet: guide to the start
            const b = H.bearing(pos, route[0]), rel = heading === null ? b : ((b - heading + 540) % 360) - 180;
            const far = H.distance(pos, route[0]);
            navTo('Go to the start: <strong>' + fmtM(far) + '</strong> to the ' + H.compassName(b),
                far > 2000 ? '<a href="' + data.directions + '" target="_blank" rel="noopener">Road directions to the start</a>' : 'Follow the arrow' + (heading === null ? ' (north is up — tap the compass to turn it with you)' : ''), rel);
            return;
        }
        // on the trail: turn-by-turn directions straight away; Start only begins recording
        const ins = H.instruction(turnList, pr.doneM, total);
        if (ins.kind === 'arrive') {
            if (ins.inM < 40) { navTo('<strong>You have arrived!</strong>', 'Press Finish to save your hike.', 0, 'done'); if (!announced.end) { say('You have arrived.'); announced.end = true; } }
            else navTo('Continue <strong>' + fmtM(ins.inM) + '</strong> to the ' + (data.loop ? 'finish' : 'end'), 'No more turns.', 0);
            return;
        }
        navTo((ins.inM < 30 ? '<strong>Turn ' + ins.dir + ' now</strong>' : 'In <strong>' + fmtM(ins.inM) + '</strong> · turn ' + ins.dir),
            'Look for the ' + (ins.angle < 0 ? 'turn-left' : 'turn-right') + ' mark · then ' + fmtM(ins.thenM) + (ins.thenKind === 'arrive' ? ' to the end' : ' to the next turn'),
            ins.angle, ins.inM < 30 ? 'now' : '');
        const key = Math.round(ins.inM + pr.doneM);
        if (ins.inM < 80 && !announced[key + 'a']) { say('In ' + Math.round(ins.inM / 10) * 10 + ' metres, turn ' + ins.dir); announced[key + 'a'] = true; }
        if (ins.inM < 25 && !announced[key + 'b']) { say('Turn ' + ins.dir + ' now'); if (navigator.vibrate) navigator.vibrate(200); announced[key + 'b'] = true; }
    }
    function climbInfo(pr) {
        const km = pr.doneM / 1000, alt = H.altitudeAt(profile, km);
        $('alt').textContent = alt === null ? '—' : alt + ' m';
        const c = H.nextClimb(profile, km);
        $('climb').textContent = c ? (c.inM < 50 ? 'Climbing now: ' : 'Next climb in ' + fmtM(c.inM) + ': ') + '+' + c.gainM + ' m over ' + fmtM(c.lengthM) + ' (' + c.grade + ' %)' : (profile.length ? 'No big climbs left — mostly flat or downhill.' : '');
    }
    $('voice').addEventListener('click', () => {
        voice = !voice; $('voice').setAttribute('aria-pressed', String(voice)); $('voice').textContent = 'Voice: ' + (voice ? 'on' : 'off');
        if (voice) say('Voice directions on.');
    });

    // ---- position, progress and off-route (AC13.2, 13.4, 13.5) ----
    function status(html) { $('status').innerHTML = html; }
    const clock = (min) => { const d = new Date(Date.now() + min * 60000); return d.toTimeString().slice(0, 5); };
    function onPosition(p) {
        pos = [p.coords.latitude, p.coords.longitude]; accuracy = p.coords.accuracy;
        if ((heading === null || headingSource === 'gps') && p.coords.heading !== null && !isNaN(p.coords.heading) && (p.coords.speed || 0) > 0.5) {
            heading = p.coords.heading; headingSource = 'gps'; showHeading();
        }
        if (map) {
            if (!me) {
                me = L.marker(pos, { icon: L.divIcon({ className: 'me', html: '<span class="me-arrow"></span><span class="me-dot"></span>', iconSize: [34, 34] }), keyboard: false }).addTo(map);
                meCircle = L.circle(pos, { radius: accuracy, color: '#2f80ed', weight: 1, fillOpacity: 0.12 }).addTo(map);
                map.setView(pos, Math.max(map.getZoom(), 17));
            } else { me.setLatLng(pos); meCircle.setLatLng(pos).setRadius(accuracy); }
            if (follow) map.panTo(pos);
            showHeading();
            if (state.startedAt) trail.setLatLngs(state.track);
        }
        // Progress counts only after "Start hike" and only while on the route (AC13.16): standing at home, or at the
        // start of a loop, must not show the hike as almost done. The last accepted position is the hint for loops.
        const raw = H.progress(route, pos, cum, data.pace, state.alongM || 0);
        const n = raw.nearest, total = raw.totalM;
        const onRoute = n.offRouteM <= 60 + Math.min(accuracy || 0, 30);
        if (state.startedAt && onRoute) { state.alongM = raw.doneM; }
        const doneM = state.startedAt ? (state.alongM || 0) : 0, leftM = Math.max(0, total - doneM);
        const pr = { doneM, leftM, totalM: total, percent: total ? Math.round(100 * doneM / total) : 0,
            etaMin: data.pace > 0 ? Math.round(leftM / 1000 / data.pace * 60) : null, nearest: n };
        if (state.startedAt) {
            const last = state.track[state.track.length - 1];
            if (!last || H.distance(last, pos) >= Math.max(5, Math.min(accuracy || 0, 30))) { state.track.push([+pos[0].toFixed(6), +pos[1].toFixed(6)]); store.save(state); }   // ignore GPS wobble when standing still
        }
        if (walked && state.startedAt && onRoute) walked.setLatLngs(route.slice(0, n.segment + 1).concat([n.point]));
        $('done').textContent = H.formatKm(pr.doneM);
        $('left').textContent = H.formatKm(pr.leftM);
        $('pct').textContent = pr.percent + '%';
        $('bar').style.width = pr.percent + '%';
        $('eta').textContent = pr.etaMin !== null ? clock(pr.etaMin) : '—';
        off = H.offRouteState(off, n.offRouteM, accuracy);
        guide(pr, n); climbInfo(pr);
        const banner = $('offroute');
        if (off.off) {
            const b = H.bearing(pos, n.point);
            banner.hidden = false;
            banner.innerHTML = '<strong>You are off the route.</strong> The route is ' + Math.round(n.offRouteM) + ' m to the ' + H.compassName(b) +
                ' (' + Math.round(b) + '°). Go back to the last trail mark you saw.';
            if (off.count === 2 && navigator.vibrate) navigator.vibrate([250, 120, 250]);
            if (map) { if (toRoute) toRoute.setLatLngs([pos, n.point]); else toRoute = L.polyline([pos, n.point], { color: '#d62828', dashArray: '6 6', weight: 3 }).addTo(map); }
        } else {
            banner.hidden = true;
            if (toRoute) { toRoute.remove(); toRoute = null; }
        }
        if (!state.startedAt && n.offRouteM > 150) {
            const d = H.distance(pos, route[0]), b = H.bearing(pos, route[0]);
            status('The start is ' + H.formatKm(d) + ' away to the ' + H.compassName(b) + '. Press <strong>Start hike</strong> when you get there.');
        } else if (state.startedAt) {
            status('On the trail · GPS accuracy ±' + Math.round(accuracy) + ' m' + (pr.leftM < 60 ? ' · <strong>You have arrived!</strong>' : ''));
        }
    }
    function onError(err) {
        status(err.code === 1 ? 'Location is blocked. Allow location for this site in your browser settings to see where you are.'
            : 'No GPS signal yet — try in the open, away from buildings.');
    }
    function watch() {
        if (!('geolocation' in navigator)) { status('This browser cannot read your location. You can still use the map and the trail marks.'); return; }
        if (watchId === null) watchId = navigator.geolocation.watchPosition(onPosition, onError, { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 });
    }

    // ---- screen on (AC13.9) ----
    async function keepAwake() {
        try { if ('wakeLock' in navigator && document.visibilityState === 'visible') wakeLock = await navigator.wakeLock.request('screen'); } catch (e) { /* not allowed: ignore */ }
    }
    document.addEventListener('visibilitychange', () => { if (state.startedAt && document.visibilityState === 'visible') keepAwake(); });

    // ---- start / finish (AC13.1, 13.8) ----
    let ticker = null;
    function tick() { $('clock').textContent = H.formatElapsed(Date.now() - state.startedAt); }   // counts from Start, every second
    function started() {
        $('start').hidden = true; $('finish').hidden = false;
        root.classList.add('is-hiking');
        tick(); ticker = setInterval(tick, 1000);
        keepAwake(); watch();
        root.querySelectorAll('.drop').forEach((d) => { d.open = false; });   // on the trail: only map, directions and progress
    }
    $('start').addEventListener('click', () => { state = { startedAt: Date.now(), track: pos ? [pos] : [], alongM: 0 }; store.save(state); enableCompass(); started(); });
    $('finish').addEventListener('click', () => {
        if (!window.confirm('Finish this hike?')) return;
        const minutes = Math.max(1, Math.round((Date.now() - state.startedAt) / 60000));
        const km = H.trackLength(state.track) / 1000;
        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
        clearInterval(ticker); if (wakeLock) wakeLock.release().catch(() => {});
        store.clear();
        const url = new URL(data.publish, location.href);
        if (!navigator.onLine) {                                       // AC13.14 keep the hike until there is signal
            url.searchParams.set('duration_min', String(minutes));
            if (km >= 0.1) url.searchParams.set('distance_km', km.toFixed(1));
            try { localStorage.setItem('ts-pending-hike', JSON.stringify({ url: url.toString(), name: data.name, km: km.toFixed(1), minutes, at: Date.now() })); } catch (e) { /* ignore */ }
            status('<strong>Hike finished: ' + km.toFixed(1) + ' km in ' + minutes + ' min.</strong> Saved on this phone — open TrailSense when you have signal to publish it.');
            $('finish').hidden = true; $('start').hidden = false; root.classList.remove('is-hiking');
            return;
        }
        url.searchParams.set('duration_min', String(minutes));
        if (km >= 0.1) url.searchParams.set('distance_km', km.toFixed(1));
        location.href = url.toString();
    });

    // ---- safety: share my position (AC13.7) ----
    $('share').addEventListener('click', async () => {
        const out = $('share-status');
        if (!pos) { watch(); out.textContent = 'Getting your position… press again in a moment.'; return; }
        const coords = pos[0].toFixed(5) + ', ' + pos[1].toFixed(5);
        const link = 'https://www.openstreetmap.org/?mlat=' + pos[0].toFixed(5) + '&mlon=' + pos[1].toFixed(5) + '#map=17/' + pos[0].toFixed(5) + '/' + pos[1].toFixed(5);
        const text = 'My position on ' + data.name + ': ' + coords + ' (±' + Math.round(accuracy) + ' m) ' + link;
        try {
            if (navigator.share) { await navigator.share({ title: 'My position', text }); out.textContent = 'Position shared.'; }
            else { await navigator.clipboard.writeText(text); out.textContent = 'Copied: ' + coords; }
        } catch (e) { out.textContent = coords + ' · ' + link; }
    });

    // offline download and the no-signal mode live in hike-offline.js (AC13.12–13.14, ADR-020)
    window.HikeOffline({ $, data, route, H, status, getMap: () => map, isStarted: () => !!state.startedAt });

    if (state.startedAt) started(); else watch();
});
