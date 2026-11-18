// Node tests for public/assets/hike-core.js (AC13.10). Run by tests/Unit/HikeCoreTest.php.
const H = require('../../public/assets/hike-core.js');
const assert = require('assert');
const route = [[41.0, -8.0], [41.0, -7.99], [41.01, -7.99]];   // ~840 m east, then ~1112 m north
const tests = {
    'distance: 0.01° of latitude ≈ 1112 m': () => assert(Math.abs(H.distance([41, -8], [41.01, -8]) - 1112) < 3),
    'bearing and compass name': () => {
        assert(Math.abs(H.bearing([41, -8], [41.01, -8])) < 0.5);
        assert(Math.abs(H.bearing([41, -8], [41, -7.99]) - 90) < 0.5);
        assert.strictEqual(H.compassName(44), 'NE'); assert.strictEqual(H.compassName(359), 'N'); assert.strictEqual(H.compassName(181), 'S');
    },
    'nearest point and distance off the route': () => {
        const n = H.nearest(route, [41.0003, -7.995]);              // ~33 m north of the first leg
        assert.strictEqual(n.segment, 0); assert(Math.abs(n.offRouteM - 33) < 2);
        assert(Math.abs(n.alongM - H.distance(route[0], [41, -7.995])) < 2);
    },
    'progress: done, left, percent, ETA': () => {
        const p = H.progress(route, route[1], null, 4);
        const first = H.distance(route[0], route[1]), total = first + H.distance(route[1], route[2]);
        assert(Math.abs(p.doneM - first) < 1); assert(Math.abs(p.leftM - (total - first)) < 1);
        assert.strictEqual(p.percent, Math.round(100 * first / total));
        assert.strictEqual(p.etaMin, Math.round((total - first) / 1000 / 4 * 60));
        assert.strictEqual(H.progress(route, route[2], null, 4).percent, 100);
    },
    'off route only after two consecutive fixes beyond 50 m; back on at once (AC13.5)': () => {
        let s = H.offRouteState({}, 80, 5); assert.strictEqual(s.off, false);
        s = H.offRouteState(s, 90, 5); assert.strictEqual(s.off, true);
        s = H.offRouteState(s, 10, 5); assert.strictEqual(s.off, false);
        assert.strictEqual(H.offRouteState({ count: 1 }, 60, 30).off, false);   // within limit + accuracy
    },
    'track length ignores GPS jitter under 3 m': () => {
        const t = [[41, -8], [41.00001, -8], [41.01, -8]];
        assert(Math.abs(H.trackLength(t) - H.distance(t[1], t[2])) < 1);
    },
};
let failed = 0;
for (const [name, fn] of Object.entries(tests)) {
    try { fn(); console.log('ok ' + name); } catch (e) { failed++; console.log('FAIL ' + name + ': ' + e.message); }
}
process.exit(failed ? 1 : 0);
