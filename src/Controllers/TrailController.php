<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use TrailSense\Core\Auth;
use TrailSense\Core\Csrf;
use TrailSense\Core\Database;
use TrailSense\Core\Flash;
use TrailSense\Core\HttpException;
use TrailSense\Core\Request;
use TrailSense\Core\View;
use TrailSense\Repositories\ParticipantRepository;
use TrailSense\Repositories\TrailReportRepository;
use TrailSense\Repositories\TrailRepository;

/** F1 trail discovery, F1.2 map, F1.3 photos, F10 community reports. */
final class TrailController
{
    private TrailRepository $trails;

    public function __construct()
    {
        $this->trails = new TrailRepository(Database::connection());
    }

    public function index(): void
    {
        $user = Auth::requireLogin();
        $favs = new \TrailSense\Repositories\FavouriteRepository(Database::connection());
        $regions = $this->trails->regions();
        $region = in_array(Request::get('region'), $regions, true) ? Request::get('region') : null;
        $minRaw = Request::get('max_min');
        $maxMin = in_array($minRaw, ['120', '180', '240', '360'], true) ? (int) $minRaw : null;
        $saved = Request::get('saved') === '1';
        // AC1.5: invalid filter values are silently ignored
        $q = mb_substr(Request::get('q'), 0, 60);
        $difficulty = in_array(Request::get('difficulty'), TrailRepository::DIFFICULTIES, true) ? Request::get('difficulty') : null;
        $maxRaw = Request::get('max_km');
        $maxKm = (is_numeric($maxRaw) && (float) $maxRaw > 0) ? (float) $maxRaw : null;

        View::render('trails/index', [
            'title' => 'Trails',
            'active' => 'trails',
            'trails' => $this->trails->search($q, $difficulty, $maxKm, $region, $maxMin, $saved ? $favs->ids($user['id']) : null),
            'filters' => ['q' => $q, 'difficulty' => $difficulty, 'max_km' => $maxKm, 'region' => $region, 'max_min' => $maxMin, 'saved' => $saved],
            'regions' => $regions,
            'savedIds' => $favs->ids($user['id']),
        ]);
    }

    public function show(array $errors = [], array $old = []): void
    {
        $user = Auth::requireLogin();
        $trail = $this->trailOr404();
        $db = Database::connection();
        // AC1.18 recently viewed: last 6 trails, this session only
        $recent = array_values(array_diff($_SESSION['recent_trails'] ?? [], [(int) $trail['id']]));
        $_SESSION['recent_trails'] = array_slice(array_merge([(int) $trail['id']], $recent), 0, 6);
        View::render('trails/show', [
            'title' => $trail['name'],
            'active' => 'trails',
            'trail' => $trail,
            'photos' => $this->trails->photos((int) $trail['id']),
            'outlook' => \TrailSense\Services\WeatherOutlook::forPlace((float) $trail['start_lat'], (float) $trail['start_lon']),
            'saved' => (new \TrailSense\Repositories\FavouriteRepository($db))->isSaved($user['id'], (int) $trail['id']),
            'reports' => (new TrailReportRepository($db))->recent((int) $trail['id']),
            'groupHikes' => (new ParticipantRepository($db))->upcomingGroupHikes($user['id'], (int) $trail['id'], 5),
            'conditions' => TrailReportRepository::CONDITIONS,
            'errors' => $errors,
            'old' => $old,
            'withMap' => true,
        ], $errors ? 422 : 200);
    }

    /** AC10.1: any signed-in user reports the current condition. */
    public function report(): void
    {
        Csrf::check();
        $user = Auth::requireLogin();
        $trail = $this->trailOr404();
        $condition = Request::post('condition');
        $note = Request::post('note');
        $errors = [];
        if (!array_key_exists($condition, TrailReportRepository::CONDITIONS)) {
            $errors['condition'] = 'Choose the trail condition.';
        }
        if (mb_strlen($note) > 300) {
            $errors['note'] = 'The note can have at most 300 characters.';
        }
        if ($errors !== []) {
            $this->show($errors, ['condition' => $condition, 'note' => $note]);
            return;
        }
        (new TrailReportRepository(Database::connection()))->create((int) $trail['id'], $user['id'], $condition, $note);
        Flash::set('success', 'Thanks! Your report helps other hikers.');
        View::redirect('trail', ['id' => $trail['id']]);
    }

    /** AC1.18 save / unsave a trail (CSRF → auth → validate → act → redirect). */
    public function save(): void
    {
        Csrf::check();
        $user = Auth::requireLogin();
        $trail = $this->trailOr404();
        $now = (new \TrailSense\Repositories\FavouriteRepository(Database::connection()))->toggle($user['id'], (int) $trail['id']);
        Flash::set('success', $now ? 'Trail saved. Find it under Saved trails.' : 'Removed from your saved trails.');
        Request::post('back') === 'list' ? View::redirect('trails', ['saved' => '1']) : View::redirect('trail', ['id' => $trail['id']]);
    }

    private function trailOr404(): array
    {
        $id = Request::id();
        $trail = $id ? $this->trails->find($id) : null;
        if ($trail === null) {
            throw HttpException::notFound();
        }
        return $trail;
    }
}
