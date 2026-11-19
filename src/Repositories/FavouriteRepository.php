<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;

/** AC1.18 saved trails. */
final class FavouriteRepository
{
    public function __construct(private PDO $db)
    {
    }

    /** Toggle; returns true when the trail is now saved. */
    public function toggle(int $userId, int $trailId): bool
    {
        $del = $this->db->prepare('DELETE FROM trail_favourite WHERE user_id = ? AND trail_id = ?');
        $del->execute([$userId, $trailId]);
        if ($del->rowCount() > 0) {
            return false;
        }
        $this->db->prepare('INSERT INTO trail_favourite (user_id, trail_id) VALUES (?, ?) ON CONFLICT DO NOTHING')->execute([$userId, $trailId]);
        return true;
    }

    public function isSaved(int $userId, int $trailId): bool
    {
        $s = $this->db->prepare('SELECT 1 FROM trail_favourite WHERE user_id = ? AND trail_id = ?');
        $s->execute([$userId, $trailId]);
        return (bool) $s->fetchColumn();
    }

    /** @return list<int> */
    public function ids(int $userId): array
    {
        $s = $this->db->prepare('SELECT trail_id FROM trail_favourite WHERE user_id = ?');
        $s->execute([$userId]);
        return array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
    }

    public function count(int $trailId): int
    {
        $s = $this->db->prepare('SELECT COUNT(*) FROM trail_favourite WHERE trail_id = ?');
        $s->execute([$trailId]);
        return (int) $s->fetchColumn();
    }
}
