# UX_REVIEW.md — LLM-assisted UI review vs Assignment 1 mockups

The LLM was asked to review every screen (screenshots at 390 px and 1280 px) for hierarchy, contrast, spacing,
consistency, accessibility and phone behaviour, using `.claude/agents/ux-reviewer.md`. Findings were then verified with scripts.

## Findings and what changed
| # | Finding | Change | Verified by |
|---|---|---|---|
| 1 | Organizer home showed "NOT CHECKED" on plans until each was opened — looked unfinished and contradicted "the same checks every time". | Home runs the check automatically for unchecked upcoming plans. | Screenshot m-home-org |
| 2 | Daylight value "62 min" was ambiguous. | Reads "ends 62 min before sunset / after sunset". | Unit test *daylight value reads as before/after sunset* |
| 3 | Durations shown as "1 h 10". | "1 h 10 min" everywhere. | Screens |
| 4 | Logo and "← back" links were 18–24 px tall on phone. | min-height 44 px. | Script: 0 small targets on 15 pages |
| 5 | Risk levels must not rely on colour (A1 used coloured pills). | Badges always carry text (OK / CAUTION / WARNING / NOT CHECKED); incomplete also has a dashed border. | Manual |
| 6 | Contrast of muted text and badges. | Palette tuned; all 13 text/background pairs ≥ 6:1 (AA needs 4.5). | Script below |
| 7 | Desktop: phone layout stretched. | Top nav at ≥ 900 px, 3-column trail and feed grids, tab bar hidden. | d-*.png screenshots |
| 8 | Plan page: users could not see *all* rules, only triggered ones (transparency promise from A1). | "See every rule that was checked" table. | Manual M6 |

Contrast (WCAG): text/bg 14.1 · text/card 11.5 · muted/card 7.8 · muted/factor row 6.5 · ink/yellow button 10.7 · yellow link/card 7.6 · badge OK 10.5 · CAUTION 10.0 · WARNING 8.0 · NOT CHECKED 10.4 · error/card 6.1 · input 15.0.

## Comparison with the A1 mockups
| A1 mockup | MVP | Note |
|---|---|---|
| Dark green theme, yellow primary button, "TrailSense" logo | Same palette and hierarchy | kept |
| Hiker home: "Hi, Joana! Hiker", next hike card with CAUTION, "Plan a hike", community list | Implemented as drawn | kept |
| Organizer home: "Hi, Bruno! Organizer", group hikes, "New group hike" | Implemented + **planning-completeness KPI** card | improvement: makes the A1 KPI measurable |
| Bottom bar: search · Plan pill · check · avatar | Trails · Plans/Groups · Feed · Me with labels | labels added for clarity |
| Risk assessment over a map with Rain/Wind/Daylight bubbles | List of factor rows with badge, value, rule and precaution, plus route drawing | more readable on 360 px; adds the rule (US4 AC) |
| "Start by 13:30 or go shorter." | Yellow callout computed from sunset, duration and 30-min buffer | kept, now calculated |
| Share route with distance, time, pace | Post page with route, distance, moving time, avg pace, date | likes/comments are P1 |
| Map tiles | Simplified SVG route + "Open in OpenStreetMap" | ADR-006 |
