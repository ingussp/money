<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $db = $GLOBALS['config']['db'];
            self::$connection = new PDO(
                "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
                $db['user'], $db['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES => false]
            );
            self::$connection->exec("SET time_zone = '+00:00'");
        }
        return self::$connection;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        return self::query($sql, $params)->fetch() ?: null;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        $columns = implode(',', array_keys($data));
        $values = implode(',', array_fill(0, count($data), '?'));
        self::query("INSERT INTO $table ($columns) VALUES ($values)", array_values($data));
        return (int) self::connection()->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        $db = self::connection();
        $db->beginTransaction();
        try {
            $result = $callback();
            $db->commit();
            return $result;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
