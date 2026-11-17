<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;
use TrailSense\Alerts\OfficialAlerts;
use TrailSense\Weather\Forecast;

final class AssessmentRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** Stores snapshot + assessment + one result per rule in one transaction (ADR-005). */
    public function save(int $planId, Forecast $f, string $sunset, array $evaluation, ?OfficialAlerts $alerts = null): int
    {
        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare(
                'INSERT INTO weather_snapshot (plan_id, source, available, message, precip_total_mm, gust_max_kmh,
                    temp_min_c, temp_max_c, sunset, alerts_source, warning_level, warnings, fire_risk, alerts_message)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
            );
            $alerts ??= OfficialAlerts::unavailable('');
            $s->execute([$planId, $f->source, $f->available ? 'true' : 'false', $f->message, $f->precipTotalMm,
                $f->gustMaxKmh, $f->tempMinC, $f->tempMaxC, $sunset, $alerts->source, $alerts->warningLevel,
                json_encode($alerts->warnings, JSON_UNESCAPED_UNICODE), $alerts->fireRisk, mb_substr($alerts->message, 0, 300)]);
            $snapshotId = (int) $s->fetchColumn();

            $a = $this->db->prepare(
                'INSERT INTO risk_assessment (plan_id, snapshot_id, overall_level, is_complete)
                 VALUES (?, ?, ?, ?) RETURNING id'
            );
            $a->execute([$planId, $snapshotId, $evaluation['overall'], $evaluation['complete'] ? 'true' : 'false']);
            $assessmentId = (int) $a->fetchColumn();

            $r = $this->db->prepare(
                'INSERT INTO assessment_result (assessment_id, rule_code, observed_value, evaluated, triggered)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($evaluation['results'] as $res) {
                $r->execute([$assessmentId, $res['rule']['code'], $res['observed'],
                    $res['evaluated'] ? 'true' : 'false', $res['triggered'] ? 'true' : 'false']);
            }
            $this->db->commit();
            return $assessmentId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Latest assessment of a plan with its snapshot and rule results (rules joined for display). */
    public function latestForPlan(int $planId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.id, a.overall_level, a.is_complete, a.created_at,
                    s.source, s.available, s.message, s.precip_total_mm, s.gust_max_kmh, s.temp_min_c,
                    s.temp_max_c, s.sunset, s.fetched_at, s.alerts_source, s.warning_level, s.warnings, s.fire_risk,
                    s.alerts_message
             FROM risk_assessment a JOIN weather_snapshot s ON s.id = a.snapshot_id
             WHERE a.plan_id = ? ORDER BY a.created_at DESC, a.id DESC LIMIT 1'
        );
        $stmt->execute([$planId]);
        $assessment = $stmt->fetch();
        if (!$assessment) {
            return null;
        }

        $rows = $this->db->prepare(
            "SELECT r.code, r.factor, r.metric, r.comparator, r.threshold, r.level, r.name, r.precaution, r.required,
                    ar.observed_value, ar.evaluated, ar.triggered
             FROM assessment_result ar JOIN risk_rule r ON r.code = ar.rule_code
             WHERE ar.assessment_id = ?
             ORDER BY array_position(ARRAY['official','rain','wind','heat','cold','daylight','fire','trail']::varchar[], r.factor), r.level"
        );
        $rows->execute([$assessment['id']]);
        $assessment['results'] = array_map(static fn (array $row) => [
            'rule' => $row,
            'observed' => $row['observed_value'] === null ? null : (float) $row['observed_value'],
            'evaluated' => (bool) $row['evaluated'],
            'triggered' => (bool) $row['triggered'],
        ], $rows->fetchAll());
        return $assessment;
    }
}
