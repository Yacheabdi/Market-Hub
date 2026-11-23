<?php
/**
 * Campus Market Hub - User Model
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model {
    protected static string $table = 'users';
    protected static string $primaryKey = 'UserID';

    public static function findByEmail(string $email): ?array {
        $stmt = self::db()->prepare("SELECT * FROM users WHERE Email = ? LIMIT 1");
        $stmt->execute([trim(strtolower($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByRegNo(string $regNo): ?array {
        $stmt = self::db()->prepare("SELECT * FROM users WHERE Registration_StaffNo = ? LIMIT 1");
        $stmt->execute([trim($regNo)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getStats(int $userId): array {
        $db = self::db();

        // Total listings
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE UserID = ?");
        $stmt->execute([$userId]);
        $totalListings = (int) $stmt->fetchColumn();

        // Sold / Donated listings
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE UserID = ? AND Status = 'Sold'");
        $stmt->execute([$userId]);
        $itemsSold = (int) $stmt->fetchColumn();

        // Completed transactions as buyer or seller
        $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE (BuyerID = ? OR SellerID = ?) AND TransactionStatus = 'Completed'");
        $stmt->execute([$userId, $userId]);
        $completedTrans = (int) $stmt->fetchColumn();

        // Average Rating
        $stmt = $db->prepare("SELECT AVG(Rating) as avg_rating, COUNT(*) as review_count FROM complaints_reviews WHERE TargetUserID = ? AND Type = 'Review'");
        $stmt->execute([$userId]);
        $ratingData = $stmt->fetch();

        return [
            'total_listings'    => $totalListings,
            'items_sold'        => $itemsSold,
            'completed_trades'  => $completedTrans,
            'avg_rating'        => $ratingData['avg_rating'] ? round((float)$ratingData['avg_rating'], 1) : 5.0,
            'review_count'      => (int) ($ratingData['review_count'] ?? 0)
        ];
    }

    public static function getAllWithFilters(string $search = '', string $role = '', int $limit = 50, int $offset = 0): array {
        $sql = "SELECT UserID, FullName, Registration_StaffNo, Email, PhoneNumber, Role, IsVerified, CampusDepartment, CreatedAt FROM users WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (FullName LIKE ? OR Email LIKE ? OR Registration_StaffNo LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($role)) {
            $sql .= " AND Role = ?";
            $params[] = $role;
        }

        $sql .= " ORDER BY UserID DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function toggleVerification(int $userId): bool {
        $user = self::find($userId);
        if (!$user) return false;

        $newStatus = $user['IsVerified'] ? 0 : 1;
        return self::update($userId, ['IsVerified' => $newStatus]);
    }
}
