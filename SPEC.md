# TrailSense — MVP Specification (SPEC.md)

Source of truth for Assignment 2. Derived from the Assignment 1 user stories (F1–F9).
The agent implements this spec; it does not change it. Changes to scope are made here first, by the owner.

## 1. Product in one sentence
A web application where hikers (especially beginners) and club organizers find a trail, plan a hike,
get **weather warnings explained by explicit rules with a concrete precaution**, and share the hike with a community.

## 2. Scope (MVP — Priority 0)
| ID | Feature | Persona |
|----|---------|---------|
| F1 | Trail discovery with filters | Both |
| F2 | Hiking plan creation (trail, date, start time, duration) | Both |
| F3 | Weather forecast for the plan's date, place and time window | Both |
| F4 | Rule-based risk check (rain, wind, temperature, daylight) | Both |
| F5 | Explained advice: every warning shows the rule and a precaution | Both |
| F6 | Community feed | Both |
| F7 | Publish a hike (route, notes, photo) | Both |
| F8 | Sign-in with roles (Hiker / Club organizer) | Both |
| F9 | Group plan and copyable briefing | Organizer |
| K1 | Planning-completeness indicator (A1 organizational KPI made measurable) | Organizer |

## 3. Out of scope (do not build)
Automatic GPS tracking (hikes are entered manually), following users, notifications, challenges and achievements,
password reset by e-mail, trail CRUD by users (trails are curated data), native mobile app, multiple languages,
write access through the public API.
(Release 2 moved these into scope: likes and comments, profiles and statistics, interactive map — see §8.)

## 4. Entities (see database/schema.sql — the schema is authoritative)
- **app_user** — a person with one role: `hiker` or `organizer`.
- **trail** — curated route: region, distance, elevation gain, estimated duration, difficulty, start point, route polyline.
- **hike_plan** — a user's intention to hike one trail on a date, at a start time, for a duration.
- **group_plan** — 0..1 per hike_plan; only for organizers: group size, meeting point, meeting time.
- **risk_rule** — a predefined, human-readable rule: factor, comparator, threshold, level, explanation, precaution.
- **weather_snapshot** — the forecast values used for an assessment (kept for transparency/audit).
- **risk_assessment** — one evaluation of a plan against all active rules; overall level; complete or not.
- **assessment_result** — association between assessment and rule: observed value, triggered or not, evaluated or not.
- **activity** — a published hike (trail, date, distance, duration, notes), optionally linked to the plan it came from.
- **activity_photo** — 0..n photos of an activity.

## 5. Use cases and acceptance criteria

### F8 — Sign in and role (US8)
*As a user, I want to sign in with a role so that I only see the tools I need.*
- AC8.1 Registration requires name (2–80 chars), valid unique e-mail, password ≥ 8 chars, role ∈ {hiker, organizer}.
- AC8.2 Duplicate e-mail shows "An account with this e-mail already exists." and keeps the typed name/e-mail.
- AC8.3 Wrong credentials show one generic message; the password is never echoed back.
- AC8.4 After sign-in a hiker's menu is Trails · Plans · Feed; an organizer's menu is Trails · Groups · Feed.
- AC8.5 Pages other than login/register redirect anonymous users to login. Organizer-only actions return 403 for hikers.
- AC8.6 Passwords stored with `password_hash`; session id regenerated at login; all POST forms carry a CSRF token.

### F1 — Trail discovery (US1)
*As a hiker, I want to find a route that suits my level.*
- AC1.1 List shows every trail with name, region, distance, estimated time and difficulty badge.
- AC1.2 Filters: text (name/region), difficulty, maximum distance. Filters combine (AND) and survive in the URL (GET).
- AC1.3 No result → "No trails match these filters" with a link to clear filters.
- AC1.4 Trail detail shows the route drawing, elevation gain, description and a "Plan this hike" action.
- AC1.5 Invalid filter values (e.g. `max_km=abc`) are ignored, never cause an error.

### F2 — Hiking plan (US2)
*As a hiker, I set the trail, date, start and length so I can prepare.*
- AC2.1 Required: trail, date, start time, duration (30–720 min). Duration defaults to the trail's estimate.
- AC2.2 Date cannot be in the past. Errors are shown next to the field and the form keeps the typed values.
- AC2.3 Saved plans appear in "My plans", upcoming first; past plans are listed separately.
- AC2.4 A user can delete only their own plan (ownership checked on the server).

### F3 — Weather forecast (US3)
*As a hiker, I see the conditions for my hike.*
- AC3.1 Forecast is requested from Open-Meteo for the trail start point, for the hours between start and end.
- AC3.2 Shown: total precipitation (mm), max wind gust (km/h), min/max temperature (°C), sunset time.
- AC3.3 If the provider fails or the date is beyond the 16-day horizon → a clear message
  ("Forecast not available yet — it is published up to 16 days ahead") and nothing is invented.
- AC3.4 The values used are stored (weather_snapshot) with source and fetch time.

### F4 — Rule-based risk check (US4, from A1)
*As a hiker, I want understandable weather warnings so that I can make an informed decision.*
- AC4.1 Every active rule in `risk_rule` is evaluated; each result is OK, CAUTION, WARNING or NOT EVALUATED.
- AC4.2 Each warning explains which predefined rule was triggered (rule name + condition + observed value).
- AC4.3 Overall level = worst triggered level (OK < CAUTION < WARNING).
- AC4.4 Missing data marks the assessment **incomplete** and is shown as such — never as OK.
- AC4.5 Daylight is always evaluated (computed locally from date and coordinates), even without a forecast.
- AC4.6 Rules are data: changing a threshold in the table changes the outcome without code changes.

### F5 — Explained advice (US5)
*Each warning says what to do.*
- AC5.1 Every triggered rule shows at least one precaution.
- AC5.2 Daylight rule gives a concrete action: "Start by HH:MM or choose a shorter trail" (latest start = sunset − 30 min − duration).

### F9 — Group plan and briefing (US9, organizer)
*As an organizer, I brief my group without extra work.*
- AC9.1 Organizer plan form adds group size (2–40) and meeting point (required) and meeting time (≤ start time).
- AC9.2 A briefing text is generated from plan + assessment: trail, meeting, timing, warnings, precautions.
- AC9.3 Briefing has a "Copy briefing" button and is also selectable as plain text (works without JavaScript).

### F6 — Community feed (US6)
*I learn from other hikers.*
- AC6.1 Newest first; each post shows author, trail, date, distance, duration, photo (if any), notes excerpt.
- AC6.2 Opening a post shows full details and the route drawing.
- AC6.3 Empty feed shows an invitation to publish the first hike.

### F7 — Publish a hike (US7)
*I share route, photos and notes.*
- AC7.1 Required: trail, date (not in the future), duration (10–1440 min). Distance defaults to the trail's.
- AC7.2 Optional photo: JPEG/PNG/WebP only (checked by content), max 3 MB; stored with a random file name.
- AC7.3 Notes max 1000 chars. When published from a plan, the plan is linked.
- AC7.4 After publishing, the hike appears at the top of the feed.

### K1 — Planning completeness (organizational KPI from A1)
- AC-K1 Organizer home shows "plans checked": share of their upcoming plans with a complete, up-to-date assessment.

## 6. Non-functional requirements
- NFR1 Phone-first: usable at 360 px wide; touch targets ≥ 44 px; works up to desktop width.
- NFR2 Accessibility: text contrast ≥ 4.5:1, labels on every input, visible focus, levels not conveyed by colour only (text badge).
- NFR3 Security: prepared statements only; all output escaped with `e()`; CSRF on POST; server-side validation; secrets only in `config/config.php` (not committed).
- NFR4 Performance: a page renders in < 1 s locally; external forecast call has a 6 s timeout.
- NFR5 Runs on the FEUP stack: Apache + PHP 8 + PostgreSQL, no Composer dependencies at runtime.

## 7. Default risk rules (seed; editable in `risk_rule`)
| Code | Factor | Condition | Level | Precaution |
|------|--------|-----------|-------|------------|
| RAIN_C | rain | total precipitation ≥ 2 mm | CAUTION | Pack a waterproof jacket; rocks and trails may be slippery. |
| RAIN_W | rain | total precipitation ≥ 10 mm | WARNING | Postpone or choose a short, low trail with an easy way out. |
| WIND_C | wind | max gust ≥ 40 km/h | CAUTION | Avoid exposed ridges and cliff edges; secure loose gear. |
| WIND_W | wind | max gust ≥ 60 km/h | WARNING | Do not hike exposed routes; pick a sheltered valley trail or postpone. |
| HEAT_C | heat | max temperature ≥ 30 °C | CAUTION | Carry at least 2 L of water and avoid the midday hours. |
| COLD_C | cold | min temperature ≤ 3 °C | CAUTION | Wear layers, a hat and gloves; check for ice on shaded paths. |
| DAY_C | daylight | minutes between planned end and sunset < 60 | CAUTION | Bring a head torch and keep a steady pace. |
| DAY_W | daylight | minutes between planned end and sunset < 30 | WARNING | Start by HH:MM or choose a shorter trail. |

## 8. Release 2 — improvements on the Assignment 1 proposals (owner request, 2026-11-17)
Owner feedback on release 1: the map link only showed the start point; trails need real pictures; plans were invisible
to others; only one weather source; the app felt basic. Each item below comes from the A1 deck (problem, opportunity,
journeys or Priority 1 list).

### F1.2 Real trail map (A1: "Plan with map", "Share route")
*As a hiker, I want to see the real route on a map with its start and end.*
- AC1.6 The trail page shows an interactive OpenStreetMap map (zoom, pan) with the real route line from OpenStreetMap data,
  a **start** marker and an **end** marker (loop trails: one combined start/finish marker), each with a text label.
- AC1.7 Buttons: "Directions to the start" (OSM routing to the start point) and "View on OpenStreetMap" (the route itself).
- AC1.8 Without JavaScript or if map tiles fail, the SVG route drawing is shown instead (progressive enhancement).
- AC1.9 Map data attribution "© OpenStreetMap contributors" is always visible.

### F1.3 Trail photos (A1 used Wikipedia photos)
- AC1.10 Each trail shows real photos with author, licence and a link to the source (Wikimedia Commons); trail cards use the first photo.
- AC1.11 A photo that fails to load falls back to the route drawing; every photo has alt text.

### F4.2 Official warnings and fire risk — second external API (IPMA)
*As a hiker, I want official warnings and the rural fire risk included in the check.*
- AC4.7 For the trail's district, active IPMA warnings (yellow/orange/red) that overlap the hike window are evaluated by rules
  (yellow → CAUTION, orange/red → WARNING) and listed with their type (rain, wind, thunderstorm, heat, coastal swell for coastal trails…).
- AC4.8 The IPMA rural fire risk (1 Reduced … 5 Maximum) of the nearest municipality is evaluated (≥ 3 Elevated → CAUTION, ≥ 4 → WARNING).
- AC4.9 IPMA publishes warnings ~3 days and fire risk 2 days ahead; outside that window these rules show "not published yet"
  and are **advisory**: they do not make the assessment incomplete (`risk_rule.required = false`).
- AC4.10 IPMA failure never breaks the page; the rest of the check still works.

### F9.2 Group hikes open to hikers (A1 Bruno's journey "brief participants")
*As a hiker, I want to find and join a club group hike; as an organizer, I want to know who is coming.*
- AC9.4 Upcoming group hikes are listed for every signed-in user (date, trail, organizer, places left).
- AC9.5 A hiker can join while places are left (group_size) and leave before the date; joining twice is impossible.
- AC9.6 Participants see the plan page read-only (assessment + briefing); the organizer sees the participant list.
- AC9.7 Personal (non-group) plans stay private to their owner.

### F10 Community trail reports (A1 opportunity: "community trail reports")
- AC10.1 On a trail page any user can report the current condition (good, muddy, obstacle, overgrown, closed) with an optional note.
- AC10.2 The 5 most recent reports are shown; reports older than 30 days are marked as old.
- AC10.3 A plan's assessment shows the latest report of the last 14 days for that trail; a "closed" report adds a WARNING (rule TRAIL_CL).

### F6.2 Likes and comments (A1 Priority 1)
- AC6.4 A user can like/unlike a post once; the count is shown. Comments: 1–500 chars, newest last, author can delete own comment.

### F11 Profile and statistics (A1 Priority 1)
- AC11.1 Each user has a profile page: role, number of hikes, total km, total elevation gain, total time, hikes per month (last 6 months), published hikes.
- AC11.2 Authors in the feed link to their profile. E-mail addresses are never shown on profiles.

### F12 Public REST API (Assignment 2 §3: "reachable by other applications")
- AC12.1 Read-only JSON API under `/api/v1`: `GET /trails` (filters q, difficulty, max_km), `GET /trails/{id}`,
  `GET /trails/{id}/conditions?date=YYYY-MM-DD&start=HH:MM&duration=MIN` (forecast + IPMA + daylight + rule results, nothing stored),
  `GET /group-hikes`, `GET /feed`.
- AC12.2 JSON with correct status codes (200, 400 with an error message, 404); CORS enabled; no personal data (no e-mails).
- AC12.3 Documented in docs/API.md and docs/openapi.yaml; covered by HTTP tests.

## 9. Release 3 — on the trail, and a visual risk check (owner request, 2026-11-18)
Owner feedback on release 2: the map showed "Access blocked" (fixed: Referer policy); "after starting the trail there should be
feedback: the legend of that trail's waymarks, a map with a compass — it is too basic"; "the risk assessment layout should be
more visual, with a direct link to a weather API". Scope change approved by the owner: live GPS navigation moves into scope
(it was A1 Priority 1 "Automatic GPS tracking"); recording stays on the device until the hiker publishes.

### F13 Hike mode — navigation on the trail
*As a hiker on the trail, I want to see where I am on the route, which way I face, and how to read the trail's marks.*
- AC13.1 "Start hike" on the trail page and on plan pages opens a full-screen hike map for that trail.
- AC13.2 After the hiker allows location, the map shows the live position with its accuracy, and can follow it ("Follow me").
- AC13.3 Compass: a north arrow and the heading of the phone (device orientation; on iPhone after a tap that asks
  permission). If the phone has no compass, the heading comes from GPS movement, and the screen says so.
- AC13.4 Progress along the route: km done / km left, % of the route, elapsed time, estimated finish time.
- AC13.5 Off-route alert: more than 50 m from the route on two consecutive positions → banner + vibration + distance and
  direction back to the nearest point of the route. Back on route → banner clears.
- AC13.6 Waymark legend for this trail (FCMP standard): PR yellow/red, GR white/red, PL green/white — right way, turn left,
  turn right, wrong way. Boardwalk/managed routes without a PR code show their own guidance. Also shown on the trail page.
- AC13.7 Safety: 112 button, and "Share my position" (coordinates + OpenStreetMap link, copied/shared).
- AC13.8 "Finish" stops tracking and opens "Publish a hike" pre-filled with the trail, today's date, the measured distance and duration.
- AC13.9 The screen stays on while the hike map is open (Wake Lock, when the browser supports it). Nothing is sent to the server while hiking.
- AC13.10 Navigation maths (distance, nearest point on route, progress, bearing, off-route) is plain JavaScript with automated tests.

### F1.4 Hiking map layers
- AC1.12 Map layers: Standard (OpenStreetMap), Topographic (OpenTopoMap, contour lines) and an overlay of waymarked hiking routes
  (Waymarked Trails). Each layer shows its attribution. Requests send only our origin as Referer (tile usage policies).

### F4.3 Visual risk check with direct weather links
*As a hiker, I want to understand the check at a glance and see the weather hour by hour.*
- AC4.11 Verdict card: one sentence and icon — "Good to go" (OK), "Go prepared" (CAUTION), "Rethink this plan" (WARNING),
  "Check again closer to the date" (incomplete) — with the main reason.
- AC4.12 "What to bring / do" checklist built from the triggered rules' precautions (ticks are kept only on the device).
- AC4.13 Hour by hour for the hike window (± 2 h): weather icon (WMO code), temperature, rain, gusts — as a chart and as
  hour cards; hike window shaded, sunset marked. Hourly values are stored with the snapshot.
- AC4.14 Factors as visual tiles (icon, value, status in text + colour); rule details stay one tap away; full rules table kept.
- AC4.15 Direct links: the exact Open-Meteo API request used (JSON), the Open-Meteo page for this location, and the IPMA
  warnings page for the trail's district.

## 10. Release 4 — trail GPS, "is this route for me?", richer screens (owner request, 2026-11-18)
Owner feedback: the waymarks are drawn wrong (reference: Rota Vicentina manual "Marcação, Sinalética e Manutenção", FCMP marks p. 13);
explain whether a route is a Pequena or Grande Rota for first-timers; notes on the climbs; remove 112; hike mode should work like a
trail GPS; home page too basic (photos, more content); a map image on every card (trails, plans, groups, feed); plans layout confusing.

- AC13.6 (revised) FCMP marks exactly as the manual: caminho certo = two horizontal stripes (GR white over red, PR yellow over red,
  PL white over green); caminho errado = the two colours crossed; turn left / right = upper stripe straight, lower stripe bent down at
  the turning side. Placement facts: a mark at most every 250 m, turn marks up to 30 m before the junction, confirmation within 50 m after.
- AC13.7 (revised) The 112 button is removed (owner decision); "Share my position" stays.
- AC13.11 Trail GPS: instruction banner "In 120 m turn left / Continue 600 m / Arrive", arrow pointing the way, distance to the next
  turn, the route already walked drawn behind you, map follows at street zoom, optional voice prompts, next climb ahead.
- AC1.13 "Is this route for me?": route type in plain words (GR > 30 km or more than one day; PR ≤ 30 km in one day; PL mostly urban),
  distance and time class, and the climbs: total ascent, highest point, longest climb (where and how steep), steepest section,
  a climb rating (gentle / moderate / demanding / very demanding) with advice.
- AC1.14 Elevation profile chart for every trail (stored samples from the Open-Meteo Elevation API, migration 004).
- AC1.15 Real map image (OpenStreetMap tiles + route, start and end) on trail, plan, group-hike and feed cards; works without JS.
- AC2.5 Plans as an agenda: one row per hike with date block | map | trail and time | risk verdict | group info; tabs Upcoming / Joined / Past.
- AC8.3 Home: photo hero with the next hike and its verdict and countdown, your stats, trail photos to discover, group hikes, community with photos.

## 11. Release 5 — reviewer's list of 45 improvements (owner request, 2026-11-19)
Source: "TrailSense – Final List of Recommended Improvements" (owner upload). Items already met are mapped in docs/TRACEABILITY.md.
New acceptance criteria for the gaps:
- AC0.1 Public landing page (signed-out home): headline "Hike smarter. Explore safer. Share the experience.", the three pillars
  (discovery & planning · weather-informed decisions · community), what makes TrailSense different, Hiker vs Organizer benefits,
  real trail photos, sign-up / sign-in. Demo credentials are no longer shown publicly (item 35).
- AC1.16 Trail page weather: next 7 days for the trail start (icon, min/max °C, rain probability, max wind and gusts), the time it
  was fetched, and a link to plan on a chosen day. Plan hour-by-hour adds rain probability and wind speed (items 10, 11, 13).
- AC1.17 Filters: region, distance, difficulty and duration (item 8).
- AC1.18 Save trails (favourites, stored in the DB) and "recently viewed" (this session); saved trails on home and profile (items 6, 14, 20).
- AC1.19 Share: a share/copy-link button on trail pages and group hikes (items 14, 17).
- AC9.8 Organizer dashboard: my events with participants / places left / registration status, totals, quick "New group hike" (item 7).
- AC9.9 Organizers and hikers can edit a future plan (date, time, duration, group details) and cancel it; a group hike cannot shrink
  below the people already joined; the participant list is shown to the organizer (items 15, 16, 32).
- AC8.5 Change password on the account page (current password required) (items 30, 32, 35).
- AC8.6 Forms show a busy state while submitting; empty states suggest the next action (items 27, 28).
Out of scope still: following users (item 21 "if within MVP scope" — likes and comments cover it).

## 12. Release 6 — offline trail GPS (owner request, 2026-11-20)
- AC13.12 "Download for offline" on the hike screen saves, on the phone: the hike page with the route, turns and elevation,
  the app scripts and Leaflet, and the OpenStreetMap tiles around the route (zoom 13–16, a corridor of one tile around the line,
  at most ~600 tiles). Progress and the saved size are shown.
- AC13.13 Without signal, the hike page opens from the phone; position, turn-by-turn directions, compass, progress, off-route
  alert, altitude and voice keep working; the map stays at zoom ≤ 16 (what was saved). An "Offline" badge says so.
- AC13.14 "Finish" while offline keeps the hike (distance, time) on the phone and offers to publish it when back online.
- AC13.15 Signing out removes the saved pages from the phone (privacy); map tiles may stay (they are public).
