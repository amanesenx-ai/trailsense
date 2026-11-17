# MANUAL_TESTS.md — screen-by-screen check before submission

Checked on 2026-11-17 at 390×844 (phone) and 1280×800 (desktop), Chrome. Screenshots in `docs/screenshots/`.
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
| M17 | Live forecast (online server) | create plan within 16 days on the deployed app | source line shows "Open-Meteo" | ⏳ do on the FEUP server (sandbox had no internet) |

Security checklist (run with the security-reviewer subagent prompt + grep scan): authentication ✓ · authorisation ✓ · input validated ✓ · output encoded ✓ · secrets external ✓ · uploads non-executable ✓.
