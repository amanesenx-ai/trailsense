<?php
declare(strict_types=1);

/** Release 2 user flows through real HTTP (reuses the Browser class from FlowsTest). */
$port = 8098;
$server = null;
$base = "http://127.0.0.1:$port";
$login = function (string $email) use ($base): Browser {
    $b = new Browser($base);
    $b->get('r=login')->post('r=login.submit', ['email' => $email, 'password' => 'trailsense123']);
    return $b;
};

return [
    '__before' => function () use (&$server, $port) {
        resetTestDatabase();
        $cmd = sprintf('TS_ENV=test TS_WEATHER=demo exec php -S 127.0.0.1:%d -t %s', $port, escapeshellarg(TS_ROOT . '/public'));
        $server = proc_open($cmd, [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $pipes);
        for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) { usleep(100000); }
    },
    '__after' => function () use (&$server) { if ($server) { proc_terminate($server); } },

    'trail page: real map with start and end, photos with licence, directions (AC1.6–1.10)' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=trail&id=4');
        assertSame(200, $b->status);
        assertContains('data-map=', $b->body);
        assertContains('leaflet@1.9.4', $b->body);
        assertContains('integrity="sha256-', $b->body, 'CDN files pinned with SRI (ADR-010)');
        assertContains('> Start</li>', $b->body);
        assertContains('> End</li>', $b->body);
        assertContains('openstreetmap.org/directions', $b->body);
        assertContains('openstreetmap.org/relation/5389226', $b->body);
        assertContains('CC BY-SA 2.0', $b->body);
        assertContains('© <a href="https://www.openstreetmap.org/copyright"', $b->body);
    },
    'loop trails show one start/finish marker' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=trail&id=7');
        assertContains('Start and finish', $b->body);
        assertNotContains('> End</li>', $b->body);
    },
    'hiker joins a group hike and then sees the briefing (AC9.5/9.6)' => function () use ($login) {
        $b = $login('miguel@trailsense.pt')->get('r=groups');
        assertContains('Join this hike', $b->body);
        $b->get('r=plan&id=3');
        assertSame(200, $b->status, 'group hike is visible read-only');
        assertNotContains('Group briefing', $b->body, 'briefing only after joining');
        assertNotContains('Delete plan', $b->body);
        $b->post('r=group.join&id=3', []);
        assertSame(303, $b->status);
        $b->follow();
        assertContains('You joined the group hike', $b->body);
        assertContains('Group briefing', $b->body);
    },
    'organizer sees who is coming' => function () use ($login) {
        $b = $login('bruno@trailsense.pt')->get('r=plan&id=3');
        assertContains('Who is coming', $b->body);
        assertContains('Miguel Antunes', $b->body);
        assertNotContains('miguel@trailsense.pt', $b->body);
    },
    'trail report "closed" turns the plan check into a WARNING (AC10.3)' => function () use ($login) {
        $b = $login('rita@trailsense.pt')->get('r=trail&id=3');
        $b->post('r=trail.report&id=3', ['condition' => 'closed', 'note' => 'Bridge washed away']);
        assertSame(303, $b->status);
        $b->follow();
        assertContains('Bridge washed away', $b->body);
        $o = $login('bruno@trailsense.pt')->get('r=plan&id=3');
        $o->post('r=plan.check&id=3', []);
        $o->follow();
        assertContains('Rule <code>TRAIL_CL</code> triggered', $o->body);
        assertContains('Latest hiker report:</strong> Closed', $o->body);
    },
    'report without a condition is rejected with a message' => function () use ($login) {
        $b = $login('rita@trailsense.pt')->get('r=trail&id=1');
        $b->post('r=trail.report&id=1', ['condition' => 'lava']);
        assertSame(422, $b->status);
        assertContains('Choose the trail condition.', $b->body);
    },
    'like, comment, and only delete your own comment (AC6.4)' => function () use ($login) {
        $b = $login('miguel@trailsense.pt')->get('r=activity&id=1');
        $b->post('r=activity.like&id=1', []);
        $b->follow();
        assertContains('aria-pressed="true"', $b->body);
        $b->post('r=comment.store&id=1', ['body' => 'Beautiful <b>view</b>!']);
        $b->follow();
        assertContains('Beautiful &lt;b&gt;view&lt;/b&gt;!', $b->body);
        preg_match('/r=comment\.delete&amp;id=(\d+)/', $b->body, $m);
        $other = $login('joana@trailsense.pt')->get('r=activity&id=1');
        $other->post('r=comment.delete&id=' . $m[1], []);
        assertSame(403, $other->status);
    },
    'profile shows statistics and never the e-mail (AC11.1/11.2)' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=profile&id=2');
        assertContains('Bruno Costa', $b->body);
        assertContains('Hikes in the last 6 months', $b->body);
        assertNotContains('bruno@trailsense.pt', $b->body);
        $b->get('r=account');
        assertContains('My published hikes', $b->body);
    },
    'IPMA status is labelled on the plan page (demo in tests)' => function () use ($login) {
        $b = $login('bruno@trailsense.pt')->get('r=plan&id=6');
        assertContains('Official warnings', $b->body);
        assertContains('Fire risk', $b->body);
        assertContains('IPMA: demo data', $b->body);
    },
];
