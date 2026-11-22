# DEBT.md — known technical debt / next steps
- Login rate limiting / lockout after repeated failures.
- Password reset by e-mail.
- Cache Open-Meteo answers per trail/day (the 7-day outlook is cached 1 h since release 5; the hike-window check is not).
- ~~Edit plan~~ — done in release 5 (AC9.9).
- Admin screen to edit risk_rule thresholds (now: SQL).
- ~~Image resizing~~ — done (PhotoResizer + browser resize, AC7.5).
- Remaining A1 Priority 1: following users, notifications, challenges (GPS directions done in the browser, release 3/6; recording a track to the server is still out).
- ~~Elevation profile chart~~ — done in release 4 (ADR-019).
- Coastal trail: the Matosinhos–Vila Chã relation is a super-relation; support nested relations in the generator.
- API keys + rate limiting if the API gets external traffic.

## (resolved 22 Nov) hike.js was 307 lines — offline download moved to hike-offline.js (70 lines); hike.js is now 245.

## D-? Demo accounts share the seed password with the public test suite — 22 Nov
The tests (public repo) log in with the seed password, so anyone could sign in to the demo accounts on the live site.
Action for the owner: change the demo accounts' passwords on the live site (Profile → Change password). Tests keep using the local seed.

## Backlog from user interviews (22 Nov)
- Event page visible to participants without an account (2 organizers).
- Waiting list when a group hike is full (2 organizers).
- Points of interest along the trail (1 hiker).
