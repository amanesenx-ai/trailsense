# Reviewer's 45 improvements — where each one is (release 5, 2026-11-19)

Status: ✅ done (code + automated test) · ✅M done, checked by hand · ➖ consciously out of scope (reason given).

| # | Improvement | Status | Where / evidence |
|---|---|---|---|
| 1 | Clear value proposition + landing page | ✅ | views/home/landing.php · Release5Flows *public landing page* |
| 2 | Headline "Hike smarter. Explore safer. Share the experience." | ✅ | landing hero |
| 3 | Three pillars | ✅ | landing "Everything for a good day on the trail" |
| 4 | Hiker vs Organizer benefits | ✅ | landing "Two profiles" |
| 5 | Separate dashboards | ✅ | views/home/hiker.php, views/home/organizer.php · Flows *organizer sees…* |
| 6 | Hiker: recommended, saved, recent hikes, weather, community | ✅ | hero with verdict, ★ Saved, Recently viewed, Your recent hikes, Recommended trails, community · Release5Flows *save a trail…* |
| 7 | Organizer: upcoming, events, participants, registration status, quick create | ✅ | organizer dashboard · Release5Flows *organizer dashboard…* |
| 8 | Search + filters: location, distance, difficulty, duration | ✅ | trails/index (name/region text, Region, Difficulty, Max duration, Max distance, Saved only) · Release5Flows *filters…* |
| 9 | Clear trail info incl. elevation and maps | ✅ | trails/show + "Is this route for me?" + elevation profile · Release4Flows |
| 10 | Weather on each trail page | ✅ | partials/trail-weather.php · Release5Flows *trail page: 7-day weather…* |
| 11 | Temperature, rain probability, wind, forecast for the hike period | ✅ | 7-day outlook; plan hour-by-hour adds rain chance + wind · Unit Release5 |
| 12 | Contextual recommendations from transparent rules | ✅ | risk_rule table, verdict + checklist + rule per factor (F4/F5) |
| 13 | Date/time of latest weather update | ✅ | trail outlook "updated dd/mm at hh:mm"; plan "checked … at …" |
| 14 | Save favourites, recently viewed, share routes | ✅ | trail_favourite table, session recents, Share button · Release5Flows |
| 15 | Simple hike creation for organizers (trail, date, time, max participants…) | ✅ | plans/form (group size, meeting point/time) |
| 16 | Participant list, monitor registrations, edit, cancel | ✅ | plan page "Who is coming", dashboard sign-ups, plan.edit/update, Cancel group hike · Release5Flows |
| 17 | Share organised hikes by link | ✅ | "Share invite link" on group hikes |
| 18 | Weather in organised hikes + communicate updates | ✅ | risk check per event, copyable briefing; edits re-run the check |
| 19 | Community: discover users, shared trails, interact | ✅ | feed, author profiles, likes, comments |
| 20 | Profiles: activity, shared trails, saved routes, stats | ✅ | profile/show (stats, chart, hikes, ★ saved — own profile only) |
| 21 | Follow users, likes, comments | ✅ / ➖ | likes + comments ✅; following ➖ (SPEC §3: out of MVP scope, reviewer marked it optional) |
| 22 | Visual consistency | ✅M | one app.css, shared cards/badges/buttons · docs/UX_REVIEW.md |
| 23 | Visual hierarchy | ✅M | verdict first, hero on dashboards · UX_REVIEW |
| 24 | Accessibility | ✅M | contrast, labels, aria-live, 44 px targets, text + colour statuses |
| 25 | Navigation | ✅M | top bar / phone tab bar; signed-out bar has Sign in / Create account |
| 26 | Mobile | ✅M | every screen checked at 360 px, no horizontal scroll (both roles) |
| 27 | Empty states | ✅ | saved, recent, hikes, events, plans tabs, filters, feed |
| 28 | Feedback: success, error, loading, confirmation | ✅ | flash success/error (role=alert), "Working…" on submit, confirm dialogs |
| 29 | Form validation with explanations | ✅ | PlanValidator, UserValidator, ActivityValidator; server-side, field messages |
| 30 | Registration, login, logout, secure auth | ✅ | password_hash, session regeneration, CSRF; + change password · Flows, Release5Flows |
| 31 | Data really saved/read from the DB | ✅ | PostgreSQL, repositories; integration tests |
| 32 | Create, view, edit, delete own data | ✅ | plans (create/edit/delete), hikes (publish), comments (delete), favourites, password |
| 33 | DB structure: tables, PK, FK, relationships | ✅ | 17 tables, docs/DATA_MODEL.md, er-diagram, migrations 002–005 |
| 34 | Access permissions | ✅ | ownership checks before writes · Release5Flows *nobody else can edit…* |
| 35 | No public demo credentials; fictional demo data | ✅ | removed from login page and README; seed people are fictional |
| 36 | Automated tests for main logic and critical flows | ✅ | `php tests/run.php` → 158 tests (unit, integration, HTTP, JS) |
| 37 | Manual testing of every screen | ✅M | docs/MANUAL_TESTS.md |
| 38 | Full journeys for both profiles | ✅ / ✅M | HTTP flow tests per role + docs/DEMO_SCRIPT.md |
| 39 | Architecture and folder structure documented | ✅ | docs/ARCHITECTURE.md, ADR-001…019 |
| 40 | SPEC.md from A1 user stories | ✅ | SPEC.md §1–11 |
| 41 | CLAUDE.md / AGENTS.md | ✅ | CLAUDE.md, AGENTS.md, .claude/ |
| 42 | Verify every LLM change | ✅ | tests first, run before every commit (hook), screenshots, live checks |
| 43 | Online and reachable | ✅ | https://amanesenx.alwaysdata.net + public API /api/v1 |
| 44 | 5-minute demo of both journeys | ✅ | docs/DEMO_SCRIPT.md |
| 45 | Realistic demo data | ✅ | 11 real OSM trails with photos, real forecast/IPMA, fictional people |
