<?php
declare(strict_types=1);

namespace TrailSense\Api;

use DateTimeImmutable;
use TrailSense\Core\Database;
use TrailSense\Domain\WeatherCode;
use TrailSense\Domain\PlanValidator;
use TrailSense\Repositories\ActivityRepository;
use TrailSense\Repositories\ParticipantRepository;
use TrailSense\Repositories\RuleRepository;
use TrailSense\Repositories\TrailReportRepository;
use TrailSense\Repositories\TrailRepository;
use TrailSense\Services\AssessmentService;

/** Read-only API resources. Output never contains e-mails or other private data (AC12.2). */
final class Endpoints
{
    private TrailRepository $trails;

    public function __construct()
    {
        $this->trails = new TrailRepository(Database::connection());
    }

    public function index(): array
    {
        return [
            'name' => 'TrailSense API', 'version' => 'v1', 'docs' => self::link('/docs'),
            'endpoints' => [
                'GET /api/v1/trails?q=&difficulty=easy|moderate|hard&max_km=',
                'GET /api/v1/trails/{id}',
                'GET /api/v1/trails/{id}/conditions?date=YYYY-MM-DD&start=HH:MM&duration=MINUTES',
                'GET /api/v1/group-hikes',
                'GET /api/v1/feed?limit=1..50',
            ],
            'sources' => ['OpenStreetMap contributors (routes, ODbL)', 'Open-Meteo (forecast)', 'IPMA (official warnings, fire risk)'],
        ];
    }

    public function trails(array $q): array
    {
        $difficulty = in_array($q['difficulty'] ?? '', TrailRepository::DIFFICULTIES, true) ? $q['difficulty'] : null;
        if (isset($q['difficulty']) && $q['difficulty'] !== '' && $difficulty === null) {
            throw new ApiError(400, 'difficulty must be easy, moderate or hard.');
        }
        $maxKm = null;
        if (isset($q['max_km']) && $q['max_km'] !== '') {
            if (!is_numeric($q['max_km']) || (float) $q['max_km'] <= 0) {
                throw new ApiError(400, 'max_km must be a positive number.');
            }
            $maxKm = (float) $q['max_km'];
        }
        $rows = $this->trails->search(mb_substr((string) ($q['q'] ?? ''), 0, 60), $difficulty, $maxKm);
        return ['count' => count($rows), 'data' => array_map([$this, 'trailSummary'], $rows)];
    }

    public function trail(int $id): array
    {
        $t = $this->trails->find($id) ?? throw new ApiError(404, 'Trail not found.');
        $reports = (new TrailReportRepository(Database::connection()))->recent($id);
        return ['data' => $this->trailSummary($t) + [
            'description' => $t['description'],
            'route' => ['type' => 'LineString', 'coordinates' => array_map(fn ($p) => [$p[1], $p[0]], $t['route_points'])],
            'photos' => array_map(fn ($p) => ['url' => $p['thumb_url'], 'author' => $p['author'], 'license' => $p['license'],
                'source' => $p['source_url'], 'alt' => $p['alt_text']], $this->trails->photos($id)),
            'recent_reports' => array_map(fn ($r) => ['condition' => $r['condition'], 'note' => $r['note'],
                'reported_at' => $r['created_at']], $reports),
        ]];
    }

    /** Same risk check as the app, computed on the fly and not stored. */
    public function conditions(int $id, array $q): array
    {
        $t = $this->trails->find($id) ?? throw new ApiError(404, 'Trail not found.');
        $date = PlanValidator::date((string) ($q['date'] ?? ''));
        if ($date === null) {
            throw new ApiError(400, 'date is required (YYYY-MM-DD).');
        }
        if ($date < new DateTimeImmutable('today')) {
            throw new ApiError(400, 'date cannot be in the past.');
        }
        $start = PlanValidator::time((string) ($q['start'] ?? '09:00'));
        if ($start === null) {
            throw new ApiError(400, 'start must be HH:MM.');
        }
        $duration = (string) ($q['duration'] ?? $t['est_duration_min']);
        if (!ctype_digit($duration) || (int) $duration < 30 || (int) $duration > 720) {
            throw new ApiError(400, 'duration must be 30–720 minutes.');
        }

        $plan = ['id' => 0, 'trail_id' => $id, 'planned_date' => $date->format('Y-m-d'), 'start_time' => $start,
            'duration_min' => (int) $duration, 'start_lat' => $t['start_lat'], 'start_lon' => $t['start_lon'],
            'ipma_area' => $t['ipma_area'], 'coastal' => $t['coastal']];
        $db = Database::connection();
        $reports = new TrailReportRepository($db);
        $service = new AssessmentService(AssessmentService::providerFromConfig(), new RuleRepository($db), null,
            AssessmentService::alertsFromConfig(), fn (int $tid) => $reports->closedRecently($tid));
        $c = $service->compute($plan);
        $vm = AssessmentService::present($plan, AssessmentService::asAssessment($c));

        return ['data' => [
            'trail' => ['id' => $id, 'name' => $t['name'], 'link' => self::link('/trail', $id)],
            'window' => ['date' => $plan['planned_date'], 'start' => $start, 'end' => $vm['end_time'], 'duration_min' => (int) $duration],
            'overall' => $vm['overall'], 'complete' => $vm['complete'],
            'verdict' => $vm['verdict'],
            'sunset' => $vm['sunset'], 'latest_safe_start' => $vm['latest_start'],
            'factors' => array_values(array_map(fn ($f) => [
                'factor' => $f['factor'], 'label' => $f['label'], 'status' => $f['status'], 'observed' => $f['observed_text'],
                'rule' => $f['rule'] ? ['code' => $f['rule']['code'], 'name' => $f['rule']['name'], 'condition' => $f['condition'],
                    'level' => $f['rule']['level']] : null,
                'precaution' => $f['precaution'],
            ], $vm['factors'])),
            'forecast' => ['source' => $c['forecast']->source, 'available' => $c['forecast']->available, 'message' => $c['forecast']->message,
                'precip_total_mm' => $c['forecast']->precipTotalMm, 'gust_max_kmh' => $c['forecast']->gustMaxKmh,
                'temp_min_c' => $c['forecast']->tempMinC, 'temp_max_c' => $c['forecast']->tempMaxC,
                'hourly' => array_map(fn ($h) => $h + ['description' => WeatherCode::describe($h['code'])[0]], $c['forecast']->hours)],
            'sources' => $vm['links'],
            'official' => ['source' => $c['alerts']->source, 'warning_level' => $c['alerts']->warningLevel,
                'warnings' => $c['alerts']->warnings, 'fire_risk' => $c['alerts']->fireRisk, 'message' => $c['alerts']->message],
        ]];
    }

    public function groupHikes(): array
    {
        $rows = (new ParticipantRepository(Database::connection()))->upcomingGroupHikes(0, null, 50);
        return ['count' => count($rows), 'data' => array_map(fn ($h) => [
            'id' => (int) $h['id'], 'date' => $h['planned_date'], 'start' => substr((string) $h['start_time'], 0, 5),
            'trail' => ['id' => (int) $h['trail_id'], 'name' => $h['trail_name'], 'distance_km' => (float) $h['distance_km'],
                'difficulty' => $h['difficulty']],
            'organizer' => $h['organizer_name'], 'meeting_point' => $h['meeting_point'],
            'meeting_time' => substr((string) $h['meeting_time'], 0, 5),
            'places' => (int) $h['group_size'], 'places_left' => max(0, (int) $h['group_size'] - (int) $h['participants']),
            'risk_level' => $h['overall_level'],
        ], $rows)];
    }

    public function feed(array $q): array
    {
        $limit = (string) ($q['limit'] ?? '20');
        if (!ctype_digit($limit) || (int) $limit < 1 || (int) $limit > 50) {
            throw new ApiError(400, 'limit must be 1–50.');
        }
        $rows = (new ActivityRepository(Database::connection()))->feed((int) $limit);
        return ['count' => count($rows), 'data' => array_map(fn ($a) => [
            'id' => (int) $a['id'], 'author' => $a['author'], 'trail' => ['id' => (int) $a['trail_id'], 'name' => $a['trail_name']],
            'hiked_on' => $a['hiked_on'], 'distance_km' => (float) $a['distance_km'], 'duration_min' => (int) $a['duration_min'],
            'notes' => $a['notes'], 'likes' => (int) $a['likes'], 'comments' => (int) $a['comments'],
        ], $rows)];
    }

    private function trailSummary(array $t): array
    {
        $pts = $t['route_points'];
        $end = $pts === [] ? null : end($pts);
        return [
            'id' => (int) $t['id'], 'name' => $t['name'], 'ref' => $t['ref'] ?? '', 'region' => $t['region'],
            'distance_km' => (float) $t['distance_km'], 'elevation_gain_m' => (int) $t['elevation_gain_m'],
            'estimated_duration_min' => (int) $t['est_duration_min'], 'difficulty' => $t['difficulty'],
            'loop' => in_array($t['is_loop'] ?? false, [true, 't', 1, '1'], true),
            'start' => $pts === [] ? null : ['lat' => $pts[0][0], 'lon' => $pts[0][1]],
            'end' => $end ? ['lat' => $end[0], 'lon' => $end[1]] : null,
            'links' => ['web' => self::link('/trail', (int) $t['id']), 'api' => self::link('/api', (int) $t['id'])],
        ];
    }

    private static function link(string $kind, int $id = 0): string
    {
        $host = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https'))
            . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $base = rtrim((string) config('base_url'), '/');
        return match ($kind) {
            '/trail' => $host . $base . '/index.php?r=trail&id=' . $id,
            '/api' => $host . $base . '/api/v1/trails/' . $id,
            '/docs' => 'https://github.com/amanesenx-ai/trailsense/blob/main/docs/API.md',
        };
    }
}
