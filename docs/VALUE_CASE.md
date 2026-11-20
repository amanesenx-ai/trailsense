# TrailSense — value case: evidence, assumptions, comparison, external parties

Written in response to the teachers' general feedback on Assignment 1 (2026-11-20). Rule used in this document:
**every number has a source; anything without a source is labelled ASSUMPTION, with how we would validate it.**

## 1. The problem, with evidence

| # | Claim | Evidence | Source |
|---|---|---|---|
| E1 | Accidents and rescues on Portuguese trails are frequent | GNR: more than 400 protection and rescue operations in mountain areas since 2024; the Serra da Estrela mountain search-and-rescue post alone recorded 138 interventions in 2024 and 115 in 2025 | 24noticias / SAPO, "GNR regista mais de 400 intervenções em áreas montanhosas desde 2024" (2026) |
| E2 | The problem is not only in mountains | Madeira's Regional Civil Protection Service counted 68 accidents on levadas and walking routes by mid-August 2022 | DNoticias.pt, 15 Aug 2022 |
| E3 | Responders ask hikers to check conditions and difficulty before going | After a ~10 h rescue in Gerês, the responders advised everyone to check the conditions and the difficulty of the trail before starting | Notícias ao Minuto, "Mulher resgatada em trilho no Gerês" |
| E4 | Weather, daylight and trail choice are factors in rescues | Madeira rescue procedures consider GPS coordinates, recommended vs not-recommended trails, the weather and the daylight left | DNoticias.pt, "Saiba como decorre um resgate nas serras da Madeira" (2023) |
| E5 | Fire risk can legally close the trail | In alert periods, the Government has prohibited access to and circulation in forest areas (Decreto-Lei 82/2021), when much of the mainland was at fire risk Elevated, Very high or Maximum | Despacho of the alert situation, July 2022 (e.g. coimbra.pt, ifap.pt copies) |
| E6 | Official warnings and fire risk are spread over separate apps | Avisos@IPMA gives weather warnings for the next 72 h; Fogos.pt shows active fires; neither knows your trail or time | Notícias ao Minuto, "Incêndios em Portugal: as 3 apps que não podem faltar" |
| E7 | The market is large and organised by many promoters | FCMP: 8,885 km of Pequenas Rotas and 4,386 km of Grandes Rotas homologated, by 382 promoting entities in 273 municipalities | e-konomista.pt citing FCMP |
| E8 | Trail marks follow rules a beginner does not know | Marks at most every 250 m; turn marks up to 30 m before a junction; confirmation within 50 m after | Rota Vicentina, "Marcação, Sinalética e Manutenção" manual (2021), pp. 26–27 |
| E9 | There is demand for a platform where users report trail problems | A parliamentary group in Madeira proposed "a platform where users and tourism professionals report risk situations on the routes" | DNoticias.pt, 10 Feb 2026 |
| E10 | One well-known route alone brings tens of thousands of walkers | Rota Vicentina estimated about 24,000 walkers in 2017 | Rota Vicentina blog, monitoring 2022 |

What the evidence does **not** show (and we do not claim): how many accidents were caused by weather or darkness specifically, or how many would have been avoided with an app. That link is an assumption (A1 below).

## 2. Assumptions (not facts) and how we will validate them

| # | Assumption | Why we believe it | How we validate it | Status |
|---|---|---|---|---|
| A1 | Beginners do not combine the forecast, IPMA warnings, fire risk and sunset before a hike | E3, E6: advice exists, sources are separate | 5 short interviews with beginner hikers (guide in §6) + in-app: share of plans whose user opens the risk check | **1/5 done** — Hiker 1 (experienced) checks the forecast only, no IPMA/fire/sunset mentioned; partly supports. Still need beginners |
| A2 | A clear verdict + precaution changes behaviour (bring X, start earlier, change day) | Rules + precautions are concrete | Interview question "what would you do with this screen?"; in-app: plans edited after a CAUTION/WARNING | To do |
| A3 | Club organizers spend time rewriting weather info for the group | Common practice in chats (to confirm) | Ask 2 club organizers how they brief today and how long it takes; time them with the briefing button | To do — no time figure is claimed |
| A4 | Organizers want every group hike checked before it goes ahead (K1) | Duty of care for groups | K1 indicator in the app: % of upcoming group hikes with a complete, current check. Baseline = first month of real use; no target is claimed before that | To do |
| A5 | Municipalities / promoters would read community trail reports | E9 shows a public proposal for such a platform | Contact 1 promoter (e.g. a municipality with PRs) and show the reports + the public API | To do |

No percentage, hours saved or reduction is claimed anywhere in the project until these validations are done.

## 3. Organizational and societal value — the mechanism, not just the claim

| For whom | What changes (mechanism) | How we measure it (in the app, already built) |
|---|---|---|
| Hiking clubs | One shared check per event, a briefing generated from it, sign-ups counted | K1 = upcoming group hikes fully checked; sign-ups per event; briefings copied |
| Trail promoters / municipalities (E7, E9) | Hikers report closures and obstacles; reports are dated and visible on the trail and through the public API | Reports per trail per month; age of the latest report; API calls |
| Hikers | Decision before leaving: verdict + reason + what to bring; directions and marks on the trail | Plans with a complete check; plans changed after a warning; offline downloads |
| Society / civil protection (E1, E5) | Fewer people walking into a red warning or a fire-risk closure without knowing | Not measurable by us alone — would need data from GNR/ANEPC; stated as a hypothesis, not a result |

## 4. Comparison with existing solutions

| | AllTrails | komoot | Wikiloc | Avisos@IPMA / Fogos.pt | **TrailSense** |
|---|---|---|---|---|---|
| Trails, maps, GPS | Yes (offline maps and wrong-turn alerts in paid tiers) | Yes, turn-by-turn voice navigation, offline maps | Yes, GPS navigation with off-track alerts (Premium) | No | Yes, for curated Portuguese trails; offline download |
| Weather | Paid "Trail Conditions" combining 15 weather factors | Weather on route (Premium) | Weather forecast (Premium) | Official warnings (IPMA) / active fires | Hour-by-hour forecast for **your** hike window |
| Official PT warnings + fire risk + sunset in one check | No | No | No | Separate apps, not tied to a trail or time | **Yes, one verdict with the rule and the precaution** |
| Explains PR/GR marks and climbs for beginners | No | No | No | No | **Yes** |
| Club organizer tools (events, sign-ups, briefing) | No | No | No | No | **Yes** |

Sources: AllTrails press release "AllTrails expands membership offering with AllTrails Peak" (May 2025); komoot Premium page; Wikiloc App Store page; Notícias ao Minuto (E6).
**Honest limit:** the big apps have far more trails, native apps and better offline maps. TrailSense competes on the *decision and safety layer for Portugal* and on *club organisation*, not on map coverage.

## 5. External parties the project depends on

| Party | Used for | Terms / limits we follow | If it fails |
|---|---|---|---|
| Open-Meteo (forecast API) | Hourly forecast, 7-day outlook | Free, non-commercial; no key; one request per check, outlook cached 1 h | Check marked "incomplete", never "OK" |
| Open-Meteo (elevation API) | Elevation profiles | Fetched once, stored in the DB (migration 004) | Profiles already stored |
| IPMA open data | Weather warnings, rural fire risk | Public JSON; published ~3 days / 2 days ahead | "Not published yet" / rest of the check still works |
| OpenStreetMap (data + tiles) | Routes (relations), map tiles | ODbL attribution shown; tile policy: Referer sent, no bulk download, offline ≤ zoom 16 near the route | SVG route drawing instead of the map |
| OpenTopoMap, Waymarked Trails | Optional map layers | Attribution shown | Layer simply not loaded |
| Wikimedia Commons | Trail photos | Author + licence shown for every photo | Route drawing instead of the photo |
| unpkg (Leaflet 1.9.4) | Map library | Pinned version with SRI hash | SVG route drawing |
| alwaysdata | Hosting (PHP + PostgreSQL) | Free plan; auto-deploy from GitHub every 5 min | — |
| GitHub | Code, CI | Public repository, no secrets in it | — |
| AI | **Not used inside the product.** Claude (LLM) was used only to develop it, under SPEC.md / CLAUDE.md, with every change tested. | — | — |

## 6. Talking to real users — interview guide (to run before the final presentation)

Goal: 5 conversations (3 beginner/occasional hikers, 2 club organizers), 15 minutes each, notes kept in `docs/research/`.

1. Tell me about the last walk you did. How did you choose the trail and the day?
2. What did you check before leaving? Where? (don't suggest apps)
3. Has the weather, the dark or a closed path ever surprised you on a trail? What happened?
4. (Show the risk check of a real plan) What does this screen tell you? What would you do?
5. Would you change anything in your plan because of it? What?
6. Organizers: how do you brief your group today, and how long does it take? (then show the briefing)
7. What would make you not use TrailSense?

**Done:** 1/5 — `docs/research/2026-11-20-hiker-1.md`. Finding outside our assumptions: an experienced hiker builds the difficulty picture herself (Google + elevation) and would value points of interest along the trail (caves, fossils) — logged as backlog, not MVP scope.

Record for each person: profile, answers, the one thing that surprised you. Update §2 "Status" with what you learned —
including when an assumption turns out to be wrong.
