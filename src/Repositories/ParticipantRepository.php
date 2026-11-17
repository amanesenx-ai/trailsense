<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;

/** F9.2: hikers joining organizer group hikes (many-to-many hike_plan × app_user). */
final class ParticipantRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** Upcoming group hikes open to everyone, with places left and whether $userId joined. */
    public function upcomingGroupHikes(int $userId, ?int $trailId = null, int $limit = 20): array
    {
        $sql = "SELECT p.id, p.planned_date, p.start_time, p.duration_min, t.id AS trail_id, t.name AS trail_name,
                       t.region, t.distance_km, t.difficulty, t.route_points, g.group_size, g.meeting_point, g.meeting_time,
                       u.id AS organizer_id, u.name AS organizer_name,
                       (SELECT COUNT(*) FROM hike_participant hp WHERE hp.plan_id = p.id) AS participants,
                       EXISTS (SELECT 1 FROM hike_participant hp WHERE hp.plan_id = p.id AND hp.user_id = ?) AS joined,
                       a.overall_level, a.is_complete
                FROM hike_plan p
                JOIN group_plan g ON g.plan_id = p.id
                JOIN trail t ON t.id = p.trail_id
                JOIN app_user u ON u.id = p.user_id
                LEFT JOIN LATERAL (SELECT overall_level, is_complete FROM risk_assessment ra WHERE ra.plan_id = p.id
                                   ORDER BY ra.created_at DESC, ra.id DESC LIMIT 1) a ON TRUE
                WHERE p.planned_date >= CURRENT_DATE" . ($trailId !== null ? ' AND p.trail_id = ?' : '') . "
                ORDER BY p.planned_date, p.start_time LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $i = 1;
        $stmt->bindValue($i++, $userId, PDO::PARAM_INT);
        if ($trailId !== null) {
            $stmt->bindValue($i++, $trailId, PDO::PARAM_INT);
        }
        $stmt->bindValue($i, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * AC9.5: joins only an upcoming group hike that still has places; never twice; not your own hike.
     * Locks the plan row so two people cannot take the last place at the same time.
     * @return string 'joined' | 'full' | 'already' | 'own' | 'not-open'
     */
    public function join(int $planId, int $userId): string
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT p.user_id, p.planned_date, g.group_size FROM hike_plan p JOIN group_plan g ON g.plan_id = p.id
                 WHERE p.id = ? FOR UPDATE OF p'
            );
            $stmt->execute([$planId]);
            $plan = $stmt->fetch();
            $result = match (true) {
                !$plan || $plan['planned_date'] < date('Y-m-d') => 'not-open',
                (int) $plan['user_id'] === $userId => 'own',
                $this->isParticipant($planId, $userId) => 'already',
                $this->count($planId) >= (int) $plan['group_size'] => 'full',
                default => 'joined',
            };
            if ($result === 'joined') {
                $this->db->prepare('INSERT INTO hike_participant (plan_id, user_id) VALUES (?, ?)')->execute([$planId, $userId]);
            }
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function leave(int $planId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM hike_participant hp USING hike_plan p
             WHERE hp.plan_id = p.id AND hp.plan_id = ? AND hp.user_id = ? AND p.planned_date >= CURRENT_DATE'
        );
        $stmt->execute([$planId, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function isParticipant(int $planId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM hike_participant WHERE plan_id = ? AND user_id = ?');
        $stmt->execute([$planId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function count(int $planId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM hike_participant WHERE plan_id = ?');
        $stmt->execute([$planId]);
        return (int) $stmt->fetchColumn();
    }

    /** AC9.6: organizer sees who is coming (names only, never e-mails). */
    public function participants(int $planId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, hp.joined_at FROM hike_participant hp JOIN app_user u ON u.id = hp.user_id
             WHERE hp.plan_id = ? ORDER BY hp.joined_at'
        );
        $stmt->execute([$planId]);
        return $stmt->fetchAll();
    }

    /** Group hikes a hiker joined (for "My plans"). */
    public function joinedBy(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.planned_date, p.start_time, t.name AS trail_name, t.distance_km, t.route_points,
                    g.group_size, g.meeting_point, g.meeting_time, u.name AS organizer_name,
                    (SELECT COUNT(*) FROM hike_participant x WHERE x.plan_id = p.id) AS participants,
                    a.overall_level, a.is_complete, NULL AS notes
             FROM hike_participant hp
             JOIN hike_plan p ON p.id = hp.plan_id
             JOIN group_plan g ON g.plan_id = p.id
             JOIN trail t ON t.id = p.trail_id
             JOIN app_user u ON u.id = p.user_id
             LEFT JOIN LATERAL (SELECT overall_level, is_complete FROM risk_assessment ra WHERE ra.plan_id = p.id
                                ORDER BY ra.created_at DESC, ra.id DESC LIMIT 1) a ON TRUE
             WHERE hp.user_id = ? AND p.planned_date >= CURRENT_DATE
             ORDER BY p.planned_date"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
