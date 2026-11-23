<?php
/**
 * Campus Market Hub - User Activity Log Model
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class UserActivityLog extends Model {
    protected static string $table = 'user_activity_logs';
    protected static string $primaryKey = 'LogID';

    /**
     * Record an interaction event
     */
    public static function record(?int $userId, string $type, ?int $productId = null, ?int $categoryId = null, ?string $query = null): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250);

        self::create([
            'UserID'       => $userId ?: null,
            'ActivityType' => $type,
            'ProductID'    => $productId ?: null,
            'CategoryID'   => $categoryId ?: null,
            'SearchQuery'  => $query ? substr($query, 0, 250) : null,
            'IpAddress'    => $ip,
            'UserAgent'    => $ua,
            'CreatedAt'    => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get aggregate category affinities for a user
     */
    public static function getUserCategoryAffinities(int $userId): array {
        $sql = "SELECT CategoryID, COUNT(*) as interaction_weight 
                FROM user_activity_logs 
                WHERE UserID = ? AND CategoryID IS NOT NULL
                GROUP BY CategoryID 
                ORDER BY interaction_weight DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get recent search keywords for a user
     */
    public static function getUserRecentSearches(int $userId, int $limit = 5): array {
        $sql = "SELECT DISTINCT SearchQuery 
                FROM user_activity_logs 
                WHERE UserID = ? AND SearchQuery IS NOT NULL AND TRIM(SearchQuery) != ''
                ORDER BY LogID DESC LIMIT ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
