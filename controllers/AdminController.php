<?php
/**
 * Campus Market Hub - Administrator & Fraud Control Panel Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\ComplaintReview;

class AdminController extends Controller {
    public function __construct() {
        parent::__construct();
        Auth::requireAdmin();
    }

    /**
     * Admin Dashboard Overview (/admin)
     */
    public function index(): void {
        $activeTab = $this->input('tab', 'overview');

        // Aggregate Platform Metrics
        $totalUsers        = User::count();
        $verifiedUsers     = User::count(['IsVerified' => 1]);
        $activeListings    = Product::count(['Status' => 'Available']);
        $soldListings      = Product::count(['Status' => 'Sold']);
        $pendingReports    = ComplaintReview::count(['Status' => 'Pending']);
        $transMetrics      = Transaction::getPlatformMetrics();

        // Data for tabs
        $users = User::getAllWithFilters($this->input('user_search', ''), $this->input('user_role', ''));
        $listings = Product::getMarketplaceListings(['status' => ''], 50, 0);
        $fraudReports = ComplaintReview::getAdminReports();

        $this->view('admin/index', [
            'pageTitle'       => 'Administrator & Safety Desk - Campus Market Hub',
            'activeTab'       => $activeTab,
            'metrics'         => [
                'total_users'      => $totalUsers,
                'verified_users'   => $verifiedUsers,
                'active_listings'  => $activeListings,
                'sold_listings'    => $soldListings,
                'pending_reports'  => $pendingReports,
                'completed_trades' => $transMetrics['completed'] ?? 0,
                'total_volume'     => $transMetrics['total_volume'] ?? 0.0
            ],
            'users'           => $users,
            'listings'        => $listings,
            'fraudReports'    => $fraudReports
        ]);
    }

    /**
     * Toggle User Verification Status
     */
    public function toggleVerification(array $params = []): void {
        $this->validateCsrf();
        $userId = (int)($params['id'] ?? $this->input('user_id'));

        if ($userId) {
            User::toggleVerification($userId);
            $this->setFlash('success', "User #{$userId} verification status has been toggled.");
        }

        $this->redirect('/admin?tab=users');
    }

    /**
     * Moderate Listing (Delete / Remove scam or inappropriate item)
     */
    public function deleteListing(array $params = []): void {
        $this->validateCsrf();
        $productId = (int)($params['id'] ?? $this->input('product_id'));

        if ($productId) {
            Product::delete($productId);
            $this->setFlash('success', "Listing #{$productId} removed from the marketplace.");
        }

        $this->redirect('/admin?tab=listings');
    }

    /**
     * Toggle Featured Item
     */
    public function toggleFeatured(array $params = []): void {
        $this->validateCsrf();
        $productId = (int)($params['id'] ?? $this->input('product_id'));

        $product = Product::find($productId);
        if ($product) {
            $newVal = $product['IsFeatured'] ? 0 : 1;
            Product::update($productId, ['IsFeatured' => $newVal]);
            $this->setFlash('success', "Listing #{$productId} featured status updated.");
        }

        $this->redirect('/admin?tab=listings');
    }

    /**
     * Resolve or Dismiss a Fraud Complaint
     */
    public function resolveComplaint(array $params = []): void {
        $this->validateCsrf();
        $reviewId = (int)($params['id'] ?? $this->input('review_id'));
        $action   = $this->input('action', 'Resolved'); // 'Resolved', 'Dismissed', 'TakeDown'
        $notes    = $this->input('admin_notes', '');

        $report = ComplaintReview::find($reviewId);
        if (!$report) {
            $this->setFlash('error', 'Report not found.');
            $this->redirect('/admin?tab=reports');
        }

        if ($action === 'TakeDown' && !empty($report['ProductID'])) {
            Product::delete($report['ProductID']);
            ComplaintReview::resolve($reviewId, 'Resolved', "Item removed by admin: " . $notes);
            $this->setFlash('success', "Report #{$reviewId} resolved and the flagged item has been taken down.");
        } else {
            ComplaintReview::resolve($reviewId, $action, $notes);
            $this->setFlash('success', "Report #{$reviewId} marked as '{$action}'.");
        }

        $this->redirect('/admin?tab=reports');
    }
}
