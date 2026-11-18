# LESSONS.md — recurring mistakes and fixes
- Test files share PHP's global scope: a test variable named `$start` overwrote the runner's timer. Fix: each test file is required inside a closure.
- A POST test failed with 419 because the page it came from had no form (no CSRF token). Tests must take the token from the page that really contains the form — like a user.
- Claude Code hook matchers match the tool name (`Bash`), not its arguments; the secret-scan script filters `git commit` itself.
- Booleans to PostgreSQL through PDO: pass `'true'/'false'` explicitly for clarity.
- Weather is external and unreliable: design the "unavailable" state first (ADR-004) and test it before the happy path.
- Release 2 deploy returned HTTP 500: release 1 had written an ISO date into `var/installed`; release 2 read that file as a version number ("2026") and skipped migration 002. Fix: new marker file, only digits trusted, regression test with a legacy marker. Lesson: when a file's meaning changes, change its name.
- Overpass API was overloaded (502/timeouts); the OSM API `relation/{id}/full.json` was reliable. Joining route ways in member order left gaps; nearest-endpoint joining fixed them. Always compare computed distance with the official one.
- Third-party map libraries add stacking contexts (Leaflet panes z-index 400+): isolate them so sticky UI stays on top. Only visible on the real site, with real tiles.
