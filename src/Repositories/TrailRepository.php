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
        $sql = 'SELECT id, name, region, distance_km, elevation_gain_m, est_duration_min, difficulty, route_points
                FROM trail WHERE TRUE';
        $params = [];
        if ($text !== '') {
            $sql .= ' AND (name ILIKE ? OR region ILIKE ?)';
            $like = '%' . addcslashes($text, '%_\\') . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if ($difficulty !== null) {
            $sql .= ' AND difficulty = ?';
            $params[] = $difficulty;
        }
        if ($maxKm !== null) {
            $sql .= ' AND distance_km <= ?';
            $params[] = $maxKm;
        }
        $sql .= " ORDER BY array_position(ARRAY['easy','moderate','hard']::varchar[], difficulty), distance_km";
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
