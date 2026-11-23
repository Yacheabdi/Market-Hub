<?php
/**
 * Campus Market Hub - Transaction Model
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class Transaction extends Model {
    protected static string $table = 'transactions';
    protected static string $primaryKey = 'TransactionID';

    /**
     * Get user transactions (both purchases/claims and sales/exchanges)
     */
    public static function getUserTransactions(int $userId, ?string $status = null): array {
        $sql = "SELECT t.*, 
                       p.ProductName, p.ProductImage, p.ListingType, p.Price as ProductPrice,
                       buyer.FullName as BuyerName, buyer.Email as BuyerEmail, buyer.PhoneNumber as BuyerPhone,
                       seller.FullName as SellerName, seller.Email as SellerEmail, seller.PhoneNumber as SellerPhone
                FROM transactions t
                JOIN products p ON t.ProductID = p.ProductID
                JOIN users buyer ON t.BuyerID = buyer.UserID
                JOIN users seller ON t.SellerID = seller.UserID
                WHERE (t.BuyerID = ? OR t.SellerID = ?)";
        
        $params = [$userId, $userId];

        if ($status !== null) {
            $sql .= " AND t.TransactionStatus = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY t.TransactionDate DESC";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get single transaction with full relations
     */
    public static function getDetails(int $id): ?array {
        $sql = "SELECT t.*, 
                       p.ProductName, p.ProductImage, p.ListingType, p.Price as ProductPrice, p.Status as ProductStatus,
                       buyer.FullName as BuyerName, buyer.Registration_StaffNo as BuyerRegNo, buyer.Email as BuyerEmail, buyer.PhoneNumber as BuyerPhone,
                       seller.FullName as SellerName, seller.Registration_StaffNo as SellerRegNo, seller.Email as SellerEmail, seller.PhoneNumber as SellerPhone
                FROM transactions t
                JOIN products p ON t.ProductID = p.ProductID
                JOIN users buyer ON t.BuyerID = buyer.UserID
                JOIN users seller ON t.SellerID = seller.UserID
                WHERE t.TransactionID = ? LIMIT 1";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Update transaction status & optionally update product status to 'Sold' upon completion
     */
    public static function setStatus(int $transactionId, string $status): bool {
        $completedAt = ($status === 'Completed') ? date('Y-m-d H:i:s') : null;

        $db = self::db();
        $stmt = $db->prepare("UPDATE transactions SET TransactionStatus = ?, CompletedAt = ? WHERE TransactionID = ?");
        $updated = $stmt->execute([$status, $completedAt, $transactionId]);

        if ($updated && $status === 'Completed') {
            // Automatically mark associated product as Sold
            $trans = self::find($transactionId);
            if ($trans && !empty($trans['ProductID'])) {
                Product::update($trans['ProductID'], ['Status' => 'Sold']);
            }
        }

        return $updated;
    }

    /**
     * Summary counts for Admin Dashboard
     */
    public static function getPlatformMetrics(): array {
        $db = self::db();

        $metrics = [];
        $stmt = $db->query("SELECT TransactionStatus, COUNT(*) as cnt FROM transactions GROUP BY TransactionStatus");
        while ($row = $stmt->fetch()) {
            $metrics[strtolower($row['TransactionStatus'])] = (int)$row['cnt'];
        }

        $stmtTotal = $db->query("SELECT SUM(Amount) as total_volume FROM transactions WHERE TransactionStatus = 'Completed'");
        $metrics['total_volume'] = (float)($stmtTotal->fetchColumn() ?: 0.0);

        return $metrics;
    }
}
