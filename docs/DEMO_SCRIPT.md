# DEMO_SCRIPT.md — 5-minute live demo

Before: `php bin/db-reset.php` on the server (fresh, realistic data; plans are relative to today). Two tabs: phone-sized window (DevTools 390 px) and desktop.

| Time | Show | Say |
|---|---|---|
| 0:00–0:30 | Login page | "TrailSense helps beginners and club organizers plan safer hikes. A1 promised: discover, prepare, record, connect. This is the working MVP: PHP + PostgreSQL, online at …" |
| 0:30–1:15 | Sign in as **Joana** → hiker home → Trails, filter *Moderate, ≤ 10 km* → Serra de Santa Justa | "Role-specific home. Filters combine and live in the URL." |
| 1:15–2:30 | *Plan this hike* → date in 3 days, **start 15:30**, save | "The plan is saved, the forecast for exactly those hours is fetched from Open-Meteo and every rule in the rule table is evaluated. Each warning says **which rule fired**, the observed value and **what to do** — here: *Start by HH:MM or choose a shorter trail*." Open *See every rule that was checked*. |
| 2:30–3:00 | Plan 30 days ahead | "No forecast yet: we never invent data — it is marked *incomplete*, daylight is still checked." |
| 3:00–3:50 | Sign in as **Bruno** → organizer home (KPI card) → New group hike → **Copy briefing** | "Organizers see Groups and the planning-completeness KPI from my A1 business case. The briefing is generated from the same assessment — same checks every time." |
| 3:50–4:30 | Feed → Publish a hike with a photo → appears first | "Record and connect. Photo type is checked by content." |
| 4:30–5:00 | Terminal: `php tests/run.php` → `PASSED: 66 FAILED: 0`; show CLAUDE.md, SPEC.md, docs/TRACEABILITY.md | "How I worked with the agent: architecture and spec first, rules in CLAUDE.md, every change accepted only with tests." |

Backup if Wi-Fi fails: screenshots in docs/screenshots/.
