<?php
/**
 * Campus Market Hub - Recommendation Model
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class Recommendation extends Model {
    protected static string $table = 'recommendations';
    protected static string $primaryKey = 'RecommendationID';

    /**
     * Get tailored recommendations for a logged in user with fallback to trending
     */
    public static function getForUser(int $userId, int $limit = 6): array {
        $sql = "SELECT r.RecommendationScore, r.Reason, 
                       p.*, c.CategoryName, u.FullName as SellerName, u.IsVerified as SellerVerified
                FROM recommendations r
                JOIN products p ON r.ProductID = p.ProductID
                JOIN categories c ON p.CategoryID = c.CategoryID
                JOIN users u ON p.UserID = u.UserID
                WHERE r.UserID = ? AND p.Status = 'Available' AND p.UserID != ?
                ORDER BY r.RecommendationScore DESC, p.ProductID DESC
                LIMIT ?";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId, $userId, $limit]);
        $results = $stmt->fetchAll();

        // If personalized recommendations are fewer than requested limit, backfill with top trending items
        if (count($results) < $limit) {
            $existingIds = array_column($results, 'ProductID');
            $existingIds[] = 0; // prevent empty IN clause
            $needed = $limit - count($results);

            $inClause = implode(',', array_map('intval', $existingIds));
            $fallbackSql = "SELECT 0.75 as RecommendationScore, 'Popular on Campus' as Reason,
                                   p.*, c.CategoryName, u.FullName as SellerName, u.IsVerified as SellerVerified
                            FROM products p
                            JOIN categories c ON p.CategoryID = c.CategoryID
                            JOIN users u ON p.UserID = u.UserID
                            WHERE p.Status = 'Available' AND p.UserID != ? AND p.ProductID NOT IN ({$inClause})
                            ORDER BY p.ViewsCount DESC, p.ProductID DESC
                            LIMIT ?";
            $fallbackStmt = self::db()->prepare($fallbackSql);
            $fallbackStmt->execute([$userId, $needed]);
            $fallbacks = $fallbackStmt->fetchAll();

            $results = array_merge($results, $fallbacks);
        }

        return $results;
    }

    /**
     * Store or update recommendation score
     */
    public static function upsert(int $userId, int $productId, float $score, string $reason): void {
        $sql = "INSERT INTO recommendations (UserID, ProductID, RecommendationScore, Reason, DateGenerated)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    RecommendationScore = VALUES(RecommendationScore),
                    Reason = VALUES(Reason),
                    DateGenerated = NOW()";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId, $productId, $score, $reason]);
    }
}
