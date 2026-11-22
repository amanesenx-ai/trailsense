# 5-minute live demo — both journeys (updated 22 Nov, release 7)

Use two browser windows: a hiker account and an organizer account (demo accounts are given to the teachers privately).

| Time | Who | What to show | What to say |
|---|---|---|---|
| 0:00–0:30 | Signed out | Landing page + section menu | "Hike smarter, explore safer, share the experience. A clear go/no-go for *your* hike with official IPMA data — not just another trails app." |
| 0:30–1:30 | Hiker | Trails → filter Region + ≤ 3 h → open a trail | Photos, real map, 7-day weather (rain chance, wind, updated time), "Is this route for me?" (PR explained, climbs), FCMP marks. ☆ Save trail, Share. |
| 1:30–2:30 | Hiker | "Plan this day" → save → risk check | Verdict ("Go prepared" + reason), hour by hour, what to bring, the rule behind each factor, link to the raw Open-Meteo data and IPMA. |
| 2:30–3:00 | Hiker | Home | Next hike hero with countdown, saved trails, recently viewed, group hikes to join → Join. |
| 3:00–4:15 | Organizer | Dashboard → event → Edit → Share invite link | Sign-ups 2/8, places left, status, weather per event, briefing to copy; edit time → weather re-checked; cannot shrink below people joined. |
| 4:15–4:45 | Hiker | Start hike (phone) | GPS banner "In 70 m · turn left", compass, progress; Finish → publish pre-filled → feed. |
| 4:45–5:00 | — | Repo | SPEC.md, CLAUDE.md, 17 tables, 166 tests passing, app online + public API. |

## Before the demo (checklist)
- **Fresh data.** The demo plans were created relative to the day the database was filled, so they age. The day before,
  create one personal plan (hiker) and one group hike (organizer) a few days ahead — or check `/api/v1/group-hikes` lists one.
- **GPS step at home.** Away from the trail the banner says "Go to the start: 35 km", not "In 70 m · turn left". Either say so
  ("it first guides you to the start"), or in Chrome DevTools → ⋮ → More tools → Sensors → Location, set the trail start
  (e.g. 41.16761, -8.05072 for Trilho dos Dólmens) to show the real directions. Keep the GPS screenshot on the landing as a backup.
- Hard refresh (Ctrl+Shift+R) once so the browser has the latest CSS.
