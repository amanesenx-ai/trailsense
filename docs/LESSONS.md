# LESSONS.md — recurring mistakes and fixes
- Test files share PHP's global scope: a test variable named `$start` overwrote the runner's timer. Fix: each test file is required inside a closure.
- A POST test failed with 419 because the page it came from had no form (no CSRF token). Tests must take the token from the page that really contains the form — like a user.
- Claude Code hook matchers match the tool name (`Bash`), not its arguments; the secret-scan script filters `git commit` itself.
- Booleans to PostgreSQL through PDO: pass `'true'/'false'` explicitly for clarity.
- Weather is external and unreliable: design the "unavailable" state first (ADR-004) and test it before the happy path.
- Release 2 deploy returned HTTP 500: release 1 had written an ISO date into `var/installed`; release 2 read that file as a version number ("2026") and skipped migration 002. Fix: new marker file, only digits trusted, regression test with a legacy marker. Lesson: when a file's meaning changes, change its name.
- Overpass API was overloaded (502/timeouts); the OSM API `relation/{id}/full.json` was reliable. Joining route ways in member order left gaps; nearest-endpoint joining fixed them. Always compare computed distance with the official one.
- Third-party map libraries add stacking contexts (Leaflet panes z-index 400+): isolate them so sticky UI stays on top. Only visible on the real site, with real tiles.

## Release 3
- A security header can break a third-party service: `Referrer-Policy: same-origin` made OSM block our tiles. It looked fine in one browser because cached tiles were shown. Lesson: check a fresh browser profile and read the service's usage policy.
- Navigation maths written as pure JS functions, so they could be tested with Node from the same `php tests/run.php` command. The DOM code stays thin.
- Screenshots at 360 px caught two problems the tests could not (5 px chart text, a "hidden" button that showed).

## Release 4
- A reference document beats memory: the waymark drawings changed after reading the Rota Vicentina manual (PL is white over green, not green over white).
- A 90 m terrain model gives false steep steps, and our first ascent numbers were inflated by noise. Smoothing plus a fixed window gave believable ratings; the numbers are labelled as estimates.
- Data from an external API is fetched once and stored (migration), so pages never depend on it at view time.

## Releases 5–8 (19–22 Nov)
- A service worker that serves CSS "cache first" hid every style update from returning users. Network first for the app shell, cache only as the offline fallback.
- Changing the colour scheme without showing a preview first cost a full round-trip ("why is it white?"). Show a screenshot before switching a visual style.
- A YAML error (an unquoted `:` in a step name) made GitHub reject the workflow file silently: every commit showed a red ✗ with no job and no warning. Validate workflow files locally (`yaml.safe_load`) and check that CI really runs.
- A comment pasted into the middle of a JavaScript line broke the whole hike page, and the PHP suite did not notice. Now every script in `public/` is checked with `node --check` in the suite.
- GPS progress must only count after "Start" and while on the route; for loops the start and the end are the same place, so the last known progress decides which one the hiker is at.
- A report generated from `information_schema` marked each column of a composite UNIQUE as UNIQUE on its own, which reads as a modelling error. Show composite keys as one key.
- Feedback from users is cheapest when acted on the same day (editable briefing, "Send on WhatsApp"); record what was asked in docs/research and the acceptance criterion in SPEC.md.
