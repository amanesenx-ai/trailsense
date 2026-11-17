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

    /** @param array{name:string,mime:string,bytes:string}|null $photo */
    public function create(int $userId, array $data, ?int $planId, ?array $photo, string $photoAlt = ''): int
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
            if ($photo !== null) {
                $stmt = $this->db->prepare(
                    'INSERT INTO activity_photo (activity_id, file_name, mime_type, content, alt_text) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->bindValue(1, $id, \PDO::PARAM_INT);
                $stmt->bindValue(2, $photo['name']);
                $stmt->bindValue(3, $photo['mime']);
                $stmt->bindValue(4, $photo['bytes'], \PDO::PARAM_LOB);
                $stmt->bindValue(5, $photoAlt);
                $stmt->execute();
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

    /** @return array{mime:string,bytes:string}|null */
    public function photo(string $fileName): ?array
    {
        $stmt = $this->db->prepare('SELECT mime_type, content FROM activity_photo WHERE file_name = ?');
        $stmt->execute([$fileName]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $bytes = is_resource($row['content']) ? stream_get_contents($row['content']) : (string) $row['content'];
        return ['mime' => $row['mime_type'], 'bytes' => $bytes];
    }

    private function decode(array $row): array
    {
        $row['route_points'] = json_decode((string) $row['route_points'], true) ?: [];
        return $row;
    }
}
