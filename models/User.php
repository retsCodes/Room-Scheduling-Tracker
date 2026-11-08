<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** tblUsers */
final class User
{
    public const ROLES = ['admin', 'teacher', 'student', 'scheduler', 'viewer'];

    public function __construct(private Database $db) {}

    public function find(int $id): ?array
    {
        return $this->db->one(
            'SELECT user_id, username, email, profile_img, role, section, org_name, phone_number
             FROM tblUsers WHERE user_id = ?',
            [$id]
        );
    }

    public function findByLogin(string $login): ?array
    {
        return $this->db->one(
            'SELECT * FROM tblUsers WHERE username = ? OR email = ? LIMIT 1',
            [$login, $login]
        );
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->one('SELECT * FROM tblUsers WHERE username = ? LIMIT 1', [$username]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->one('SELECT * FROM tblUsers WHERE email = ? LIMIT 1', [$email]);
    }

    /** @return array<int, array> */
    public function all(): array
    {
        return $this->db->all(
            'SELECT user_id, username, email, profile_img, role, section, org_name, phone_number
             FROM tblUsers ORDER BY username'
        );
    }

    public function create(array $data): int
    {
        $this->db->run(
            'INSERT INTO tblUsers (username, email, password_hash, profile_img, role, section, org_name, phone_number)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['profile_img'] ?? null,
                $data['role'] ?? 'student',
                $data['section'] ?? null,
                $data['org_name'] ?? null,
                $data['phone_number'] ?? null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->run(
            'UPDATE tblUsers SET username = ?, email = ?, role = ?, section = ?, org_name = ?, phone_number = ?
             WHERE user_id = ?',
            [$data['username'], $data['email'], $data['role'], $data['section'] ?? null,
             $data['org_name'] ?? null, $data['phone_number'] ?? null, $id]
        );
    }

    public function updatePassword(int $id, string $password): void
    {
        $this->db->run(
            'UPDATE tblUsers SET password_hash = ? WHERE user_id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->run('DELETE FROM tblUsers WHERE user_id = ?', [$id]);
    }

    public function verifyPassword(array $userRow, string $password): bool
    {
        return password_verify($password, $userRow['password_hash']);
    }
}
