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

## (resolved 22 Nov) hike.js was 307 lines — offline download moved to hike-offline.js (70 lines); hike.js is now 245.

## D-? Demo accounts share the seed password with the public test suite — 22 Nov
The tests (public repo) log in with the seed password, so anyone could sign in to the demo accounts on the live site.
Action for the owner: change the demo accounts' passwords on the live site (Profile → Change password). Tests keep using the local seed.
