/* TrailSense hike mode — finishing a hike (AC13.8, AC13.14, AC13.18).
   "Finish" opens a choice instead of going straight to the publish form: publish to the community,
   finish without publishing (nothing is sent anywhere), or keep hiking. */
window.HikeFinish = function (ctx) {
    'use strict';
    const { $, data, H, root, store, status, getState, stop } = ctx;
    const box = $('finish-box');
    const summary = () => {
        const st = getState();
        const minutes = Math.max(1, Math.round((Date.now() - st.startedAt) / 60000));
        const km = H.trackLength(st.track) / 1000;
        return { minutes, km, text: km.toFixed(1) + ' km in ' + H.formatElapsed(Date.now() - st.startedAt) };
    };
    const ended = () => { stop(); store.clear(); box.hidden = true; $('finish').hidden = true; root.classList.remove('is-hiking'); };

    $('finish').addEventListener('click', () => {
        $('finish-summary').textContent = 'So far: ' + summary().text + '.';
        box.hidden = false; $('finish').hidden = true;
        $('finish-publish').focus();
    });
    $('finish-cancel').addEventListener('click', () => { box.hidden = true; $('finish').hidden = false; $('finish').focus(); });

    $('finish-only').addEventListener('click', () => {
        const s = summary();
        ended();
        status('<strong>Hike finished: ' + s.text + '.</strong> Not published — nothing was sent.');
        $('done-box').hidden = false;
    });

    $('finish-publish').addEventListener('click', () => {
        const s = summary();
        ended();
        const url = new URL(data.publish, location.href);
        url.searchParams.set('duration_min', String(s.minutes));
        if (s.km >= 0.1) url.searchParams.set('distance_km', s.km.toFixed(1));
        if (!navigator.onLine) {                                       // AC13.14 keep the hike until there is signal
            try { localStorage.setItem('ts-pending-hike', JSON.stringify({ url: url.toString(), name: data.name, km: s.km.toFixed(1), minutes: s.minutes, at: Date.now() })); } catch (e) { /* ignore */ }
            status('<strong>Hike finished: ' + s.text + '.</strong> Saved on this phone — open TrailSense when you have signal to publish it.');
            $('done-box').hidden = false;
            return;
        }
        location.href = url.toString();
    });
};
