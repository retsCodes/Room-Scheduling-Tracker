<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;


final class Database
{
    private static ?self $instance = null;
    private PDO $pdo;

    private function __construct(array $db)
    {
        $driver = $db['driver'] ?? 'mysql';
        try {
            if ($driver === 'sqlite') {
                $dir = dirname($db['path']);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $this->pdo = new PDO('sqlite:' . $db['path']);
                $this->pdo->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $db['host'], $db['port'], $db['database'], $db['charset']
                );
                $this->pdo = new PDO($dsn, $db['username'], $db['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                return;
            }
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            throw new PDOException(
                'Could not connect to the database. Check your .env DB_* settings '
                . 'and make sure the database from database/rst_db.sql exists. '
                . 'Driver error: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            if ($config === []) {
                $config = require dirname(__DIR__) . '/config/config.php';
            }
            self::$instance = new self($config['db']);
        }
        return self::$instance;
    }

    
    public static function reset(): void
    {
        self::$instance = null;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    
    public function all(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    
    public function one(string $sql, array $params = []): ?array
    {
        $rows = $this->all($sql, $params);
        return $rows[0] ?? null;
    }

    
    public function run(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function begin(): void  { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
