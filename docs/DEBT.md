# DEBT.md — known technical debt / next steps
- Interactive tile map (Leaflet + OSM) instead of SVG route (ADR-006).
- Login rate limiting / lockout after repeated failures.
- Password reset by e-mail.
- Cache Open-Meteo answers per trail/day to avoid repeated calls when many users check the same trail.
- Edit plan (now: delete and create again).
- Admin screen to edit risk_rule thresholds (now: SQL).
- Image resizing/thumbnails for uploaded photos.
- P1 features from SPEC §3: GPS tracking, likes/comments, profiles, notifications.
