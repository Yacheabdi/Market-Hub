<?php
/**
 * Campus Market Hub - Real-Time Message Model
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class Message extends Model {
    protected static string $table = 'messages';
    protected static string $primaryKey = 'MessageID';

    /**
     * Get inbox conversation threads for a user
     */
    public static function getConversationsForUser(int $userId): array {
        $sql = "SELECT 
                    m.MessageID,
                    m.ProductID,
                    m.MessageText,
                    m.DateSent,
                    m.IsRead,
                    CASE 
                        WHEN m.SenderID = :uid THEN m.ReceiverID 
                        ELSE m.SenderID 
                    END AS InterlocutorID,
                    u.FullName AS InterlocutorName,
                    u.Role AS InterlocutorRole,
                    u.IsVerified AS InterlocutorVerified,
                    p.ProductName,
                    p.Price AS ProductPrice,
                    p.ProductImage,
                    (SELECT COUNT(*) FROM messages unread 
                     WHERE unread.ReceiverID = :uid 
                       AND unread.SenderID = InterlocutorID 
                       AND (unread.ProductID = m.ProductID OR (unread.ProductID IS NULL AND m.ProductID IS NULL))
                       AND unread.IsRead = 0) AS UnreadCount
                FROM messages m
                JOIN (
                    SELECT 
                        MAX(MessageID) AS MaxID
                    FROM messages
                    WHERE SenderID = :uid OR ReceiverID = :uid
                    GROUP BY 
                        CASE WHEN SenderID = :uid THEN ReceiverID ELSE SenderID END,
                        IFNULL(ProductID, 0)
                ) latest ON m.MessageID = latest.MaxID
                JOIN users u ON u.UserID = (CASE WHEN m.SenderID = :uid THEN m.ReceiverID ELSE m.SenderID END)
                LEFT JOIN products p ON m.ProductID = p.ProductID
                ORDER BY m.DateSent DESC";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all messages in a specific thread between two users
     */
    public static function getThread(int $user1, int $user2, ?int $productId = null): array {
        $sql = "SELECT m.*, u.FullName as SenderName 
                FROM messages m
                JOIN users u ON m.SenderID = u.UserID
                WHERE ((m.SenderID = ? AND m.ReceiverID = ?) OR (m.SenderID = ? AND m.ReceiverID = ?))";
        
        $params = [$user1, $user2, $user2, $user1];

        if ($productId) {
            $sql .= " AND (m.ProductID = ? OR m.ProductID IS NULL)";
            $params[] = $productId;
        }

        $sql .= " ORDER BY m.MessageID ASC";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Send a new message
     */
    public static function send(int $senderId, int $receiverId, ?int $productId, string $text): int {
        return self::create([
            'SenderID'    => $senderId,
            'ReceiverID'  => $receiverId,
            'ProductID'   => $productId ?: null,
            'MessageText' => trim($text),
            'DateSent'    => date('Y-m-d H:i:s'),
            'IsRead'      => 0
        ]);
    }

    /**
     * Mark incoming messages as read in a thread
     */
    public static function markThreadRead(int $currentUserId, int $otherUserId, ?int $productId = null): void {
        $sql = "UPDATE messages SET IsRead = 1 
                WHERE ReceiverID = ? AND SenderID = ? AND IsRead = 0";
        $params = [$currentUserId, $otherUserId];

        if ($productId) {
            $sql .= " AND (ProductID = ? OR ProductID IS NULL)";
            $params[] = $productId;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Total unread messages count for navbar notification badge
     */
    public static function getUnreadCount(int $userId): int {
        $stmt = self::db()->prepare("SELECT COUNT(*) FROM messages WHERE ReceiverID = ? AND IsRead = 0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Poll for newly received messages since last message ID
     */
    public static function pollNewMessages(int $userId, int $lastMessageId, ?int $otherUserId = null, ?int $productId = null): array {
        $sql = "SELECT m.*, u.FullName as SenderName 
                FROM messages m
                JOIN users u ON m.SenderID = u.UserID
                WHERE m.ReceiverID = ? AND m.MessageID > ?";
        $params = [$userId, $lastMessageId];

        if ($otherUserId) {
            $sql .= " AND m.SenderID = ?";
            $params[] = $otherUserId;
        }

        if ($productId) {
            $sql .= " AND (m.ProductID = ? OR m.ProductID IS NULL)";
            $params[] = $productId;
        }

        $sql .= " ORDER BY m.MessageID ASC";

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
