<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;

/** F6.2 likes and comments on published hikes. */
final class SocialRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** Toggles a like; returns true if the post is now liked by the user (AC6.4: once per user, by PK). */
    public function toggleLike(int $activityId, int $userId): bool
    {
        $del = $this->db->prepare('DELETE FROM activity_like WHERE activity_id = ? AND user_id = ?');
        $del->execute([$activityId, $userId]);
        if ($del->rowCount() === 1) {
            return false;
        }
        $this->db->prepare('INSERT INTO activity_like (activity_id, user_id) VALUES (?, ?) ON CONFLICT DO NOTHING')
            ->execute([$activityId, $userId]);
        return true;
    }

    public function likeCount(int $activityId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM activity_like WHERE activity_id = ?');
        $stmt->execute([$activityId]);
        return (int) $stmt->fetchColumn();
    }

    public function likedBy(int $activityId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM activity_like WHERE activity_id = ? AND user_id = ?');
        $stmt->execute([$activityId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function addComment(int $activityId, int $userId, string $body): int
    {
        $stmt = $this->db->prepare('INSERT INTO activity_comment (activity_id, user_id, body) VALUES (?, ?, ?) RETURNING id');
        $stmt->execute([$activityId, $userId, $body]);
        return (int) $stmt->fetchColumn();
    }

    /** Only the author can delete their own comment. Returns the activity id, or null if nothing was deleted. */
    public function deleteOwnComment(int $commentId, int $userId): ?int
    {
        $stmt = $this->db->prepare('DELETE FROM activity_comment WHERE id = ? AND user_id = ? RETURNING activity_id');
        $stmt->execute([$commentId, $userId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function comments(int $activityId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.body, c.created_at, c.user_id, u.name AS author
             FROM activity_comment c JOIN app_user u ON u.id = c.user_id
             WHERE c.activity_id = ? ORDER BY c.created_at, c.id'
        );
        $stmt->execute([$activityId]);
        return $stmt->fetchAll();
    }
}
