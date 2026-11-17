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
Automatic GPS tracking (hikes are entered manually), follow/like/comment, profiles and statistics pages,
notifications, challenges and achievements, password reset by e-mail, interactive tile maps, trail CRUD by users
(trails are curated seed data), native mobile app, multiple languages.

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
