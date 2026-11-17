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
        Auth::requireLogin();
        // AC1.5: invalid filter values are silently ignored
        $q = mb_substr(Request::get('q'), 0, 60);
        $difficulty = in_array(Request::get('difficulty'), TrailRepository::DIFFICULTIES, true) ? Request::get('difficulty') : null;
        $maxRaw = Request::get('max_km');
        $maxKm = (is_numeric($maxRaw) && (float) $maxRaw > 0) ? (float) $maxRaw : null;

        View::render('trails/index', [
            'title' => 'Trails',
            'active' => 'trails',
            'trails' => $this->trails->search($q, $difficulty, $maxKm),
            'filters' => ['q' => $q, 'difficulty' => $difficulty, 'max_km' => $maxKm],
        ]);
    }

    public function show(array $errors = [], array $old = []): void
    {
        $user = Auth::requireLogin();
        $trail = $this->trailOr404();
        $db = Database::connection();
        View::render('trails/show', [
            'title' => $trail['name'],
            'active' => 'trails',
            'trail' => $trail,
            'photos' => $this->trails->photos((int) $trail['id']),
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
