<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;

/** F11 profile and statistics. Never returns e-mail addresses (AC11.2). */
final class ProfileRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function user(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, role, created_at FROM app_user WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Totals over published hikes; elevation gain comes from the trail. */
    public function stats(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS hikes, COALESCE(SUM(a.distance_km), 0) AS km,
                    COALESCE(SUM(t.elevation_gain_m), 0) AS gain_m, COALESCE(SUM(a.duration_min), 0) AS minutes,
                    COUNT(DISTINCT a.trail_id) AS trails
             FROM activity a JOIN trail t ON t.id = a.trail_id WHERE a.user_id = ?'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return ['hikes' => (int) $row['hikes'], 'km' => (float) $row['km'], 'gain_m' => (int) $row['gain_m'],
            'minutes' => (int) $row['minutes'], 'trails' => (int) $row['trails']];
    }

    /** Hikes per month for the last 6 months, including empty months (for the bar chart). */
    public function monthly(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT to_char(m, 'YYYY-MM') AS month, to_char(m, 'Mon') AS label,
                    COUNT(a.id) AS hikes, COALESCE(SUM(a.distance_km), 0) AS km
             FROM generate_series(date_trunc('month', CURRENT_DATE) - interval '5 months',
                                  date_trunc('month', CURRENT_DATE), interval '1 month') AS m
             LEFT JOIN activity a ON a.user_id = ? AND date_trunc('month', a.hiked_on) = m
             GROUP BY m ORDER BY m"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
