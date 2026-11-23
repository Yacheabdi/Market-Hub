<?php
/**
 * Campus Market Hub - Base Database Model
 */

namespace App\Core;

use PDO;

abstract class Model {
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    /**
     * Get PDO connection
     */
    protected static function db(): PDO {
        return Database::getConnection();
    }

    /**
     * Find single record by primary key
     */
    public static function find($id): ?array {
        $stmt = self::db()->prepare("SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ? LIMIT 1");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        return $record ?: null;
    }

    /**
     * Find all records with optional ORDER BY and LIMIT
     */
    public static function all(string $orderBy = '', int $limit = 0): array {
        $sql = "SELECT * FROM " . static::$table;
        if (!empty($orderBy)) {
            $sql .= " ORDER BY " . $orderBy;
        }
        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = self::db()->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Query by key-value conditions
     */
    public static function where(array $conditions, string $orderBy = '', int $limit = 0): array {
        $sql = "SELECT * FROM " . static::$table . " WHERE ";
        $clauses = [];
        $values = [];

        foreach ($conditions as $column => $value) {
            $clauses[] = "{$column} = ?";
            $values[] = $value;
        }

        $sql .= implode(' AND ', $clauses);

        if (!empty($orderBy)) {
            $sql .= " ORDER BY " . $orderBy;
        }
        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll();
    }

    /**
     * Find first record matching conditions
     */
    public static function findWhere(array $conditions): ?array {
        $results = self::where($conditions, '', 1);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Insert a new record
     */
    public static function create(array $data): int {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            "INSERT INTO %s (`%s`) VALUES (%s)",
            static::$table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($data));
        return (int) self::db()->lastInsertId();
    }

    /**
     * Update record by primary key
     */
    public static function update($id, array $data): bool {
        $clauses = [];
        $values = [];

        foreach ($data as $column => $value) {
            $clauses[] = "`{$column}` = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $sql = sprintf(
            "UPDATE %s SET %s WHERE `%s` = ?",
            static::$table,
            implode(', ', $clauses),
            static::$primaryKey
        );

        $stmt = self::db()->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete record by primary key
     */
    public static function delete($id): bool {
        $sql = "DELETE FROM " . static::$table . " WHERE `" . static::$primaryKey . "` = ?";
        $stmt = self::db()->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Count records matching optional conditions
     */
    public static function count(array $conditions = []): int {
        $sql = "SELECT COUNT(*) as cnt FROM " . static::$table;
        $values = [];

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $column => $value) {
                $clauses[] = "`{$column}` = ?";
                $values[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $clauses);
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($values);
        $row = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }
}
