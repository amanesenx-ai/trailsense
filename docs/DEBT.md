# DEBT.md — known technical debt / next steps
- Login rate limiting / lockout after repeated failures.
- Password reset by e-mail.
- Cache Open-Meteo answers per trail/day to avoid repeated calls when many users check the same trail.
- Edit plan (now: delete and create again).
- Admin screen to edit risk_rule thresholds (now: SQL).
- Image resizing/thumbnails for uploaded photos.
- Remaining A1 Priority 1: automatic GPS tracking, following users, notifications, challenges.
- Elevation profile chart per trail (data is already sampled in the generator).
- Coastal trail: the Matosinhos–Vila Chã relation is a super-relation; support nested relations in the generator.
- API keys + rate limiting if the API gets external traffic.

## D-? public/assets/hike.js is 307 lines (rule: ~250) — 22 Nov
The pure route maths already lives in hike-core.js (tested). The rest is screen wiring (map, banner, voice, offline download).
Plan: move the offline download into `hike-offline.js` when that part is next changed. app.css is one file by design (stack rule: "one CSS file").
