# How I used generative AI in this project

## Tool
- **Claude** (Anthropic), model **Claude Opus 5.5**, used as a coding agent with access to this repository, a terminal,
  web search and a browser. Period: 17–23 November 2026.
- No AI is used **inside** the product: TrailSense's risk check is a set of explicit rules stored in the database
  (ADR-003), and all data comes from Open-Meteo, IPMA and OpenStreetMap.

## What I did
**Product and scope**
- Defined the product, the personas and the user stories (Assignment 1), and decided the core of the MVP and what stays out.
- Decided every later release and change of scope (SPEC.md — changes to scope are made there first, by me), for example:
  real OpenStreetMap trails with photos and a real map; IPMA warnings and fire risk; a GPS for the trail with directions,
  compass and trail marks; offline use; saved trails, filters and an organizer dashboard.
- Asked for the trail marks to follow the official reference (Rota Vicentina / FCMP manual), for short and long routes (PR/GR)
  and the climbs to be explained for first-timers, and for the 112 button to be removed.
- Asked for the evidence, the labelled assumptions, the comparison with AllTrails, komoot and Wikiloc, and the list of
  external services (docs/VALUE_CASE.md), and for every user story to have its benefit ("so that").

**Design and user experience**
- Reviewed every screen and gave the feedback behind each UX round (docs/UX_REVIEW.md): a richer home page with photos,
  maps on every card, a clearer plans layout, a more visual risk check, dropdown sections, the dark green style, the
  account menu, readable buttons, a section menu and less text on the home page.
- Brought in an external list of 45 recommended improvements and the final five points for the home page, and decided
  which to apply.
- Collected feedback on the home page and the sign-in pages from other people and asked for the changes.

**Testing and checking**
- Tested the app on the live site and on my phone (iPhone photos, GPS, offline) and reported what was wrong — see
  "Errors the AI made" below.
- Asked for a critical review of the work against the assignment criteria and the data model, and for the fixes.

**Users and evidence**
- Ran 7 interviews with hikers and club organizers (docs/research/) and decided what to build from them
  (editable briefing, "Send on WhatsApp").

**Delivery**
- Decided the content of the report and of the repository, reviewed the final documents, and present the live demo.

## What the agent did, under my direction
- Wrote the code, the tests and the database schema, following SPEC.md and the rules in CLAUDE.md.
- Drafted the documentation (SPEC.md sections, ARCHITECTURE.md, DECISIONS.md, README.md, UX review, report) from my
  requests; I reviewed it and asked for changes.
- Searched the web for the evidence and the comparison with existing apps (docs/VALUE_CASE.md); every number has a
  link to its source.
- Built the report PDF and captured the screenshots (on GitHub Actions).
- The interview notes were written up from my notes with help from an AI assistant; the answers are summarised, not
  word-for-word (docs/research/README.md).

## How I checked the output
- **Tests:** every change was accepted only with `php tests/run.php` → `FAILED: 0` (174 automated tests, also run on
  GitHub Actions on every push).
- **By hand:** screens checked at 360 px and on desktop (docs/MANUAL_TESTS.md, docs/UX_REVIEW.md).
- **Sources:** facts in VALUE_CASE.md and on the "Sources and credits" page have a link to the original source.
- **Traceability:** each commit names the acceptance criterion it implements; docs/TRACEABILITY.md maps requirements to
  code and tests.

## Errors the AI made, and how they were caught
- When I started a hike, the map was not a real map, only a static picture — found by me, replaced by a live map with
  my position, compass and turn-by-turn directions (releases 3–4).
- Trail marks drawn the wrong way round (turn left/right swapped) — found by me on the trail page.
- A light colour scheme applied without asking first — rejected by me, reverted.
- Old styles served from the offline cache, so menus looked broken — found by me, fixed.
- Hike mode counted kilometres before the hike started — found by me on my phone, fixed with tests (AC13.16).
- A broken JavaScript line that stopped the GPS page — found in a browser check; a syntax test was added for all scripts.
- On a straight stretch the "Done" distance could lag up to 40 m behind — found in a browser check of the practice route, fixed with a test.
- The report marked each column of a combined key as UNIQUE — found in a review, fixed.
- A YAML error that silently stopped GitHub Actions — found when checking the repository, fixed.

## Limitations
- The same request can give different code or text each time; the tests, the SPEC and the decisions are what make the
  result reproducible and checkable.
- The agent can be confidently wrong; nothing was accepted on its word alone (tests, my checks, linked sources).
- The prompts themselves are not published; the requests are reflected in SPEC.md (one section per release), in the
  commit messages and in docs/UX_REVIEW.md.

## Responsibility
I am responsible for the content of this project and of the report. The agent is a tool; commits made with it are
marked "Co-Authored-By: Claude" so that the way the work was done stays visible.
