<?php
/**
 * Campus Market Hub - Home & Discovery Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Product;
use App\Models\Category;
use App\Models\Recommendation;
use App\Models\UserActivityLog;

class HomeController extends Controller {
    public function index(): void {
        $search      = $this->input('q', '');
        $categoryId  = $this->input('category');
        $listingType = $this->input('type');
        $minPrice    = $this->input('min_price');
        $maxPrice    = $this->input('max_price');
        $sort        = $this->input('sort', 'newest');
        $page        = max(1, (int)$this->input('page', 1));
        $perPage     = 12;
        $offset      = ($page - 1) * $perPage;

        // Filters array
        $filters = [
            'status'       => 'Available',
            'search'       => $search,
            'category_id'  => $categoryId,
            'listing_type' => $listingType,
            'min_price'    => $minPrice,
            'max_price'    => $maxPrice,
            'sort'         => $sort
        ];

        // Track search activity in logs if query present
        if (!empty($search)) {
            UserActivityLog::record(
                Auth::id(),
                'search',
                null,
                $categoryId ? (int)$categoryId : null,
                $search
            );
        }

        // Fetch paginated main listings
        $listings   = Product::getMarketplaceListings($filters, $perPage, $offset);
        $totalItems = Product::countMarketplaceListings($filters);
        $totalPages = ceil($totalItems / $perPage);

        // Fetch featured and trending items for discovery sections
        $featuredItems = Product::getFeatured(4);
        $trendingItems = Product::getTrending(4);

        // Fetch categories with active product counts
        $categories = Category::allWithCounts();

        // Personalized recommendations for logged in user (or fallback for guests)
        $recommendations = [];
        if (Auth::check()) {
            $recommendations = Recommendation::getForUser(Auth::id(), 4);
        } else {
            // For guests, show top viewed items as campus picks
            $recommendations = $trendingItems;
        }

        $this->view('home/index', [
            'pageTitle'        => 'Campus Market Hub - Trusted Student & Staff Marketplace',
            'listings'         => $listings,
            'featuredItems'    => $featuredItems,
            'trendingItems'    => $trendingItems,
            'recommendations'  => $recommendations,
            'categories'       => $categories,
            'filters'          => $filters,
            'currentPage'      => $page,
            'totalPages'       => $totalPages,
            'totalItems'       => $totalItems
        ]);
    }
}
