<?php
declare(strict_types=1);

/**
 * End-to-end flows through real HTTP requests to `php -S` running the app on the TEST database
 * with the offline demo weather provider (critical user flows from SPEC.md).
 */

$port = 8099;
$server = null;
$base = "http://127.0.0.1:$port";
$login = function (string $email) use ($base): Browser {
    $b = new Browser($base);
    $b->get('r=login')->post('r=login.submit', ['email' => $email, 'password' => 'trailsense123']);
    return $b;
};
$future = (new DateTimeImmutable('+6 days'))->format('Y-m-d');

return [
    '__before' => function () use (&$server, $port) {
        resetTestDatabase();
        $cmd = sprintf('TS_ENV=test TS_WEATHER=demo exec php -S 127.0.0.1:%d -t %s', $port, escapeshellarg(TS_ROOT . '/public'));
        $server = proc_open($cmd, [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $pipes);
        for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) {
            usleep(100000);
        }
    },
    '__after' => function () use (&$server) { if ($server) { proc_terminate($server); } },

    'anonymous user is redirected to sign in (AC8.5)' => function () use ($base) {
        $b = (new Browser($base))->get('r=plans');
        assertSame(302, $b->status);
        assertContains('r=login', $b->location);
    },
    'wrong password shows one generic message and does not echo it (AC8.3)' => function () use ($base) {
        $b = (new Browser($base))->get('r=login')->post('r=login.submit', ['email' => 'joana@trailsense.pt', 'password' => 'Wr0ngSecret!']);
        assertSame(422, $b->status);
        assertContains('E-mail or password is not correct.', $b->body);
        assertNotContains('Wr0ngSecret!', $b->body);
    },
    'POST without CSRF token is refused (AC8.6)' => function () use ($base) {
        $b = (new Browser($base))->get('r=login')->post('r=login.submit', ['email' => 'joana@trailsense.pt', 'password' => 'trailsense123'], [], false);
        assertSame(419, $b->status);
    },
    'register → hiker home with hiker menu (AC8.1/8.4)' => function () use ($base) {
        $b = (new Browser($base))->get('r=register');
        $b->post('r=register.submit', ['name' => 'Carla Dias', 'email' => 'carla@example.pt', 'password' => 'hike12345', 'role' => 'hiker']);
        assertSame(303, $b->status);
        $b->follow();
        assertContains(', Carla <span class="tag">Hiker</span>', $b->body);
        assertContains('>Plans<', $b->body);
        assertContains('>Groups<', $b->body);
        assertNotContains('>My hikes<', $b->body);
    },
    'duplicate e-mail keeps typed values and explains (AC8.2)' => function () use ($base) {
        $b = (new Browser($base))->get('r=register');
        $b->post('r=register.submit', ['name' => 'Joana Two', 'email' => 'joana@trailsense.pt', 'password' => 'hike12345', 'role' => 'hiker']);
        assertSame(422, $b->status);
        assertContains('An account with this e-mail already exists.', $b->body);
        assertContains('value="Joana Two"', $b->body);
    },
    'organizer sees Groups menu and the completeness indicator (AC8.4, K1)' => function () use ($login) {
        $b = $login('bruno@trailsense.pt')->follow();
        assertContains('Hi, Bruno <span class="tag tag-org">Organizer</span>', $b->body);
        assertContains('>Groups<', $b->body);
        assertContains('Upcoming hikes fully checked', $b->body);
    },
    'trail filters work and bad values are ignored (AC1.2/1.3/1.5)' => function () use ($login) {
        $b = $login('joana@trailsense.pt');
        $b->get('r=trails&difficulty=hard');
        assertContains('Caminho do Carteiro', $b->body);
        assertNotContains('Trilho do Rio Febros', $b->body);
        $b->get('r=trails&max_km=abc&difficulty=extreme');
        assertSame(200, $b->status);
        assertContains('11 trails', $b->body);
        $b->get('r=trails&q=zzzz');
        assertContains('No trails match these filters', $b->body);
    },
    'hiker plans a hike → assessment with explained rules (F2→F5)' => function () use ($login, $future) {
        $b = $login('joana@trailsense.pt');
        $b->get('r=plan.new&trail_id=6');
        $b->post('r=plan.store', ['trail_id' => '6', 'planned_date' => $future, 'start_time' => '15:30', 'duration_min' => '150', 'notes' => '']);
        assertSame(303, $b->status);
        $b->follow();
        assertContains('Caminho do Carteiro', $b->body);
        assertContains('Risk check', $b->body);
        assertContains('Demo forecast', $b->body, 'demo data must be labelled');
        // 15:30 + 150 min = 18:00 in November → after sunset → DAY_W with concrete action (AC5.2)
        assertContains('Rule <code>DAY_W</code>:', $b->body);
        assertContains('Start by <strong>', $b->body);
    },
    'invalid plan keeps typed values and shows field errors (AC2.2)' => function () use ($login) {
        $b = $login('joana@trailsense.pt');
        $b->get('r=plan.new');
        $b->post('r=plan.store', ['trail_id' => '1', 'planned_date' => '2020-01-01', 'start_time' => '09:00', 'duration_min' => '90', 'notes' => 'keep me']);
        assertSame(422, $b->status);
        assertContains('The date cannot be in the past.', $b->body);
        assertContains('keep me', $b->body);
    },
    'organizer group plan produces a copyable briefing (F9)' => function () use ($login, $future) {
        $b = $login('bruno@trailsense.pt');
        $b->get('r=plan.new');
        $b->post('r=plan.store', ['trail_id' => '3', 'planned_date' => $future, 'start_time' => '09:30', 'duration_min' => '180',
            'group_size' => '9', 'meeting_point' => 'Valongo station', 'meeting_time' => '09:00']);
        $b->follow();
        assertContains('Group briefing', $b->body);
        assertContains('Meet: 09:00 at Valongo station', $b->body);
        assertContains('data-copy="#briefing-text"', $b->body);
        // AC9.10: the organizer can edit the text before copying; participants get it read-only
        assertContains('rows="11" data-editable>', $b->body);
        assertContains('add your own notes', $b->body);
        assertContains('data-share-wa="#briefing-text"', $b->body);   // AC9.11
        $j = $login('joana@trailsense.pt')->get('r=plan&id=3');
        assertContains('rows="11" readonly>', $j->body);
    },
    'a hiker cannot open another user\'s personal plan or delete any foreign plan (AC2.4, AC9.7)' => function () use ($login) {
        $b = $login('rita@trailsense.pt');
        $b->get('r=plan&id=1');                       // Joana's personal plan
        assertSame(403, $b->status);
        $b = $login('joana@trailsense.pt');
        $b->get('r=plan&id=1')->post('r=plan.delete&id=3', []);   // valid token from her own plan page
        assertSame(403, $b->status);
    },
    'publish a hike with a photo → top of the feed (F7/F6)' => function () use ($login) {
        $img = tempnam(sys_get_temp_dir(), 'ts') . '.png';
        $gd = imagecreatetruecolor(40, 30);
        imagepng($gd, $img);
        $b = $login('joana@trailsense.pt');
        $b->get('r=publish');
        $b->post('r=publish.store', ['trail_id' => '5', 'hiked_on' => date('Y-m-d'), 'duration_min' => '170', 'distance_km' => '', 'notes' => 'Stairs!', 'photo_alt' => 'River gorge'], ['photo' => $img]);
        assertSame(303, $b->status);
        $b->get('r=feed');
        $first = strpos($b->body, 'post-card');
        assertTrue($first !== false && strpos($b->body, 'Stairs!', $first) < strpos($b->body, 'post-card', $first + 10), 'new post first');
        assertTrue((bool) preg_match('/r=photo&amp;f=([a-f0-9]{32}\.png)/', $b->body, $m), 'photo link in feed');
        $b->get('r=photo&f=' . $m[1]);
        assertSame(200, $b->status);
        assertSame(file_get_contents($img), $b->body, 'served photo = uploaded bytes');
    },
    'a fake image (text renamed .jpg) is rejected (AC7.2)' => function () use ($login) {
        $fake = tempnam(sys_get_temp_dir(), 'ts') . '.jpg';
        file_put_contents($fake, '<?php echo "x";');
        $b = $login('joana@trailsense.pt');
        $b->get('r=publish');
        $b->post('r=publish.store', ['trail_id' => '5', 'hiked_on' => date('Y-m-d'), 'duration_min' => '170'], ['photo' => $fake]);
        assertSame(422, $b->status);
        assertContains('Only JPEG, PNG or WebP photos are accepted.', $b->body);
    },
    'user content is escaped in the feed (XSS, NFR3)' => function () use ($login) {
        $b = $login('joana@trailsense.pt');
        $b->get('r=publish');
        $b->post('r=publish.store', ['trail_id' => '1', 'hiked_on' => date('Y-m-d'), 'duration_min' => '60', 'notes' => '<script>alert(1)</script>']);
        $b->get('r=feed');
        assertNotContains('<script>alert(1)</script>', $b->body);
        assertContains('&lt;script&gt;', $b->body);
    },
    'unknown route and unknown ids give a friendly 404' => function () use ($login) {
        $b = $login('joana@trailsense.pt');
        assertSame(404, $b->get('r=nope')->status);
        assertSame(404, $b->get('r=trail&id=999')->status);
        assertSame(404, $b->get('r=activity&id=abc')->status);
        assertContains('Page not found', $b->body);
    },
];
