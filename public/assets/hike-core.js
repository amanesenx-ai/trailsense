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
    /** Nearest point on the route. Where the route passes close to the same place twice (a loop's start and end,
        an out-and-back), `hintM` — how far along the hiker already was — picks the right pass (default: the start). */
    function nearest(route, pos, cum, hintM) {
        cum = cum || cumulative(route);
        const all = [];
        const kx = Math.cos(rad(pos[0])) * R * Math.PI / 180, ky = R * Math.PI / 180;
        for (let i = 0; i < route.length - 1; i++) {
            const a = route[i], b = route[i + 1];
            const ax = (a[1] - pos[1]) * kx, ay = (a[0] - pos[0]) * ky, bx = (b[1] - pos[1]) * kx, by = (b[0] - pos[0]) * ky;
            const dx = bx - ax, dy = by - ay, len2 = dx * dx + dy * dy;
            let t = len2 === 0 ? 0 : -(ax * dx + ay * dy) / len2;
            t = Math.max(0, Math.min(1, t));
            const px = ax + t * dx, py = ay + t * dy, d = Math.hypot(px, py);
            all.push({ offRouteM: d, segment: i, point: [a[0] + t * (b[0] - a[0]), a[1] + t * (b[1] - a[1])],
                alongM: cum[i] + t * (cum[i + 1] - cum[i]) });
        }
        if (!all.length) return null;
        const hint = typeof hintM === 'number' ? hintM : 0;
        // One candidate per pass of the route near the hiker: the closest point, plus the closest point of any other
        // pass (more than 100 m further along the route) that is almost as close. Then take the pass nearest to
        // where the hiker already was. On a simple stretch there is one pass, so the closest point always wins.
        const byDist = all.slice().sort((x, y) => x.offRouteM - y.offRouteM);
        const passes = [byDist[0]];
        for (const c of byDist) {
            if (c.offRouteM > byDist[0].offRouteM + 40) break;
            if (passes.every((q) => Math.abs(q.alongM - c.alongM) > 100)) passes.push(c);
        }
        passes.sort((x, y) => Math.abs(x.alongM - hint) - Math.abs(y.alongM - hint));
        return passes[0];
    }

    /** Progress for a position: done/left metres, percent, ETA in minutes at the given pace (km/h). */
    function progress(route, pos, cum, paceKmh, hintM) {
        cum = cum || cumulative(route);
        const n = nearest(route, pos, cum, hintM), total = cum[cum.length - 1];
        const left = Math.max(0, total - n.alongM);
        return { doneM: n.alongM, leftM: left, totalM: total, percent: total ? Math.round(100 * n.alongM / total) : 0,
            etaMin: paceKmh > 0 ? Math.round(left / 1000 / paceKmh * 60) : null, nearest: n };
    }

    /** Elapsed time for the hike clock: "0:07" (m:ss) under an hour, "1:02:05" (h:mm:ss) after (AC13.17). */
    function formatElapsed(ms) {
        const t = Math.max(0, Math.floor((ms || 0) / 1000)), h = Math.floor(t / 3600), m = Math.floor((t % 3600) / 60), sec = t % 60;
        return h > 0 ? h + ':' + String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0') : m + ':' + String(sec).padStart(2, '0');
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

    /** Point at `d` metres along the route. */
    function pointAt(route, cum, d) {
        d = Math.max(0, Math.min(cum[cum.length - 1], d));
        let i = 1;
        while (i < cum.length - 1 && cum[i] < d) i++;
        const seg = cum[i] - cum[i - 1] || 1, t = (d - cum[i - 1]) / seg;
        return [route[i - 1][0] + t * (route[i][0] - route[i - 1][0]), route[i - 1][1] + t * (route[i][1] - route[i - 1][1])];
    }

    const norm = (a) => ((a + 540) % 360) - 180;          // −180…180, + = right

    /** Turns along the route (AC13.11): direction change ≥ 55° over ±25 m, merged within 40 m (gentle bends are not announced). */
    function turns(route, cum, opts) {
        cum = cum || cumulative(route);
        const o = Object.assign({ arm: 25, step: 8, min: 55, gap: 40 }, opts || {});
        const total = cum[cum.length - 1], out = [];
        let group = null;                                    // consecutive sharp samples = one turn, keep the strongest
        for (let d = o.arm; d <= total - o.arm; d += o.step) {
            const a = pointAt(route, cum, d - o.arm), p = pointAt(route, cum, d), b = pointAt(route, cum, d + o.arm);
            const delta = norm(bearing(p, b) - bearing(a, p));
            if (Math.abs(delta) < o.min) continue;
            if (group && d - group.lastD <= o.gap) {
                if (Math.abs(delta) > Math.abs(group.angle)) { group.angle = delta; group.alongM = d; }
                group.lastD = d;
            } else {
                if (group) out.push(group);
                group = { alongM: d, angle: delta, lastD: d };
            }
        }
        if (group) out.push(group);
        return out.map((t) => ({ alongM: Math.round(t.alongM), angle: Math.round(t.angle), dir: turnName(t.angle) }));
    }

    function turnName(a) {
        const side = a > 0 ? 'right' : 'left', m = Math.abs(a);
        return (m < 70 ? 'slight ' : m >= 135 ? 'sharp ' : '') + side;
    }

    /** What to show now: the next turn, or the distance to the end (AC13.11). */
    function instruction(turnList, doneM, totalM) {
        const next = turnList.find((t) => t.alongM > doneM + 3);
        if (!next) return { kind: 'arrive', inM: Math.max(0, Math.round(totalM - doneM)) };
        const after = turnList.find((t) => t.alongM > next.alongM + 3);
        return { kind: 'turn', dir: next.dir, angle: next.angle, inM: Math.round(next.alongM - doneM),
            thenM: Math.round((after ? after.alongM : totalM) - next.alongM), thenKind: after ? 'turn' : 'arrive' };
    }

    /** Altitude at `km` from a [[km, m], ...] profile (linear interpolation). */
    function altitudeAt(profile, km) {
        if (!profile || profile.length < 2) return null;
        for (let i = 1; i < profile.length; i++) {
            if (profile[i][0] >= km) {
                const a = profile[i - 1], b = profile[i], t = (km - a[0]) / ((b[0] - a[0]) || 1);
                return Math.round(a[1] + t * (b[1] - a[1]));
            }
        }
        return profile[profile.length - 1][1];
    }

    /** The next climb ahead of `km` (gain ≥ 25 m, dips < 15 m ignored): {inM, gainM, lengthM, grade} or null. */
    function nextClimb(profile, km) {
        if (!profile || profile.length < 3) return null;
        const ahead = profile.filter((p) => p[0] >= km);
        let low = ahead[0], high = ahead[0];
        for (const p of ahead.slice(1)) {
            if (p[1] > high[1]) high = p;
            if (high[1] - p[1] >= 15 || p === ahead[ahead.length - 1]) {
                if (high[1] - low[1] >= 25) {
                    const len = (high[0] - low[0]) * 1000;
                    return { inM: Math.round((low[0] - km) * 1000), gainM: Math.round(high[1] - low[1]), lengthM: Math.round(len), grade: Math.round((high[1] - low[1]) / len * 1000) / 10 };
                }
                low = p; high = p;
            }
            if (p[1] < low[1]) { low = p; high = p; }
        }
        return null;
    }

    /** Slippy-map tile x/y for a point at zoom z. */
    function tileXY(lat, lon, z) {
        const n = 2 ** z, r = rad(lat);
        return [Math.floor((lon + 180) / 360 * n), Math.floor((1 - Math.log(Math.tan(r) + 1 / Math.cos(r)) / Math.PI) / 2 * n)];
    }

    /** AC13.12 tiles to save for offline: every tile the route crosses (sampled every ~25 m) plus a corridor of `pad` tiles. */
    function tilesForRoute(route, zooms, pad, max) {
        zooms = zooms || [13, 14, 15, 16]; pad = pad === undefined ? 1 : pad; max = max || 600;
        const cum = cumulative(route), total = cum[cum.length - 1], seen = new Set(), out = [];
        for (const z of zooms) {
            for (let d = 0; d <= total; d += 25) {
                const p = pointAt(route, cum, d), [x, y] = tileXY(p[0], p[1], z);
                for (let dx = -pad; dx <= pad; dx++) for (let dy = -pad; dy <= pad; dy++) {
                    const k = z + '/' + (x + dx) + '/' + (y + dy);
                    if (!seen.has(k)) { seen.add(k); out.push({ z, x: x + dx, y: y + dy }); }
                }
            }
            if (out.length > max) return out.slice(0, max);
        }
        return out;
    }

    return { distance, bearing, compassName, cumulative, nearest, progress, formatElapsed, offRouteState, trackLength, formatKm, tileXY, tilesForRoute,
        pointAt, turns, instruction, altitudeAt, nextClimb };
}));
