<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use TrailSense\Core\Auth;
use TrailSense\Core\Database;
use TrailSense\Core\View;
use TrailSense\Repositories\ActivityRepository;
use TrailSense\Services\AssessmentService;
use TrailSense\Repositories\PlanRepository;

/** Role-specific home screen (AC8.4, A1 "two roles, two views"). */
final class HomeController
{
    public function index(): void
    {
        $user = Auth::user();
        $db = Database::connection();
        if ($user === null) {                                   // AC0.1 public landing page
            View::render('home/landing', [
                'title' => 'Hike smarter. Explore safer.',
                'trails' => array_slice(array_values(array_filter((new \TrailSense\Repositories\TrailRepository($db))->search(),
                    static fn (array $t) => !empty($t['thumb_url']))), 0, 6),
            ]);
            return;
        }
        $planRepo = new PlanRepository($db);
        $plans = $planRepo->forUser($user['id']);

        if (AssessmentService::checkUnchecked($db, $plans['upcoming'])) {   // same checks every time (A1)
            $plans = $planRepo->forUser($user['id']);
        }
        $feed = (new ActivityRepository($db))->feed(3);

        if ($user['role'] === 'organizer') {
            View::render('home/organizer', [
                'title' => 'My group hikes',
                'active' => 'home',
                'plans' => $plans['upcoming'],
                'pastCount' => count($plans['past']),
                'kpi' => $planRepo->completeness($user['id']),
                'feed' => $feed,
                'hero' => $plans['upcoming'][0]['trail_thumb'] ?? null,
            ]);
            return;
        }
        $open = array_values(array_filter(
            (new \TrailSense\Repositories\ParticipantRepository($db))->upcomingGroupHikes($user['id'], null, 6),
            static fn (array $h) => !in_array($h['joined'], [true, 't'], true)
        ));
        View::render('home/hiker', [
            'groupHikes' => array_slice($open, 0, 2),
            'title' => 'Home',
            'active' => 'home',
            'next' => $plans['upcoming'][0] ?? null,
            'more' => array_slice($plans['upcoming'], 1, 2),
            'feed' => (new ActivityRepository($db))->feed(3),
            'stats' => (new \TrailSense\Repositories\ProfileRepository($db))->stats($user['id']),
            'discover' => array_slice(array_values(array_filter((new \TrailSense\Repositories\TrailRepository($db))->search(),
                static fn (array $t) => !empty($t['thumb_url']))), 0, 8),
            'saved' => (new \TrailSense\Repositories\TrailRepository($db))->search('', null, null, null, null,
                (new \TrailSense\Repositories\FavouriteRepository($db))->ids($user['id'])),
            'recent' => self::inOrder((new \TrailSense\Repositories\TrailRepository($db))->search('', null, null, null, null,
                $_SESSION['recent_trails'] ?? []), $_SESSION['recent_trails'] ?? []),
            'myHikes' => array_slice((new ActivityRepository($db))->byUser($user['id']), 0, 3),
            'pastCount' => count($plans['past']),
        ]);
    }

    /** Keep the session order for "recently viewed". */
    private static function inOrder(array $rows, array $ids): array
    {
        $pos = array_flip(array_map('intval', $ids));
        usort($rows, fn ($a, $b) => ($pos[(int) $a['id']] ?? 99) <=> ($pos[(int) $b['id']] ?? 99));
        return $rows;
    }

    /** Public page listing data sources, rules, evidence and the apps we compared with. */
    public function sources(): void
    {
        View::render('home/sources', ['title' => 'Sources and credits', 'active' => '']);
    }
}
