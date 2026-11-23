<?php
declare(strict_types=1);

namespace App\Core;

use SessionHandlerInterface;

// Stores PHP sessions in tblSessions instead of files. Required on
// Vercel, where each request can land on a fresh server with no shared
// filesystem, so file sessions would randomly log users out. Every method
// fails soft (empty session / false) so a missing table or a down
// database never turns a page load into a fatal error.
final class DbSessionHandler implements SessionHandlerInterface
{
    public function __construct(private Database $db) {}

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $row = $this->db->one(
                'SELECT data FROM tblSessions WHERE id = ? AND last_activity > ?',
                [$id, time() - $this->lifetime()]
            );
            return $row ? (string) $row['data'] : '';
        } catch (\Throwable) {
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $now = time();
            if ($this->db->driver() === 'sqlite') {
                $this->db->run(
                    'INSERT INTO tblSessions (id, data, last_activity) VALUES (?, ?, ?)
                     ON CONFLICT(id) DO UPDATE SET data = excluded.data, last_activity = excluded.last_activity',
                    [$id, $data, $now]
                );
            } else {
                $this->db->run(
                    'INSERT INTO tblSessions (id, data, last_activity) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = VALUES(last_activity)',
                    [$id, $data, $now]
                );
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $this->db->run('DELETE FROM tblSessions WHERE id = ?', [$id]);
        } catch (\Throwable) {
        }
        return true;
    }

    public function gc(int $maxLifetime): int|false
    {
        try {
            return $this->db->run(
                'DELETE FROM tblSessions WHERE last_activity < ?',
                [time() - $maxLifetime]
            );
        } catch (\Throwable) {
            return false;
        }
    }

    private function lifetime(): int
    {
        $v = (int) ini_get('session.gc_maxlifetime');
        return $v > 0 ? $v : 1440;
    }
}
