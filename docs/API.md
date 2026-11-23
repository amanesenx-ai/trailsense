# TrailSense public API (v1)

Read-only JSON API so other applications can reuse TrailSense data and its risk check (ADR-013).
Base URL: `https://amanesenx.alwaysdata.net/api/v1` · Machine-readable spec: [openapi.yaml](openapi.yaml)

- No authentication, CORS open (`Access-Control-Allow-Origin: *`), GET only (other methods → 405).
- Errors: `{"error": {"status": 400, "message": "..."}}` with the matching HTTP status (400, 404, 405, 500).
- No personal data: no e-mail addresses, no private plans.
- Fallback URL if a server cannot rewrite paths: `/index.php?r=api&path=/trails/4`.
- Data sources: routes © OpenStreetMap contributors (ODbL), forecast Open-Meteo, warnings and fire risk IPMA, photos Wikimedia Commons (licence per photo).

| Method & path | Description |
|---|---|
| `GET /api/v1` | Index: endpoints and sources |
| `GET /api/v1/trails?q=&difficulty=easy\|moderate\|hard&max_km=` | Trails with distance, ascent, estimated time, start and end points |
| `GET /api/v1/trails/{id}` | One trail + GeoJSON `LineString` route + photos (author, licence) + recent condition reports |
| (release 4) `GET /api/v1/trails/{id}` also returns `route_type`, `climb` {ascent, per_km, highest, longest, steepest, rating, advice} and `elevation_profile` [{km, m}] | |
| `GET /api/v1/trails/{id}/conditions?date=YYYY-MM-DD&start=HH:MM&duration=MIN` | Live risk check for that hike window: Open-Meteo forecast, IPMA warnings + fire risk, daylight, community reports → rules. **Not stored.** `duration` defaults to the trail estimate, `start` to 09:00. Release 3 adds `verdict` {key, title, reason}, `forecast.hourly` (hike window ± 2 h with WMO `code` + `description`) and `sources` {api, page, ipma} — the exact Open-Meteo request and IPMA page used |
| `GET /api/v1/group-hikes` | Upcoming group hikes with places left and current risk level |
| `GET /api/v1/feed?limit=1..50` | Latest published hikes |

## Example
```bash
curl "https://amanesenx.alwaysdata.net/api/v1/trails/7/conditions?date=2026-11-21&start=10:00&duration=120"
```
```json
{
  "data": {
    "trail": {"id": 7, "name": "Trilho da Preguiça", "link": "https://amanesenx.alwaysdata.net/index.php?r=trail&id=7"},
    "window": {"date": "2026-11-21", "start": "10:00", "end": "12:00", "duration_min": 120},
    "overall": "caution", "complete": true, "sunset": "17:10", "latest_safe_start": "14:40",
    "factors": [
      {"factor": "rain", "label": "Rain", "status": "caution", "observed": "3.1 mm",
       "rule": {"code": "RAIN_C", "name": "Rain expected during the hike", "condition": "Total rain ≥ 2 mm", "level": "caution"},
       "precaution": "Pack a waterproof jacket; rocks and trails may be slippery."}
    ],
    "forecast": {"source": "open-meteo", "available": true, "precip_total_mm": 3.1, "gust_max_kmh": 28, "temp_min_c": 7, "temp_max_c": 12},
    "official": {"source": "ipma", "warning_level": 0, "warnings": [], "fire_risk": null, "message": "Fire risk is published for today and tomorrow."}
  }
}
```
(values illustrative)

Factor `status`: `ok`, `caution`, `warning`, `unknown` (required data missing → `complete: false`), `pending` (IPMA data not published yet for that date; advisory, does not affect `complete`).
