<?php
/**
 * Campus Market Hub - Complaints, Reviews & Fraud Reports Model
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class ComplaintReview extends Model {
    protected static string $table = 'complaints_reviews';
    protected static string $primaryKey = 'ReviewID';

    /**
     * Submit a peer review or fraud complaint
     */
    public static function submit(array $data): int {
        return self::create([
            'ReporterID'   => $data['ReporterID'],
            'TargetUserID' => $data['TargetUserID'] ?? null,
            'ProductID'    => $data['ProductID'] ?? null,
            'Type'         => $data['Type'] ?? 'Review',
            'Rating'       => !empty($data['Rating']) ? (int)$data['Rating'] : null,
            'Title'        => $data['Title'] ?? null,
            'Comment'      => trim($data['Comment']),
            'Status'       => $data['Status'] ?? 'Pending',
            'CreatedAt'    => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get reviews written about a target user
     */
    public static function getReviewsForUser(int $userId): array {
        $sql = "SELECT cr.*, u.FullName as ReporterName, u.Role as ReporterRole
                FROM complaints_reviews cr
                JOIN users u ON cr.ReporterID = u.UserID
                WHERE cr.TargetUserID = ? AND cr.Type = 'Review' AND cr.Status = 'Resolved'
                ORDER BY cr.CreatedAt DESC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get fraud complaints for Admin Panel
     */
    public static function getAdminReports(?string $status = null): array {
        $sql = "SELECT cr.*, 
                       rep.FullName as ReporterName, rep.Email as ReporterEmail, rep.Registration_StaffNo as ReporterRegNo,
                       tgt.FullName as AccusedName, tgt.Email as AccusedEmail, tgt.Registration_StaffNo as AccusedRegNo,
                       p.ProductName, p.Price as ProductPrice, p.ProductImage
                FROM complaints_reviews cr
                JOIN users rep ON cr.ReporterID = rep.UserID
                LEFT JOIN users tgt ON cr.TargetUserID = tgt.UserID
                LEFT JOIN products p ON cr.ProductID = p.ProductID
                WHERE cr.Type IN ('Complaint', 'FraudReport')";
        
        $params = [];
        if ($status !== null) {
            $sql .= " AND cr.Status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY cr.ReviewID DESC";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Update complaint status with admin note
     */
    public static function resolve(int $reviewId, string $status, ?string $adminNotes = null): bool {
        $data = ['Status' => $status];
        if ($adminNotes !== null) {
            $data['AdminNotes'] = $adminNotes;
        }
        return self::update($reviewId, $data);
    }
}
