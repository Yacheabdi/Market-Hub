<?php
/**
 * Campus Market Hub - Intelligent Recommendation Engine
 * Lightweight, native PHP/MySQL Content-Based & Collaborative Filtering Service.
 * Runs completely locally on XAMPP/Apache without requiring Python or external ML microservices.
 */

namespace App\Services;

use App\Core\Database;
use App\Models\Recommendation;
use PDO;

class RecommendationEngine {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Compute and refresh recommendation scores for a specific user
     */
    public function computeForUser(int $userId): array {
        // Step 1: Collect user's category affinity scores from user_activity_logs and transactions
        $categoryScores = $this->getCategoryAffinities($userId);

        // Step 2: Collect user's recent search terms
        $searchTerms = $this->getUserSearchKeywords($userId);

        // Step 3: Find candidate available items (excluding user's own products and already purchased items)
        $candidates = $this->getCandidateProducts($userId);
        if (empty($candidates)) {
            return [];
        }

        // Step 4: Calculate composite scores
        $scoredProducts = [];
        foreach ($candidates as $product) {
            $score = 0.0;
            $reasons = [];

            // A. Category Affinity Signal (Weight: up to 0.45)
            $catId = (int)$product['CategoryID'];
            if (isset($categoryScores[$catId])) {
                $normCatScore = min(1.0, $categoryScores[$catId] / 10.0);
                $catContribution = $normCatScore * 0.45;
                $score += $catContribution;
                if ($catContribution >= 0.20) {
                    $reasons[] = "Matches your high interest in " . $product['CategoryName'];
                }
            }

            // B. Search Term Match Signal (Weight: up to 0.30)
            if (!empty($searchTerms)) {
                $titleWords = preg_split('/\s+/', strtolower($product['ProductName'] . ' ' . $product['Description']));
                $matchedKeywords = [];
                foreach ($searchTerms as $term) {
                    $tokens = preg_split('/\s+/', strtolower($term));
                    foreach ($tokens as $token) {
                        if (strlen($token) >= 3 && in_array($token, $titleWords)) {
                            $matchedKeywords[] = $token;
                        }
                    }
                }
                if (!empty($matchedKeywords)) {
                    $score += 0.30;
                    $reasons[] = "Matches your recent search for '" . htmlspecialchars($matchedKeywords[0]) . "'";
                }
            }

            // C. Global Popularity & Campus Trending Signal (Weight: up to 0.15)
            $views = (int)$product['ViewsCount'];
            $popScore = min(0.15, log10($views + 1) * 0.06);
            $score += $popScore;

            // D. Verified Seller Trust Boost (+0.05)
            if (!empty($product['SellerVerified'])) {
                $score += 0.05;
            }

            // E. Free / Donation or Student Value Boost (+0.04)
            if ($product['ListingType'] === 'Donate' || $product['ListingType'] === 'Exchange') {
                $score += 0.04;
                if (empty($reasons)) {
                    $reasons[] = "Available for campus peer exchange or donation";
                }
            }

            // Fallback reason if empty
            if (empty($reasons)) {
                $reasons[] = "Popular choice among " . $product['CategoryName'];
            }

            $finalScore = min(0.98, max(0.20, round($score, 2)));

            $scoredProducts[] = [
                'ProductID'           => $product['ProductID'],
                'RecommendationScore' => $finalScore,
                'Reason'              => implode(' • ', array_unique($reasons))
            ];
        }

        // Sort descending by score
        usort($scoredProducts, fn($a, $b) => $b['RecommendationScore'] <=> $a['RecommendationScore']);

        // Persist top 10 recommendations to the recommendations table
        $topRecommendations = array_slice($scoredProducts, 0, 10);
        foreach ($topRecommendations as $rec) {
            Recommendation::upsert($userId, $rec['ProductID'], $rec['RecommendationScore'], $rec['Reason']);
        }

        return $topRecommendations;
    }

    /**
     * Recompute recommendations for all active campus users
     */
    public function computeForAllUsers(): int {
        $stmt = $this->db->query("SELECT UserID FROM users WHERE Role != 'Admin'");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $count = 0;
        foreach ($users as $userId) {
            $this->computeForUser((int)$userId);
            $count++;
        }

        return $count;
    }

    /**
     * Compute weighted category affinities from activity logs and transactions
     */
    private function getCategoryAffinities(int $userId): array {
        $scores = [];

        // Weights: view = 1, search = 2, message = 3, transaction = 5
        $sql = "SELECT CategoryID, 
                       SUM(CASE 
                            WHEN ActivityType = 'view' THEN 1.0
                            WHEN ActivityType = 'search' THEN 2.0
                            WHEN ActivityType = 'message_sent' THEN 3.0
                            WHEN ActivityType = 'transaction_init' THEN 4.0
                            ELSE 1.0 
                           END) as weighted_score
                FROM user_activity_logs
                WHERE UserID = ? AND CategoryID IS NOT NULL
                GROUP BY CategoryID";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        while ($row = $stmt->fetch()) {
            $scores[(int)$row['CategoryID']] = (float)$row['weighted_score'];
        }

        // Add weight from past transactions
        $transSql = "SELECT p.CategoryID, COUNT(*) * 5.0 as trans_weight
                     FROM transactions t
                     JOIN products p ON t.ProductID = p.ProductID
                     WHERE t.BuyerID = ?
                     GROUP BY p.CategoryID";
        $transStmt = $this->db->prepare($transSql);
        $transStmt->execute([$userId]);
        while ($row = $transStmt->fetch()) {
            $catId = (int)$row['CategoryID'];
            $scores[$catId] = ($scores[$catId] ?? 0.0) + (float)$row['trans_weight'];
        }

        return $scores;
    }

    /**
     * Retrieve user's recent search queries
     */
    private function getUserSearchKeywords(int $userId): array {
        $sql = "SELECT DISTINCT SearchQuery 
                FROM user_activity_logs 
                WHERE UserID = ? AND ActivityType = 'search' AND SearchQuery IS NOT NULL
                ORDER BY LogID DESC LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Retrieve eligible candidate products for recommendations
     */
    private function getCandidateProducts(int $userId): array {
        $sql = "SELECT p.*, c.CategoryName, u.IsVerified as SellerVerified
                FROM products p
                JOIN categories c ON p.CategoryID = c.CategoryID
                JOIN users u ON p.UserID = u.UserID
                WHERE p.Status = 'Available' 
                  AND p.UserID != ?
                  AND p.ProductID NOT IN (
                      SELECT ProductID FROM transactions WHERE BuyerID = ? AND TransactionStatus = 'Completed'
                  )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }
}
