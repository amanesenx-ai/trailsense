<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;

final class TrailRepository
{
    public const DIFFICULTIES = ['easy', 'moderate', 'hard'];

    public function __construct(private readonly PDO $db)
    {
    }

    /** F1: combined filters (AC1.2). Invalid values must already be dropped by the caller (AC1.5). */
    public function search(string $text = '', ?string $difficulty = null, ?float $maxKm = null): array
    {
        $sql = 'SELECT t.id, t.name, t.region, t.distance_km, t.elevation_gain_m, t.est_duration_min, t.difficulty,
                       t.route_points, t.ref, t.is_loop,
                       (SELECT thumb_url FROM trail_photo ph WHERE ph.trail_id = t.id ORDER BY position LIMIT 1) AS thumb_url,
                       (SELECT alt_text FROM trail_photo ph WHERE ph.trail_id = t.id ORDER BY position LIMIT 1) AS thumb_alt
                FROM trail t WHERE TRUE';
        $params = [];
        if ($text !== '') {
            $sql .= ' AND (t.name ILIKE ? OR t.region ILIKE ? OR t.ref ILIKE ?)';
            $like = '%' . addcslashes($text, '%_\\') . '%';
            array_push($params, $like, $like, $like);
        }
        if ($difficulty !== null) {
            $sql .= ' AND t.difficulty = ?';
            $params[] = $difficulty;
        }
        if ($maxKm !== null) {
            $sql .= ' AND t.distance_km <= ?';
            $params[] = $maxKm;
        }
        $sql .= " ORDER BY array_position(ARRAY['easy','moderate','hard']::varchar[], t.difficulty), t.distance_km";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'decode'], $stmt->fetchAll());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM trail WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->decode($row) : null;
    }

    /** F1.3 licensed photos with attribution. */
    public function photos(int $trailId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM trail_photo WHERE trail_id = ? ORDER BY position');
        $stmt->execute([$trailId]);
        return $stmt->fetchAll();
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM trail WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    /** Options for <select>. */
    public function options(): array
    {
        return $this->db->query(
            'SELECT id, name, region, distance_km, est_duration_min FROM trail ORDER BY name'
        )->fetchAll();
    }

    public function regions(): array
    {
        return $this->db->query('SELECT DISTINCT region FROM trail ORDER BY region')->fetchAll(PDO::FETCH_COLUMN);
    }

    private function decode(array $row): array
    {
        $row['route_points'] = json_decode((string) ($row['route_points'] ?? '[]'), true) ?: [];
        return $row;
    }
}
