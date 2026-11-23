<?php
/**
 * Campus Market Hub - Product Model
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class Product extends Model {
    protected static string $table = 'products';
    protected static string $primaryKey = 'ProductID';

    /**
     * Retrieve products with seller information and category details for marketplace
     */
    public static function getMarketplaceListings(array $filters = [], int $limit = 12, int $offset = 0): array {
        $sql = "SELECT p.*, c.CategoryName, c.CategorySlug, u.FullName as SellerName, 
                       u.Registration_StaffNo as SellerRegNo, u.IsVerified as SellerVerified,
                       u.Role as SellerRole
                FROM products p
                JOIN categories c ON p.CategoryID = c.CategoryID
                JOIN users u ON p.UserID = u.UserID
                WHERE 1=1";

        $params = [];

        // Filter: Status (defaults to Available unless specified)
        if (!empty($filters['status'])) {
            $sql .= " AND p.Status = ?";
            $params[] = $filters['status'];
        } else {
            $sql .= " AND p.Status = 'Available'";
        }

        // Filter: Category
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.CategoryID = ?";
            $params[] = (int)$filters['category_id'];
        }

        // Filter: Listing Type (Sell, Exchange, Donate, Service)
        if (!empty($filters['listing_type'])) {
            $sql .= " AND p.ListingType = ?";
            $params[] = $filters['listing_type'];
        }

        // Filter: Price Min
        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $sql .= " AND p.Price >= ?";
            $params[] = (float)$filters['min_price'];
        }

        // Filter: Price Max
        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $sql .= " AND p.Price <= ?";
            $params[] = (float)$filters['max_price'];
        }

        // Filter: Search Keyword
        if (!empty($filters['search'])) {
            $sql .= " AND (p.ProductName LIKE ? OR p.Description LIKE ? OR c.CategoryName LIKE ?)";
            $term = "%" . trim($filters['search']) . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $sql .= " ORDER BY p.Price ASC, p.ProductID DESC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.Price DESC, p.ProductID DESC";
                break;
            case 'popular':
                $sql .= " ORDER BY p.ViewsCount DESC, p.ProductID DESC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY p.ProductID DESC";
                break;
        }

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Total count matching the given filters for pagination
     */
    public static function countMarketplaceListings(array $filters = []): int {
        $sql = "SELECT COUNT(*) as total 
                FROM products p
                JOIN categories c ON p.CategoryID = c.CategoryID
                JOIN users u ON p.UserID = u.UserID
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.Status = ?";
            $params[] = $filters['status'];
        } else {
            $sql .= " AND p.Status = 'Available'";
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.CategoryID = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['listing_type'])) {
            $sql .= " AND p.ListingType = ?";
            $params[] = $filters['listing_type'];
        }

        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $sql .= " AND p.Price >= ?";
            $params[] = (float)$filters['min_price'];
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $sql .= " AND p.Price <= ?";
            $params[] = (float)$filters['max_price'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.ProductName LIKE ? OR p.Description LIKE ? OR c.CategoryName LIKE ?)";
            $term = "%" . trim($filters['search']) . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Get Featured Items
     */
    public static function getFeatured(int $limit = 6): array {
        $sql = "SELECT p.*, c.CategoryName, u.FullName as SellerName, u.IsVerified as SellerVerified
                FROM products p
                JOIN categories c ON p.CategoryID = c.CategoryID
                JOIN users u ON p.UserID = u.UserID
                WHERE p.Status = 'Available' AND p.IsFeatured = 1
                ORDER BY p.ProductID DESC LIMIT ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get Popular & Trending Items
     */
    public static function getTrending(int $limit = 6): array {
        $sql = "SELECT p.*, c.CategoryName, u.FullName as SellerName, u.IsVerified as SellerVerified
                FROM products p
                JOIN categories c ON p.CategoryID = c.CategoryID
                JOIN users u ON p.UserID = u.UserID
                WHERE p.Status = 'Available'
                ORDER BY p.ViewsCount DESC, p.ProductID DESC LIMIT ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get full product details including seller profile and category
     */
    public static function getByIdWithDetails(int $id): ?array {
        $sql = "SELECT p.*, c.CategoryName, c.CategorySlug, 
                       u.FullName as SellerName, u.Email as SellerEmail, 
                       u.PhoneNumber as SellerPhone, u.Registration_StaffNo as SellerRegNo,
                       u.Role as SellerRole, u.IsVerified as SellerVerified,
                       u.CampusDepartment as SellerDepartment, u.CreatedAt as SellerJoinedAt
                FROM products p
                JOIN categories c ON p.CategoryID = c.CategoryID
                JOIN users u ON p.UserID = u.UserID
                WHERE p.ProductID = ? LIMIT 1";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Increment view counter safely
     */
    public static function incrementViews(int $id): void {
        $stmt = self::db()->prepare("UPDATE products SET ViewsCount = ViewsCount + 1 WHERE ProductID = ?");
        $stmt->execute([$id]);
    }

    /**
     * Get listings posted by a specific user
     */
    public static function getUserListings(int $userId, ?string $status = null): array {
        $sql = "SELECT p.*, c.CategoryName 
                FROM products p
                JOIN categories c ON p.CategoryID = c.CategoryID
                WHERE p.UserID = ?";
        $params = [$userId];

        if ($status !== null) {
            $sql .= " AND p.Status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY p.ProductID DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Related items in same category
     */
    public static function getRelated(int $productId, int $categoryId, int $limit = 4): array {
        $sql = "SELECT p.*, c.CategoryName, u.FullName as SellerName, u.IsVerified as SellerVerified
                FROM products p
                JOIN categories c ON p.CategoryID = c.CategoryID
                JOIN users u ON p.UserID = u.UserID
                WHERE p.CategoryID = ? AND p.ProductID != ? AND p.Status = 'Available'
                ORDER BY p.ProductID DESC LIMIT ?";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$categoryId, $productId, $limit]);
        return $stmt->fetchAll();
    }
}
