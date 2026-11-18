/* TrailSense hike-core (F13, AC13.10): pure navigation maths, no DOM. Tested by tests/js/hike-core.test.js. */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.HikeCore = factory();
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';
    const R = 6371000, rad = (d) => d * Math.PI / 180, deg = (r) => r * 180 / Math.PI;

    /** Great-circle distance in metres between [lat, lon] points. */
    function distance(a, b) {
        const dLat = rad(b[0] - a[0]), dLon = rad(b[1] - a[1]);
        const h = Math.sin(dLat / 2) ** 2 + Math.cos(rad(a[0])) * Math.cos(rad(b[0])) * Math.sin(dLon / 2) ** 2;
        return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
    }

    /** Initial bearing from a to b, degrees 0–360 (0 = north). */
    function bearing(a, b) {
        const y = Math.sin(rad(b[1] - a[1])) * Math.cos(rad(b[0]));
        const x = Math.cos(rad(a[0])) * Math.sin(rad(b[0])) - Math.sin(rad(a[0])) * Math.cos(rad(b[0])) * Math.cos(rad(b[1] - a[1]));
        return (deg(Math.atan2(y, x)) + 360) % 360;
    }

    /** 8-point compass name for a bearing. */
    function compassName(b) {
        return ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'][Math.round(((b % 360) + 360) % 360 / 45) % 8];
    }

    /** Cumulative metres at each vertex. */
    function cumulative(route) {
        const out = [0];
        for (let i = 1; i < route.length; i++) out.push(out[i - 1] + distance(route[i - 1], route[i]));
        return out;
    }

    /** Nearest point on the route polyline: {point, offRouteM, alongM, segment}. Local equirectangular projection per segment. */
    function nearest(route, pos, cum) {
        cum = cum || cumulative(route);
        let best = null;
        const kx = Math.cos(rad(pos[0])) * R * Math.PI / 180, ky = R * Math.PI / 180;
        for (let i = 0; i < route.length - 1; i++) {
            const a = route[i], b = route[i + 1];
            const ax = (a[1] - pos[1]) * kx, ay = (a[0] - pos[0]) * ky, bx = (b[1] - pos[1]) * kx, by = (b[0] - pos[0]) * ky;
            const dx = bx - ax, dy = by - ay, len2 = dx * dx + dy * dy;
            let t = len2 === 0 ? 0 : -(ax * dx + ay * dy) / len2;
            t = Math.max(0, Math.min(1, t));
            const px = ax + t * dx, py = ay + t * dy, d = Math.hypot(px, py);
            if (best === null || d < best.offRouteM) {
                best = { offRouteM: d, segment: i, point: [a[0] + t * (b[0] - a[0]), a[1] + t * (b[1] - a[1])],
                    alongM: cum[i] + t * (cum[i + 1] - cum[i]) };
            }
        }
        return best;
    }

    /** Progress for a position: done/left metres, percent, ETA in minutes at the given pace (km/h). */
    function progress(route, pos, cum, paceKmh) {
        cum = cum || cumulative(route);
        const n = nearest(route, pos, cum), total = cum[cum.length - 1];
        const left = Math.max(0, total - n.alongM);
        return { doneM: n.alongM, leftM: left, totalM: total, percent: total ? Math.round(100 * n.alongM / total) : 0,
            etaMin: paceKmh > 0 ? Math.round(left / 1000 / paceKmh * 60) : null, nearest: n };
    }

    /** Off-route state machine (AC13.5): off after `needed` consecutive fixes beyond the limit, back on at once. */
    function offRouteState(prev, offRouteM, accuracyM, limitM, needed) {
        limitM = limitM || 50; needed = needed || 2;
        const beyond = offRouteM > limitM + Math.min(accuracyM || 0, 30);
        const count = beyond ? (prev.count || 0) + 1 : 0;
        return { count, off: count >= needed };
    }

    /** Metres of a recorded track (ignores jumps < 3 m, which are GPS noise). */
    function trackLength(track) {
        let m = 0;
        for (let i = 1; i < track.length; i++) { const d = distance(track[i - 1], track[i]); if (d >= 3) m += d; }
        return m;
    }

    function formatKm(m) { return (m / 1000).toFixed(m < 10000 ? 2 : 1) + ' km'; }

    return { distance, bearing, compassName, cumulative, nearest, progress, offRouteState, trackLength, formatKm };
}));
