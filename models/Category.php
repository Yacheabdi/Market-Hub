<?php
/**
 * Campus Market Hub - Category Model
 */

namespace App\Models;

use App\Core\Model;

class Category extends Model {
    protected static string $table = 'categories';
    protected static string $primaryKey = 'CategoryID';

    public static function allWithCounts(): array {
        $sql = "SELECT c.*, COUNT(p.ProductID) AS product_count 
                FROM categories c 
                LEFT JOIN products p ON c.CategoryID = p.CategoryID AND p.Status = 'Available'
                GROUP BY c.CategoryID 
                ORDER BY c.DisplayOrder ASC, c.CategoryName ASC";
        $stmt = self::db()->query($sql);
        return $stmt->fetchAll();
    }

    public static function findBySlug(string $slug): ?array {
        $stmt = self::db()->prepare("SELECT * FROM categories WHERE CategorySlug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
