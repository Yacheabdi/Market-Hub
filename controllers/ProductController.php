<?php
/**
 * Campus Market Hub - Product Listing & Management Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Storage;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\ComplaintReview;
use App\Models\UserActivityLog;

class ProductController extends Controller {
    /**
     * Show Product Details (/products/:id)
     */
    public function view(array $params = []): void {
        $id = (int)($params['id'] ?? $this->input('id'));
        if (!$id) {
            $this->redirect('/');
        }

        $product = Product::getByIdWithDetails($id);
        if (!$product) {
            $this->setFlash('error', 'The requested product listing was not found.');
            $this->redirect('/');
        }

        // Increment views
        Product::incrementViews($id);

        // Record viewing activity for Recommendation Engine
        UserActivityLog::record(
            Auth::id(),
            'view',
            $id,
            (int)$product['CategoryID']
        );

        // Fetch seller trust metrics & reviews
        $sellerStats = User::getStats((int)$product['UserID']);
        $sellerReviews = ComplaintReview::getReviewsForUser((int)$product['UserID']);

        // Fetch related products in the same category
        $relatedProducts = Product::getRelated($id, (int)$product['CategoryID'], 4);

        $this->view('products/view', [
            'pageTitle'       => htmlspecialchars($product['ProductName']) . ' - Campus Market Hub',
            'product'         => $product,
            'sellerStats'     => $sellerStats,
            'sellerReviews'   => $sellerReviews,
            'relatedProducts' => $relatedProducts
        ]);
    }

    /**
     * Show Create Listing Form
     */
    public function showCreate(): void {
        Auth::requireLogin();

        $categories = Category::all('DisplayOrder ASC');
        $this->view('products/create', [
            'pageTitle'  => 'Post a New Campus Listing - Campus Market Hub',
            'categories' => $categories
        ]);
    }

    /**
     * Store New Listing
     */
    public function store(): void {
        Auth::requireLogin();
        $this->validateCsrf();

        $name          = $this->input('product_name');
        $categoryId    = (int)$this->input('category_id');
        $listingType   = $this->input('listing_type', 'Sell');
        $price         = (float)$this->input('price', 0.0);
        $description   = $this->input('description');
        $condition     = $this->input('condition_status', 'Good');
        $pickupLoc     = $this->input('pickup_location', 'Main Campus Student Center');
        $exchangePref  = $this->input('exchange_preferences');

        // Validation
        $errors = [];
        if (empty($name) || strlen($name) < 4) {
            $errors[] = "Product name must be at least 4 characters long.";
        }
        if (!$categoryId) {
            $errors[] = "Please select a valid marketplace category.";
        }
        if (empty($description) || strlen($description) < 10) {
            $errors[] = "Please provide a detailed description (at least 10 characters).";
        }

        // If Donate or Exchange, force price to 0.00
        if (in_array($listingType, ['Donate', 'Exchange'])) {
            $price = 0.00;
        }

        // Handle Image Upload
        $imageUrl = 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&auto=format&fit=crop&q=80'; // Default
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploaded = Storage::uploadProductImage($_FILES['product_image']);
                if ($uploaded) {
                    $imageUrl = $uploaded;
                }
            } catch (\Exception $e) {
                $errors[] = "Image upload error: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $this->setFlash('error', implode('<br>', $errors));
            $this->redirect('/products/create');
        }

        $productId = Product::create([
            'UserID'              => Auth::id(),
            'CategoryID'          => $categoryId,
            'ProductName'         => $name,
            'Description'         => $description,
            'Price'               => $price,
            'ListingType'         => $listingType,
            'ProductImage'        => $imageUrl,
            'ConditionStatus'     => $condition,
            'PickupLocation'      => $pickupLoc,
            'Status'              => 'Available',
            'IsFeatured'          => 0,
            'ExchangePreferences' => $exchangePref ?: null,
            'DatePosted'          => date('Y-m-d H:i:s')
        ]);

        $this->setFlash('success', 'Your campus listing has been posted successfully!');
        $this->redirect('/products/' . $productId);
    }

    /**
     * Show Edit Listing Form
     */
    public function showEdit(array $params = []): void {
        Auth::requireLogin();
        $id = (int)($params['id'] ?? $this->input('id'));
        $product = Product::find($id);

        if (!$product) {
            $this->setFlash('error', 'Listing not found.');
            $this->redirect('/products/manage');
        }

        // Check permission (owner or admin)
        if ($product['UserID'] != Auth::id() && !Auth::isAdmin()) {
            $this->setFlash('error', 'Unauthorized. You can only edit your own listings.');
            $this->redirect('/products/manage');
        }

        $categories = Category::all('DisplayOrder ASC');
        $this->view('products/edit', [
            'pageTitle'  => 'Edit Listing - ' . htmlspecialchars($product['ProductName']),
            'product'    => $product,
            'categories' => $categories
        ]);
    }

    /**
     * Update Listing
     */
    public function update(array $params = []): void {
        Auth::requireLogin();
        $this->validateCsrf();

        $id = (int)($params['id'] ?? $this->input('id'));
        $product = Product::find($id);

        if (!$product || ($product['UserID'] != Auth::id() && !Auth::isAdmin())) {
            $this->setFlash('error', 'Unauthorized action.');
            $this->redirect('/products/manage');
        }

        $name         = $this->input('product_name');
        $categoryId   = (int)$this->input('category_id');
        $listingType  = $this->input('listing_type', 'Sell');
        $price        = (float)$this->input('price', 0.0);
        $description  = $this->input('description');
        $condition    = $this->input('condition_status', 'Good');
        $pickupLoc    = $this->input('pickup_location');
        $status       = $this->input('status', 'Available');
        $exchangePref = $this->input('exchange_preferences');

        if (in_array($listingType, ['Donate', 'Exchange'])) {
            $price = 0.00;
        }

        $updateData = [
            'CategoryID'          => $categoryId,
            'ProductName'         => $name,
            'Description'         => $description,
            'Price'               => $price,
            'ListingType'         => $listingType,
            'ConditionStatus'     => $condition,
            'PickupLocation'      => $pickupLoc,
            'Status'              => $status,
            'ExchangePreferences' => $exchangePref ?: null,
        ];

        // Process optional new image
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploaded = Storage::uploadProductImage($_FILES['product_image']);
                if ($uploaded) {
                    $updateData['ProductImage'] = $uploaded;
                }
            } catch (\Exception $e) {
                $this->setFlash('error', 'Image error: ' . $e->getMessage());
            }
        }

        Product::update($id, $updateData);
        $this->setFlash('success', 'Listing updated successfully!');
        $this->redirect('/products/' . $id);
    }

    /**
     * Delete Listing
     */
    public function delete(array $params = []): void {
        Auth::requireLogin();
        $this->validateCsrf();

        $id = (int)($params['id'] ?? $this->input('id'));
        $product = Product::find($id);

        if ($product && ($product['UserID'] == Auth::id() || Auth::isAdmin())) {
            Product::delete($id);
            $this->setFlash('success', 'Listing removed from the campus marketplace.');
        } else {
            $this->setFlash('error', 'Cannot delete this listing.');
        }

        $this->redirect('/products/manage');
    }

    /**
     * Quick status toggle (Available <-> Sold <-> Reserved)
     */
    public function toggleStatus(array $params = []): void {
        Auth::requireLogin();
        $id = (int)($params['id'] ?? $this->input('id'));
        $newStatus = $this->input('status', 'Sold');

        $product = Product::find($id);
        if ($product && ($product['UserID'] == Auth::id() || Auth::isAdmin())) {
            if (in_array($newStatus, ['Available', 'Sold', 'Reserved'])) {
                Product::update($id, ['Status' => $newStatus]);
                $this->setFlash('success', "Listing status marked as '{$newStatus}'.");
            }
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/products/manage');
    }

    /**
     * Manage My Listings View (/products/manage)
     */
    public function manage(): void {
        Auth::requireLogin();
        $userId = Auth::id();

        $allListings = Product::getUserListings($userId);
        $activeCount = 0;
        $soldCount   = 0;

        foreach ($allListings as $item) {
            if ($item['Status'] === 'Available') $activeCount++;
            if ($item['Status'] === 'Sold') $soldCount++;
        }

        $this->view('products/manage', [
            'pageTitle'    => 'Manage My Campus Listings - Campus Market Hub',
            'listings'     => $allListings,
            'activeCount'  => $activeCount,
            'soldCount'    => $soldCount
        ]);
    }

    /**
     * Report scam/fraud/counterfeit listing
     */
    public function report(array $params = []): void {
        Auth::requireLogin();
        $this->validateCsrf();

        $productId = (int)($params['id'] ?? $this->input('product_id'));
        $reason    = $this->input('reason');
        $details   = $this->input('details');

        $product = Product::find($productId);
        if (!$product) {
            $this->setFlash('error', 'Product not found.');
            $this->redirect('/');
        }

        ComplaintReview::submit([
            'ReporterID'   => Auth::id(),
            'TargetUserID' => $product['UserID'],
            'ProductID'    => $productId,
            'Type'         => 'FraudReport',
            'Title'        => $reason,
            'Comment'      => $details,
            'Status'       => 'Pending'
        ]);

        $this->setFlash('success', 'Thank you for keeping campus safe. Your report has been submitted to the admin fraud desk.');
        $this->redirect('/products/' . $productId);
    }
}
