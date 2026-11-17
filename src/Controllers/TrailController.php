<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use TrailSense\Core\Auth;
use TrailSense\Core\Database;
use TrailSense\Core\HttpException;
use TrailSense\Core\Request;
use TrailSense\Core\View;
use TrailSense\Repositories\TrailRepository;

/** F1: trail discovery. */
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

    public function show(): void
    {
        Auth::requireLogin();
        $id = Request::id();
        $trail = $id ? $this->trails->find($id) : null;
        if ($trail === null) {
            throw HttpException::notFound();
        }
        View::render('trails/show', ['title' => $trail['name'], 'active' => 'trails', 'trail' => $trail]);
    }
}
