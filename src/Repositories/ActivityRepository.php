<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;

final class ActivityRepository
{
    private const SELECT = 'SELECT a.*, u.name AS author, u.role AS author_role,
            t.name AS trail_name, t.region, t.difficulty, t.route_points,
            (SELECT file_name FROM activity_photo ph WHERE ph.activity_id = a.id ORDER BY ph.id LIMIT 1) AS photo,
            (SELECT alt_text FROM activity_photo ph WHERE ph.activity_id = a.id ORDER BY ph.id LIMIT 1) AS photo_alt
        FROM activity a
        JOIN app_user u ON u.id = a.user_id
        JOIN trail t ON t.id = a.trail_id';

    public function __construct(private readonly PDO $db)
    {
    }

    public function create(int $userId, array $data, ?int $planId, ?string $photoFile, string $photoAlt = ''): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO activity (user_id, trail_id, plan_id, hiked_on, distance_km, duration_min, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id'
            );
            $stmt->execute([$userId, $data['trail_id'], $planId, $data['hiked_on'], $data['distance_km'],
                $data['duration_min'], $data['notes']]);
            $id = (int) $stmt->fetchColumn();
            if ($photoFile !== null) {
                $this->db->prepare('INSERT INTO activity_photo (activity_id, file_name, alt_text) VALUES (?, ?, ?)')
                    ->execute([$id, $photoFile, $photoAlt]);
            }
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** AC6.1: newest first. */
    public function feed(int $limit = 30): array
    {
        $stmt = $this->db->prepare(self::SELECT . ' ORDER BY a.created_at DESC, a.id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'decode'], $stmt->fetchAll());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE a.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->decode($row) : null;
    }

    private function decode(array $row): array
    {
        $row['route_points'] = json_decode((string) $row['route_points'], true) ?: [];
        return $row;
    }
}
