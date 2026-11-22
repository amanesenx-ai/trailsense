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
    'loop: standing at the start counts as the start, not the end (AC13.16)': () => {
        const loop = [[41.0, -8.0], [41.0, -7.99], [41.008, -7.995], [41.0001, -8.0001]];   // ends 14 m from the start
        const total = H.cumulative(loop).slice(-1)[0];
        const atStart = H.progress(loop, [41.0, -8.0], null, 4, 0);
        assert(atStart.doneM < 20, 'done near 0, got ' + atStart.doneM);
        assert(atStart.leftM > total - 20, 'left is almost the whole loop');
        const atEnd = H.progress(loop, [41.0, -8.0], null, 4, total - 30);           // came all the way round
        assert(atEnd.doneM > total - 40, 'done near the total when the hiker was already near the end');
    },
    'nearest without a hint behaves as before on simple routes': () => {
        assert.strictEqual(H.nearest(route, [41.0003, -7.995]).segment, 0);
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
    'turns: an L-shaped route has one left turn at the corner; a straight line has none (AC13.11)': () => {
        const L = [[41, -8], [41, -7.994], [41.0045, -7.994]];
        const t = H.turns(L);
        assert.strictEqual(t.length, 1); assert.strictEqual(t[0].dir, 'left');
        assert(Math.abs(t[0].alongM - H.distance(L[0], L[1])) < 20);
        assert.strictEqual(H.turns([[41, -8], [41, -7.99], [41, -7.98]]).length, 0);
        const R = [[41, -8], [41.0045, -8], [41.0045, -7.994]];          // north then east = right
        assert.strictEqual(H.turns(R)[0].dir, 'right');
    },
    'instruction: next turn with distance, then arrive (AC13.11)': () => {
        const list = [{ alongM: 500, angle: -90, dir: 'left' }, { alongM: 900, angle: 150, dir: 'sharp right' }];
        assert.deepStrictEqual(H.instruction(list, 380, 1300), { kind: 'turn', dir: 'left', angle: -90, inM: 120, thenM: 400, thenKind: 'turn' });
        assert.strictEqual(H.instruction(list, 950, 1300).kind, 'arrive');
        assert.strictEqual(H.instruction(list, 950, 1300).inM, 350);
    },
    'altitude and next climb from the elevation profile': () => {
        const p = [[0, 100], [0.5, 100], [1, 150], [1.5, 200], [2, 195], [2.5, 150]];
        assert.strictEqual(H.altitudeAt(p, 0.75), 125);
        const c = H.nextClimb(p, 0.2);
        assert.strictEqual(c.gainM, 100); assert.strictEqual(c.inM, 300); assert.strictEqual(c.grade, 10);
        assert.strictEqual(H.nextClimb(p, 1.6), null);
    },
    'offline tiles: route corridor at zoom 13-16, no duplicates, capped (AC13.12)': () => {
        assert.deepStrictEqual(H.tileXY(41.15, -8.61, 13), [3900, 3066]);
        const r = [[41.18439, -8.48793], [41.17622, -8.49331], [41.17295, -8.47952]];
        const t = H.tilesForRoute(r);
        const keys = t.map((k) => k.z + '/' + k.x + '/' + k.y);
        assert.strictEqual(new Set(keys).size, keys.length);
        assert.deepStrictEqual([...new Set(t.map((k) => k.z))], [13, 14, 15, 16]);
        assert(t.length > 20 && t.length <= 600, 'between 20 and 600 tiles, got ' + t.length);
        assert(t.every((k) => k.z <= 16), 'never zoom 17+ (OSM tile policy)');
        assert.strictEqual(H.tilesForRoute(r, [13, 14, 15, 16], 1, 30).length, 30);
    },
};
let failed = 0;
for (const [name, fn] of Object.entries(tests)) {
    try { fn(); console.log('ok ' + name); } catch (e) { failed++; console.log('FAIL ' + name + ': ' + e.message); }
}
process.exit(failed ? 1 : 0);
