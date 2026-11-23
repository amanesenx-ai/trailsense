# MANUAL_TESTS.md — screen-by-screen check before submission

Checked on 2026-11-17 at 390×844 (phone) and 1280×800 (desktop), Chrome. Screenshots of that release in `docs/screenshots/archive/`; current screens in `docs/screenshots/a2/`.
Scripted extra checks at 360 px: no horizontal scroll on any of 15 pages, every input has a label, touch targets ≥ 44 px.

| # | Screen / flow | Steps | Expected | Result |
|---|---|---|---|---|
| M1 | Sign in | wrong password, then correct | generic error; then role home | ✅ |
| M2 | Register | empty submit; duplicate e-mail; valid | field errors; keeps values; welcome message | ✅ |
| M3 | Trails | filter Hard; ≤ 5 km; text "zzz"; clear | correct subset; empty message; reset | ✅ |
| M4 | Trail detail → Plan this hike | open Santa Justa, press button | form preselects trail, duration = estimate | ✅ |
| M5 | Plan form | past date; then valid | error next to field, values kept; saved → assessment | ✅ |
| M6 | Assessment | open a plan with rain | factor rows with badge text, rule code, condition, precaution | ✅ |
| M7 | Late start | start 15:30, 150 min, November | DAY_W warning + "Start by HH:MM" callout | ✅ |
| M8 | Beyond 16 days | plan in 30 days | notice, factors "Not checked", badge "Incomplete", daylight still checked | ✅ |
| M9 | Check again | press button | new assessment, flash message | ✅ |
| M10 | Organizer home | sign in as Bruno | Groups menu, KPI card, group cards | ✅ |
| M11 | Group plan + briefing | create, press Copy briefing | briefing text, "Briefing copied" status | ✅ |
| M12 | Security: other user's plan | Joana opens `r=plan&id=3` | 403 page | ✅ |
| M13 | Publish a hike | with photo, then fake .jpg | appears first in feed; fake rejected | ✅ |
| M14 | Feed / post | open a post | photo or route, stats, pace, notes | ✅ |
| M15 | Delete plan | delete, confirm dialog | plan gone, flash message | ✅ |
| M16 | Keyboard only | Tab through login and plan form | visible focus ring, logical order, skip link | ✅ |
| M17 | Online deployment | open https://amanesenx.alwaysdata.net | login page over HTTPS; http→https; logged-out pages redirect to login; 404 page; src/config/database not reachable | ✅ 2026-11-17 |
| M18 | Live forecast | sign in, create a plan within 16 days | source line shows "Forecast: Open-Meteo" | ⏳ check in the browser before the demo |

Security checklist (run with the security-reviewer subagent prompt + grep scan): authentication ✓ · authorisation ✓ · input validated ✓ · output encoded ✓ · secrets external ✓ · uploads non-executable ✓.

## Release 2 — checked on the live site (https://amanesenx.alwaysdata.net), 2026-11-18
| # | Check | Result |
|---|---|---|
| L1 | Deploy: push → scheduled `git pull` (≤ 5 min) → migration 002 runs once on the first request | ✅ after fixing the release-1 marker bug (see LESSONS) |
| L2 | `GET /api/v1/trails?difficulty=hard` → 3 trails; `/trails/4`, `/feed`, `/group-hikes` → 200 JSON | ✅ |
| L3 | `/trails/7/conditions` for tomorrow: **real Open-Meteo** forecast (gust 56 km/h → WIND_C CAUTION) and **real IPMA** (no warning, fire risk 1) | ✅ |
| L4 | Same check 10 days ahead: IPMA rules "pending", assessment still complete (AC4.9) | ✅ |
| L5 | Trail page on a phone: Leaflet loads (SRI accepted), OSM tiles load, Start + End markers, photos with author/licence | ✅ |
| L6 | Sticky "Plan this hike" button was covered by the map layers | ❌ → fixed (CSS isolation), re-checked ✅ |
| L7 | My report: OSM tiles show "403 Access blocked" | ❌ cause: `Referrer-Policy: same-origin` sent no Referer → fixed (strict-origin-when-cross-origin). From the live origin a tile returns the real 18 KB image; with no Referer, the 6.7 KB "blocked" image ✅ |
| M-R3-1 | Phone outdoors: Start hike → allow location → blue dot + accuracy circle, Follow me, km done/left update while walking | to do on a walk |
| M-R3-2 | iPhone: tap the compass → permission prompt → rose turns with the phone; Android: turns without a tap | to do on phones |
| M-R3-3 | Walk > 50 m away from the route → red banner, vibration, dashed line back to the route; back on route → banner clears | to do on a walk |
| M-R3-4 | Finish → Publish a hike with trail, today, distance and duration filled in | ✅ (automated + desktop) |
| M-R3-5 | Plan page 360 px: verdict, chart scrolls sideways, hour cards, 2-column tiles, checklist ticks survive reload | ✅ screenshots |
| M-R4-1 | Simulated GPS on Trilho dos Romanos: 70 m before the first turn → "In 70 m · turn left"; at 15 m → "Turn left now"; next climb "+151 m over 1.2 km" | ✅ (headless Chrome, simulated position) |
| M-R4-2 | Live: card maps show OSM tiles aligned with the route (trails, plans, groups, feed) | to check on the live site |
| M-R4-3 | Voice prompts on a phone (speech synthesis) | to do on a walk |
| M-R5-1 | Every screen at 360 px for hiker and organizer (home, trails, trail, plans, plan, edit, groups, feed, account, hike) — no horizontal scroll | ✅ (headless Chrome; fixed 2 overflows) |
| M-R5-2 | Landing page signed out; top bar shows Sign in / Create account | ✅ |
| M-R5-3 | Live: trail page 7-day forecast from Open-Meteo, "updated" time | to check on the live site |
| M-R6-1 | Offline (headless Chrome, network cut): download trail → reload with no network → page opens, "Offline" badge, "In 70 m · turn left" still shown; Finish offline → hike kept → "Publish it now" banner when back online | ✅ |
| M-R6-2 | Real phone: download a trail on Wi-Fi, airplane mode + GPS on, walk 200 m: map tiles shown up to zoom 16, directions update | to do on a walk |
| M-R6-3 | Avatar letter centred in the circle on desktop and phone | ✅ (measured 0 px offset) |

## M-R7 — iPhone photo (AC7.2 rev, AC7.5) — 20 Nov
| Step | Expected |
|---|---|
| On an iPhone, Feed → Publish a hike → Photo → choose a photo from the library (4–8 MB) | Message "✓ Photo ready (x MB → ~0.4 MB)" |
| Publish | Hike on top of the feed; photo upright, not sideways |
| Same on a desktop with a 10 MB JPEG and JavaScript disabled | Accepted; server resizes it |

## M-R8 — releases 7–8 (22 Nov), checked on the live site and in a browser
| Step | Expected | Result |
|---|---|---|
| Home (signed out) → section menu → "Trails" | Page scrolls to the section; the menu marks it | ✅ |
| Header at 360 px | "Sign in" and "Create account" on one line; dark text on yellow | ✅ |
| Footer → Sources and credits (signed out) | 22 sources with links in 4 groups | ✅ live |
| Organizer → event → edit the briefing → "Send on WhatsApp" | WhatsApp opens with the edited text | ✅ |
| Hike mode at home, before Start | Done 0.00 km, Left = whole trail | ✅ |
| Start at the trail start, walk a third, step off the route | 0.00 → ~1.4 km; numbers stop while off the route | ✅ |
| GitHub Actions | tests and screenshots workflows green | ✅ |
