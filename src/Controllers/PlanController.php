<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use DateTimeImmutable;
use TrailSense\Core\Auth;
use TrailSense\Core\Csrf;
use TrailSense\Core\Database;
use TrailSense\Core\Flash;
use TrailSense\Core\HttpException;
use TrailSense\Core\Request;
use TrailSense\Core\View;
use TrailSense\Domain\Briefing;
use TrailSense\Domain\PlanValidator;
use TrailSense\Repositories\AssessmentRepository;
use TrailSense\Repositories\ParticipantRepository;
use TrailSense\Repositories\TrailReportRepository;
use TrailSense\Repositories\PlanRepository;
use TrailSense\Repositories\RuleRepository;
use TrailSense\Repositories\TrailRepository;
use TrailSense\Services\AssessmentService;

/** F2 plans, F3–F5 assessment, F9 group briefing. Organizer plans are always group plans. */
final class PlanController
{
    private PlanRepository $plans;
    private TrailRepository $trails;
    private AssessmentRepository $assessments;

    public function __construct()
    {
        $db = Database::connection();
        $this->plans = new PlanRepository($db);
        $this->trails = new TrailRepository($db);
        $this->assessments = new AssessmentRepository($db);
    }

    public function index(): void
    {
        $user = Auth::requireLogin();
        View::render('plans/index', [
            'title' => $user['role'] === 'organizer' ? 'My group hikes' : 'My plans',
            'active' => 'plans',
            'plans' => $this->plans->forUser($user['id']),
            'joined' => (new ParticipantRepository(Database::connection()))->joinedBy($user['id']),
        ]);
    }

    public function create(array $errors = [], array $old = []): void
    {
        $user = Auth::requireLogin();
        if ($old === []) {
            $trail = Request::id('trail_id') ? $this->trails->find((int) Request::id('trail_id')) : null;
            $old = [
                'trail_id' => $trail['id'] ?? '',
                'planned_date' => (new DateTimeImmutable('next saturday'))->format('Y-m-d'),
                'start_time' => '09:30',
                'duration_min' => $trail['est_duration_min'] ?? '',
            ];
        }
        View::render('plans/form', [
            'title' => $user['role'] === 'organizer' ? 'New group hike' : 'Plan a hike',
            'active' => 'plans',
            'trails' => $this->trails->options(),
            'isGroup' => $user['role'] === 'organizer',
            'errors' => $errors,
            'old' => $old,
        ], $errors ? 422 : 200);
    }

    public function store(): void
    {
        Csrf::check();
        $user = Auth::requireLogin();
        $isGroup = $user['role'] === 'organizer';
        $fields = ['trail_id', 'planned_date', 'start_time', 'duration_min', 'notes', 'group_size', 'meeting_point', 'meeting_time'];
        $input = [];
        foreach ($fields as $f) {
            $input[$f] = Request::post($f);
        }

        [$data, $errors] = PlanValidator::validate($input, fn (int $id) => $this->trails->exists($id), $isGroup, new DateTimeImmutable('today'));
        if ($errors !== []) {
            $this->create($errors, $input);       // AC2.2: keep typed values
            return;
        }
        $id = $this->plans->create($user['id'], $data);
        $this->service()->assess((array) $this->plans->find($id));
        Flash::set('success', 'Plan saved. Here is the weather check for your hike.');
        View::redirect('plan', ['id' => $id]);
    }

    public function show(): void
    {
        $plan = $this->visiblePlan();
        $user = Auth::user();
        $isOwner = (int) $plan['user_id'] === $user['id'];
        $assessment = $this->assessments->latestForPlan((int) $plan['id']);
        if ($assessment === null) {
            $this->service()->assess($plan);
            $assessment = $this->assessments->latestForPlan((int) $plan['id']);
        }
        $vm = AssessmentService::present($plan, (array) $assessment);

        $briefing = null;
        if ($plan['group_size'] !== null) {
            $group = ['group_size' => $plan['group_size'], 'meeting_point' => $plan['meeting_point'], 'meeting_time' => $plan['meeting_time']];
            $briefing = Briefing::build($plan, $group, $vm['factors'], $vm['complete'], $vm['latest_start']);
        }
        $participants = new ParticipantRepository(Database::connection());
        View::render('plans/show', [
            'title' => $plan['trail_name'],
            'active' => $isOwner ? 'plans' : 'groups',
            'plan' => $plan,
            'vm' => $vm,
            'briefing' => $briefing,
            'isPast' => $plan['planned_date'] < date('Y-m-d'),
            'isOwner' => $isOwner,
            'participants' => $isOwner && $plan['group_size'] !== null ? $participants->participants((int) $plan['id']) : [],
            'isParticipant' => !$isOwner && $participants->isParticipant((int) $plan['id'], $user['id']),
            'report' => (new TrailReportRepository(Database::connection()))->latestWithin((int) $plan['trail_id']),
            'withMap' => true,
        ]);
    }

    public function check(): void
    {
        Csrf::check();
        $plan = $this->ownedPlan();
        $this->service()->assess($plan);
        Flash::set('success', 'Conditions checked again with the latest forecast.');
        View::redirect('plan', ['id' => $plan['id']]);
    }

    public function delete(): void
    {
        Csrf::check();
        $user = Auth::requireLogin();
        $id = Request::id() ?? 0;
        if (!$this->plans->deleteOwned($id, $user['id'])) {      // AC2.4
            throw HttpException::forbidden();
        }
        Flash::set('info', 'Plan deleted.');
        View::redirect('plans');
    }

    /**
     * Owner, or participant of a group hike, or anyone for an upcoming group hike (read-only, AC9.6).
     * Personal plans stay private to their owner (AC9.7).
     */
    private function visiblePlan(): array
    {
        $user = Auth::requireLogin();
        $plan = ($id = Request::id()) ? $this->plans->find($id) : null;
        if ($plan === null) {
            throw HttpException::notFound();
        }
        $isOwner = (int) $plan['user_id'] === $user['id'];
        $isGroup = $plan['group_size'] !== null;
        if (!$isOwner && !$isGroup) {
            throw HttpException::forbidden();
        }
        return $plan;
    }

    /** Loads a plan and checks ownership (writes). */
    private function ownedPlan(): array
    {
        $user = Auth::requireLogin();
        $id = Request::id();
        $plan = $id ? $this->plans->find($id) : null;
        if ($plan === null) {
            throw HttpException::notFound();
        }
        if ((int) $plan['user_id'] !== $user['id']) {
            throw HttpException::forbidden();
        }
        return $plan;
    }

    private function service(): AssessmentService
    {
        return AssessmentService::forApp(Database::connection());
    }
}
