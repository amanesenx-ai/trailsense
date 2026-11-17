<?php
declare(strict_types=1);

use TrailSense\Repositories\ParticipantRepository;
use TrailSense\Repositories\ProfileRepository;
use TrailSense\Repositories\SocialRepository;
use TrailSense\Repositories\TrailReportRepository;

$db = null;
return [
    '__before' => function () use (&$db) { $db = resetTestDatabase(); },

    'join a group hike: joined, then already, own hike refused (AC9.5)' => function () use (&$db) {
        $p = new ParticipantRepository($db);
        assertSame('joined', $p->join(4, 1));            // Joana joins Bruno's Paiva hike
        assertSame('already', $p->join(4, 1));
        assertSame('own', $p->join(4, 2));               // Bruno organises it
        assertSame(2, $p->count(4));
    },
    'full group hike refuses new participants (capacity = group_size)' => function () use (&$db) {
        $db->exec('UPDATE group_plan SET group_size = 2 WHERE plan_id = 3');   // 2 already joined in the seed
        assertSame('full', (new ParticipantRepository($db))->join(3, 4));
        $db->exec('UPDATE group_plan SET group_size = 8 WHERE plan_id = 3');
    },
    'personal plans and past hikes cannot be joined' => function () use (&$db) {
        $p = new ParticipantRepository($db);
        assertSame('not-open', $p->join(1, 3));          // Joana's personal plan (no group_plan row)
        $db->exec('UPDATE hike_plan SET planned_date = CURRENT_DATE - 1 WHERE id = 6');
        assertSame('not-open', $p->join(6, 4));
        $db->exec('UPDATE hike_plan SET planned_date = CURRENT_DATE + 1 WHERE id = 6');
    },
    'leaving works once; participants list has names but never e-mails (AC9.6)' => function () use (&$db) {
        $p = new ParticipantRepository($db);
        assertTrue($p->leave(4, 1));
        assertSame(false, $p->leave(4, 1));
        $list = $p->participants(3);
        assertSame(['Joana Silva', 'Rita Moreira'], array_column($list, 'name'));
        assertSame(false, array_key_exists('email', $list[0]));
    },
    'upcoming group hikes show places left and whether I joined (AC9.4)' => function () use (&$db) {
        $rows = (new ParticipantRepository($db))->upcomingGroupHikes(1);
        $byId = array_column($rows, null, 'id');
        assertTrue(isset($byId[3], $byId[4], $byId[6]));
        assertSame(true, $byId[3]['joined']);
        assertSame(2, (int) $byId[3]['participants']);
    },
    'trail reports: latest within 14 days, old ones ignored, closed detected (AC10.2/10.3)' => function () use (&$db) {
        $r = new TrailReportRepository($db);
        assertSame('muddy', $r->latestWithin(1)['condition']);
        assertSame(null, $r->latestWithin(8), '20-day-old obstacle is outside 14 days');
        assertSame(false, $r->closedRecently(4));
        $r->create(4, 3, 'closed', 'Walkway closed after a landslide');
        assertSame(true, $r->closedRecently(4));
        assertSame('closed', $r->recent(4)[0]['condition']);
    },
    'invalid report condition is rejected by the database' => function () use (&$db) {
        assertThrows(fn () => (new TrailReportRepository($db))->create(1, 1, 'lava', ''), PDOException::class);
    },
    'likes toggle and are unique per user (AC6.4)' => function () use (&$db) {
        $s = new SocialRepository($db);
        $before = $s->likeCount(4);
        assertSame(true, $s->toggleLike(4, 4));
        assertSame($before + 1, $s->likeCount(4));
        assertSame(false, $s->toggleLike(4, 4));
        assertSame($before, $s->likeCount(4));
    },
    'comments: only the author can delete their comment' => function () use (&$db) {
        $s = new SocialRepository($db);
        $id = $s->addComment(1, 4, 'Great photos!');
        assertSame(null, $s->deleteOwnComment($id, 1), 'someone else cannot delete it');
        assertSame(1, $s->deleteOwnComment($id, 4));
        assertThrows(fn () => $s->addComment(1, 4, '   '), PDOException::class);
    },
    'profile statistics come from published hikes; no e-mail exposed (AC11.1/11.2)' => function () use (&$db) {
        $p = new ProfileRepository($db);
        $stats = $p->stats(1);
        assertSame(1, $stats['hikes']);
        assertSame(4.1, $stats['km']);
        assertSame(6, count($p->monthly(1)), 'six months, empty months included');
        assertSame(false, array_key_exists('email', $p->user(1)));
    },
];
