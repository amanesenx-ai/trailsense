<?php
declare(strict_types=1);

/** F12 public REST API — real HTTP requests to php -S on the test database (demo weather, demo IPMA). */
$port = 8097;
$server = null;
$base = "http://127.0.0.1:$port";
$get = function (string $path, string $method = 'GET') use ($base): array {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_TIMEOUT => 10]);
    $raw = (string) curl_exec($ch);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$status, substr($raw, 0, $hs), json_decode(substr($raw, $hs), true), substr($raw, $hs)];
};
$tomorrow = (new DateTimeImmutable('+1 day'))->format('Y-m-d');

return [
    '__before' => function () use (&$server, $port) {
        resetTestDatabase();
        $cmd = sprintf('TS_ENV=test TS_WEATHER=demo exec php -S 127.0.0.1:%d -t %s', $port, escapeshellarg(TS_ROOT . '/public'));
        $server = proc_open($cmd, [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $pipes);
        for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) { usleep(100000); }
    },
    '__after' => function () use (&$server) { if ($server) { proc_terminate($server); } },

    'GET /api/v1 lists the endpoints, JSON + CORS, no session cookie (AC12.1/12.2)' => function () use ($get) {
        [$st, $h, $j] = $get('/api/v1');
        assertSame(200, $st);
        assertContains('application/json', $h);
        assertContains('Access-Control-Allow-Origin: *', $h);
        assertNotContains('Set-Cookie', $h);
        assertSame('v1', $j['version']);
    },
    'GET /trails filters, includes start and end points' => function () use ($get) {
        [$st, , $j] = $get('/api/v1/trails?difficulty=hard');
        assertSame(200, $st);
        assertSame(3, $j['count']);
        foreach ($j['data'] as $t) {
            assertSame('hard', $t['difficulty']);
            assertTrue(isset($t['start']['lat'], $t['end']['lon']));
        }
    },
    'bad filter → 400 with a message' => function () use ($get) {
        [$st, , $j] = $get('/api/v1/trails?difficulty=extreme');
        assertSame(400, $st);
        assertContains('difficulty', $j['error']['message']);
    },
    'GET /trails/{id} returns GeoJSON route, photos with licence, reports' => function () use ($get) {
        [$st, , $j] = $get('/api/v1/trails/4');
        assertSame(200, $st);
        assertSame('LineString', $j['data']['route']['type']);
        assertTrue(count($j['data']['route']['coordinates']) > 50);
        assertContains('CC BY', $j['data']['photos'][0]['license']);
        assertSame('Passadiços do Paiva', $j['data']['name']);
        assertSame(81, count($j['data']['elevation_profile']), 'elevation profile (AC1.14)');
        assertTrue(in_array($j['data']['climb']['rating'], ['gentle', 'moderate', 'demanding', 'very'], true));
        assertContains('Not an official', $j['data']['route_type']['name']);
    },
    'unknown trail → 404, unknown endpoint → 404, POST → 405' => function () use ($get) {
        assertSame(404, $get('/api/v1/trails/999')[0]);
        assertSame(404, $get('/api/v1/nothing')[0]);
        assertSame(405, $get('/api/v1/trails', 'POST')[0]);
    },
    'GET /trails/{id}/conditions runs the risk check without storing it' => function () use ($get, $tomorrow) {
        $before = (int) resetlessCount('risk_assessment');
        [$st, , $j] = $get("/api/v1/trails/7/conditions?date=$tomorrow&start=10:00&duration=120");
        assertSame(200, $st);
        assertTrue(in_array($j['data']['overall'], ['ok', 'caution', 'warning'], true));
        assertSame(13 - 5, count($j['data']['factors']), '8 factors: official, rain, wind, heat, cold, daylight, fire, trail');
        assertSame('demo', $j['data']['forecast']['source']);
        assertTrue($j['data']['official']['fire_risk'] !== null, 'tomorrow has an IPMA fire risk');
        assertSame($before, (int) resetlessCount('risk_assessment'), 'nothing stored');
        assertTrue(in_array($j['data']['verdict']['key'], ['ok', 'caution', 'warning', 'pending'], true), 'verdict (AC4.11)');
        assertTrue(count($j['data']['forecast']['hourly']) >= 4, 'hourly rows for the window ± 2 h (AC4.13)');
        assertTrue(isset($j['data']['forecast']['hourly'][0]['description']), 'weather code described');
        assertContains('api.open-meteo.com', (string) $j['data']['sources']['api'], 'direct link to the weather API (AC4.15)');
    },
    'conditions validation: past date and bad time → 400' => function () use ($get) {
        assertSame(400, $get('/api/v1/trails/7/conditions?date=2020-01-01')[0]);
        assertSame(400, $get('/api/v1/trails/7/conditions?date=2099-01-01&start=25:00')[0]);
    },
    'group hikes and feed never contain e-mail addresses' => function () use ($get) {
        [$st, , $j, $raw] = $get('/api/v1/group-hikes');
        assertSame(200, $st);
        assertTrue($j['count'] >= 3);
        assertTrue(isset($j['data'][0]['places_left']));
        [, , , $feed] = $get('/api/v1/feed?limit=5');
        assertNotContains('@trailsense.pt', $raw . $feed);
    },
];

function resetlessCount(string $table): int
{
    return (int) TrailSense\Core\Database::connect(config('db_test'))->query("SELECT COUNT(*) FROM $table")->fetchColumn();
}
