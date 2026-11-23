# ARCHITECTURE.md — current state of the system

Defined before the first prompt. Describes *what is*, not history — history lives in DECISIONS.md.

## Style: layered monolith (client · server · database)
The A1 diagram had three blocks: web application, application backend (auth, business logic, API integration,
rule engine, community services) and storage + external services (OpenStreetMap, Open-Meteo). The MVP keeps that shape
and makes each block an explicit layer:

```
 Browser — HTML + one CSS file, phone-first; small vanilla JS: app.js (menus, copy/share, map layers),
           hike-core.js (route maths, tested with Node) · hike.js · hike-offline.js (GPS directions, offline),
           photo-shrink.js (resizes phone photos) · sw.js (service worker: offline pages and map tiles)
        │  HTTP GET/POST (?r=route)                 │  GET /api/v1/… (read-only JSON for other apps)
 ┌──────▼───────────────────────────────────────────▼──────────────┐
 │ public/index.php  front controller → src/routes.php · src/Api/Router │
 │ Controllers  Auth · Home · Trail · Plan · Group(Groups) · Activity(Feed) · Profile · Hike │
 │      │                                                             │
 │ Domain (pure PHP, no I/O)          Weather / Alerts (adapters)     │
 │  RiskEngine · Verdict · Daylight    WeatherProvider ◄─ OpenMeteoProvider ──► api.open-meteo.com
 │  Briefing · Climb · RouteType        (demo provider only locally/tests) │
 │  Waymarks · WeatherCode · Sources   OfficialAlertsProvider ◄─ IpmaProvider ──► api.ipma.pt
 │  Validators (Plan, Activity, User, Photo)                          │
 │ Services: AssessmentService (weather → engine → storage) · WeatherOutlook (7 days, cached 1 h) · PhotoResizer │
 │      │                                                             │
 │ Repositories (the only SQL; PDO prepared statements)               │
 └──────┬─────────────────────────────────────────────────────────────┘
        │ SQL
 PostgreSQL — 17 tables: app_user · trail · trail_photo · trail_report · trail_favourite · hike_plan · group_plan ·
   hike_participant · risk_rule · weather_snapshot · risk_assessment · assessment_result · activity · activity_photo ·
   activity_like · activity_comment · schema_migration
 External (browser side): OpenStreetMap / OpenTopoMap / Waymarked Trails tiles · Wikimedia Commons photos · Leaflet (unpkg, SRI)
```

Dependency rule: Controllers → Services / Domain / Weather / Alerts / Repositories. Domain depends on nothing.
Repositories depend only on PDO. Views receive plain arrays and print every value with `e()`.

## Folder structure
```
trailsense/
├── CLAUDE.md  AGENTS.md  SPEC.md  README.md
├── .claude/              agents/ (security-reviewer, ux-reviewer) · commands/new-feature · skills/risk-rules · settings.json (hooks)
├── .github/workflows/    tests.yml (suite on every push) · screenshots.yml (report screenshots)
├── bin/                  db-reset · db-init · demo-refresh · screenshots.py · gen-trail-data.py · hooks/
├── config/               config.example.php (committed) · config.php (secrets, git-ignored)
├── database/             schema.sql · seed.sql · migrations/002–006
├── docs/                 ARCHITECTURE · DATA_MODEL · DECISIONS (ADR-001…022) · TRACEABILITY · MANUAL_TESTS · UX_REVIEW · JOURNEYS ·
│                         VALUE_CASE · research/ · API + openapi.yaml · DEPLOY · DEMO_SCRIPT · LESSONS · DEBT · REVIEW_CHECKLIST · screenshots/
├── public/               index.php (only entry) · sw.js · assets/ (app.css, app.js, hike-core.js, hike.js, hike-offline.js, photo-shrink.js, img/)
├── src/
│   ├── bootstrap.php     autoload, config, session, helpers (e(), url(), asset())
│   ├── routes.php        route name → [method, Controller, action]
│   ├── Core/             Database · Installer (migrations) · Auth · Csrf · View · Request · Flash · Html · Icons ·
│   │                     StaticMap · WeatherChart · ElevationChart (server-side SVG) · HttpException
│   ├── Domain/           see diagram
│   ├── Weather/ Alerts/  adapters behind interfaces
│   ├── Services/         AssessmentService · WeatherOutlook · PhotoResizer
│   ├── Repositories/     User · Trail · Plan · Participant · Favourite · Rule · Assessment · Activity · Social · Profile · TrailReport
│   ├── Api/              Router · Endpoints · ApiError (read-only JSON, no personal data)
│   └── Controllers/      one per area
├── views/                layout.php · partials/ · auth/ · home/ · trails/ · plans/ · groups/ · hike/ · feed/ · profile/ · errors/
└── tests/                run.php · Unit/ · Integration/ · Http/ · js/ (Node) · fixtures/
```

## Main flow: plan → check → advise (F2–F5)
1. `PlanController::store` validates with `PlanValidator`, saves `hike_plan` (+ `group_plan` for organizers).
2. `AssessmentService::assess(plan)` asks `WeatherProvider` for the hourly window, computes sunset with `Daylight`,
   stores a `weather_snapshot`, runs `RiskEngine` with the rules from `risk_rule`, stores `risk_assessment` + one
   `assessment_result` per rule.
3. `plans/show` renders each factor with level badge (text + colour), rule condition, observed value and precaution.
4. Organizer: `Briefing::build()` renders the same data as plain text; the organizer can edit it and copy it or send it on WhatsApp.

## On the trail (F13, ADR-015/020)
`HikeController` serves the route; everything else happens in the browser: position (Geolocation), progress and turn-by-turn
directions (`hike-core.js`), compass, voice, wake lock. Nothing is sent to the server until the hiker publishes. A trail can be
downloaded for offline use (service worker caches the page and the map tiles near the route).

## Cross-cutting concerns
**Security**
- Every POST: CSRF token checked first (`Core/Csrf`), then login (`Auth::require…`), then validation on the server, then the action, then a redirect (PRG).
- Authorisation in the controller before any write: a hiker cannot read or change another user's personal plan; organizer-only actions check the role.
- SQL only in `src/Repositories`, always prepared statements; every value in a view is printed with `e()` (HTML escaping).
- Passwords with `password_hash` / `password_verify`; the session id is regenerated at login; session cookie is HttpOnly, SameSite=Lax and Secure on HTTPS.
- Headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`.
- Uploads: type checked by file content, size limited, stored with a random name; secrets only in environment variables or the git-ignored `config/config.php`.

**When an external service fails**
- Open-Meteo has a 6 s timeout; if it fails, the risk check is marked *incomplete* and is never shown as OK (ADR-004).
- IPMA warnings and fire risk that are not published yet are shown as "not published yet"; the rest of the check still works.
- Map tiles or Leaflet unavailable → the route is drawn as SVG; photos unavailable → the route drawing is shown instead.
- The 7-day outlook on the trail page is cached for 1 hour, so a slow API does not slow every page view.

**Privacy**
- GPS position and track stay on the phone; only distance and duration are sent if the hiker publishes (ADR-015).
- Uploaded photos are re-encoded, which removes their location metadata (EXIF).
- Personal plans are private; the public API returns no personal data and no e-mail addresses.

**Accessibility and phones**
- Phone-first layout checked at 360 px; labels on every input; risk levels always shown as text, never by colour alone; keyboard navigation; contrast checked (docs/UX_REVIEW.md).

**Performance**
- Card maps are static images built on the server (ADR-018); elevation profiles are stored once (ADR-019); offline downloads are limited to the tiles near the route (ADR-020).

## Deployment (ADR-009)
alwaysdata (free, always on): Apache + PHP 8.3 + PostgreSQL, document root `public/`, auto-deploy from GitHub every 5 min.
Config from environment variables; `Core/Installer` creates the tables on first run. See docs/DEPLOY.md.
