<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM app_user WHERE email = ?');
        $stmt->execute([mb_strtolower($email)]);
        return $stmt->fetch() ?: null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function create(string $name, string $email, string $password, string $role): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO app_user (name, email, password_hash, role) VALUES (?, ?, ?, ?) RETURNING id'
        );
        $stmt->execute([$name, mb_strtolower($email), password_hash($password, PASSWORD_DEFAULT), $role]);
        return (int) $stmt->fetchColumn();
    }

    /** AC8.5 */
    public function updatePassword(int $id, string $password): void
    {
        $this->db->prepare('UPDATE app_user SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public function passwordHash(int $id): ?string
    {
        $s = $this->db->prepare('SELECT password_hash FROM app_user WHERE id = ?');
        $s->execute([$id]);
        $h = $s->fetchColumn();
        return $h === false ? null : (string) $h;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, email, role FROM app_user WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
