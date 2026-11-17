<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;

final class PlanRepository
{
    private const SELECT = "SELECT p.*, t.name AS trail_name, t.region, t.distance_km, t.difficulty,
            t.start_lat, t.start_lon, t.route_points, t.est_duration_min, t.ref, t.ipma_area, t.coastal, t.is_loop,
            t.osm_relation_id, u.name AS organizer_name,
            (SELECT COUNT(*) FROM hike_participant hp WHERE hp.plan_id = p.id) AS participants,
            g.group_size, g.meeting_point, g.meeting_time,
            a.overall_level, a.is_complete, a.created_at AS assessed_at
        FROM hike_plan p
        JOIN trail t ON t.id = p.trail_id
        JOIN app_user u ON u.id = p.user_id
        LEFT JOIN group_plan g ON g.plan_id = p.id
        LEFT JOIN LATERAL (
            SELECT overall_level, is_complete, created_at FROM risk_assessment ra
            WHERE ra.plan_id = p.id ORDER BY ra.created_at DESC, ra.id DESC LIMIT 1
        ) a ON TRUE";

    public function __construct(private readonly PDO $db)
    {
    }

    /** Saves plan (+ group details) atomically. */
    public function create(int $userId, array $data): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO hike_plan (user_id, trail_id, planned_date, start_time, duration_min, notes)
                 VALUES (?, ?, ?, ?, ?, ?) RETURNING id'
            );
            $stmt->execute([$userId, $data['trail_id'], $data['planned_date'], $data['start_time'],
                $data['duration_min'], $data['notes'] ?? '']);
            $planId = (int) $stmt->fetchColumn();

            if (!empty($data['group'])) {
                $g = $data['group'];
                $this->db->prepare(
                    'INSERT INTO group_plan (plan_id, group_size, meeting_point, meeting_time) VALUES (?, ?, ?, ?)'
                )->execute([$planId, $g['group_size'], $g['meeting_point'], $g['meeting_time']]);
            }
            $this->db->commit();
            return $planId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE p.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['route_points'] = json_decode((string) $row['route_points'], true) ?: [];
        return $row;
    }

    /** AC2.3: upcoming (soonest first) and past (most recent first). */
    public function forUser(int $userId): array
    {
        $up = $this->db->prepare(self::SELECT . ' WHERE p.user_id = ? AND p.planned_date >= CURRENT_DATE
            ORDER BY p.planned_date, p.start_time');
        $up->execute([$userId]);
        $past = $this->db->prepare(self::SELECT . ' WHERE p.user_id = ? AND p.planned_date < CURRENT_DATE
            ORDER BY p.planned_date DESC LIMIT 10');
        $past->execute([$userId]);
        return ['upcoming' => $up->fetchAll(), 'past' => $past->fetchAll()];
    }

    /** Only deletes when the plan belongs to the user (AC2.4). Returns true if a row was deleted. */
    public function deleteOwned(int $planId, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM hike_plan WHERE id = ? AND user_id = ?');
        $stmt->execute([$planId, $userId]);
        return $stmt->rowCount() === 1;
    }

    /** K1: share of upcoming plans whose latest assessment is complete. */
    public function completeness(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS total, COUNT(*) FILTER (WHERE a.is_complete) AS checked
             FROM hike_plan p
             LEFT JOIN LATERAL (SELECT is_complete FROM risk_assessment ra WHERE ra.plan_id = p.id
                                ORDER BY ra.created_at DESC, ra.id DESC LIMIT 1) a ON TRUE
             WHERE p.user_id = ? AND p.planned_date >= CURRENT_DATE'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return ['total' => (int) $row['total'], 'checked' => (int) $row['checked']];
    }
}
