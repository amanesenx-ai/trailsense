<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;

/** F10 community trail condition reports. */
final class TrailReportRepository
{
    public const CONDITIONS = [
        'good' => 'Good', 'muddy' => 'Muddy / slippery', 'obstacle' => 'Obstacle (fallen tree, rocks)',
        'overgrown' => 'Overgrown', 'closed' => 'Closed',
    ];

    public function __construct(private readonly PDO $db)
    {
    }

    public function create(int $trailId, int $userId, string $condition, string $note): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO trail_report (trail_id, user_id, condition, note) VALUES (?, ?, ?, ?) RETURNING id'
        );
        $stmt->execute([$trailId, $userId, $condition, $note]);
        return (int) $stmt->fetchColumn();
    }

    /** AC10.2: most recent first, with author name and age in days. */
    public function recent(int $trailId, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.id, r.condition, r.note, r.created_at, u.id AS user_id, u.name AS author,
                    (CURRENT_DATE - r.created_at::date) AS age_days
             FROM trail_report r JOIN app_user u ON u.id = r.user_id
             WHERE r.trail_id = ? ORDER BY r.created_at DESC, r.id DESC LIMIT ?"
        );
        $stmt->bindValue(1, $trailId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** AC10.3: latest report of the last $days days, or null. */
    public function latestWithin(int $trailId, int $days = 14): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.condition, r.note, r.created_at, u.name AS author, (CURRENT_DATE - r.created_at::date) AS age_days
             FROM trail_report r JOIN app_user u ON u.id = r.user_id
             WHERE r.trail_id = ? AND r.created_at >= now() - make_interval(days => ?)
             ORDER BY r.created_at DESC, r.id DESC LIMIT 1"
        );
        $stmt->execute([$trailId, $days]);
        return $stmt->fetch() ?: null;
    }

    public function closedRecently(int $trailId, int $days = 14): bool
    {
        return ($this->latestWithin($trailId, $days)['condition'] ?? '') === 'closed';
    }
}
