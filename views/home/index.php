<?php
/**
 * Campus Market Hub - Marketplace Discovery & Home View
 */
use App\Core\Auth;
use App\Core\Storage;

$currency = 'KSh ';
?>

<!-- Hero Banner Section -->
<section class="hero-section">
    <div class="container hero-container">
        <div class="hero-text-content">
            <div class="hero-badge">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Trusted University Circular Economy</span>
            </div>
            <h1 class="hero-title">Buy, Sell, Exchange & Donate Within Your Campus</h1>
            <p class="hero-subtitle">
                The secure digital marketplace exclusively for verified students and university staff. Trade course textbooks, laptops, hostel furniture, or donate revision kits with zero broker fees.
            </p>

            <!-- Quick Listing Type Filter Pills -->
            <div class="hero-filter-pills">
                <a href="/?type=" class="type-pill <?= empty($filters['listing_type']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-border-all"></i> All Listings
                </a>
                <a href="/?type=Sell" class="type-pill <?= ($filters['listing_type'] === 'Sell') ? 'active' : '' ?>">
                    <i class="fa-solid fa-tag"></i> Buy & Sell
                </a>
                <a href="/?type=Exchange" class="type-pill <?= ($filters['listing_type'] === 'Exchange') ? 'active' : '' ?>">
                    <i class="fa-solid fa-repeat"></i> Campus Exchanges
                </a>
                <a href="/?type=Donate" class="type-pill <?= ($filters['listing_type'] === 'Donate') ? 'active' : '' ?>">
                    <i class="fa-solid fa-hand-holding-heart"></i> Free / Donations
                </a>
                <a href="/?type=Service" class="type-pill <?= ($filters['listing_type'] === 'Service') ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-graduate"></i> Peer Services & Tutoring
                </a>
            </div>
        </div>

        <div class="hero-actions-card">
            <h3>Start Trading on Campus</h3>
            <p>Verified university ID required to post or negotiate.</p>
            <div class="hero-card-buttons">
                <?php if (Auth::check()): ?>
                    <a href="/products/create" class="btn-hero-primary">
                        <i class="fa-solid fa-plus-circle"></i>
                        <span>Post an Item or Service</span>
                    </a>
                    <a href="/products/manage" class="btn-hero-secondary">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        <span>Manage My Listings</span>
                    </a>
                <?php else: ?>
                    <a href="/register" class="btn-hero-primary">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Register with Campus ID</span>
                    </a>
                    <a href="/login" class="btn-hero-secondary">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Sign In with University Email</span>
                    </a>
                <?php endif; ?>
            </div>
            <div class="hero-security-note">
                <i class="fa-solid fa-shield-check"></i>
                <span>100% Student & Staff Verified Community</span>
            </div>
        </div>
    </div>
</section>

<!-- Category Navigation Bar -->
<section class="category-strip">
    <div class="container">
        <div class="category-scroll-wrapper">
            <a href="/" class="cat-pill <?= empty($filters['category_id']) ? 'active' : '' ?>">
                <i class="fa-solid fa-cubes"></i>
                <span>All Categories</span>
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="/?category=<?= $cat['CategoryID'] ?>&type=<?= urlencode($filters['listing_type'] ?? '') ?>" 
                   class="cat-pill <?= ($filters['category_id'] == $cat['CategoryID']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-<?= htmlspecialchars($cat['Icon'] ?? 'tag') ?>"></i>
                    <span><?= htmlspecialchars($cat['CategoryName']) ?></span>
                    <span class="cat-count"><?= $cat['product_count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="container main-marketplace-grid">
    <!-- Left Sidebar: Filters -->
    <aside class="marketplace-sidebar">
        <form action="/" method="GET" class="filter-card" id="filterForm">
            <?php if (!empty($filters['search'])): ?>
                <input type="hidden" name="q" value="<?= htmlspecialchars($filters['search']) ?>">
            <?php endif; ?>

            <div class="filter-header">
                <h3><i class="fa-solid fa-filter"></i> Refine Search</h3>
                <a href="/" class="filter-reset">Reset All</a>
            </div>

            <!-- Category Filter -->
            <div class="filter-group">
                <label>Category</label>
                <select name="category" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['CategoryID'] ?>" <?= ($filters['category_id'] == $cat['CategoryID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['CategoryName']) ?> (<?= $cat['product_count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Listing Type Filter -->
            <div class="filter-group">
                <label>Listing Type</label>
                <select name="type" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Types (Sell, Swap, Donate, Service)</option>
                    <option value="Sell" <?= ($filters['listing_type'] === 'Sell') ? 'selected' : '' ?>>For Sale (Cash/Meetup)</option>
                    <option value="Exchange" <?= ($filters['listing_type'] === 'Exchange') ? 'selected' : '' ?>>Item Exchange / Swap</option>
                    <option value="Donate" <?= ($filters['listing_type'] === 'Donate') ? 'selected' : '' ?>>Free Donation</option>
                    <option value="Service" <?= ($filters['listing_type'] === 'Service') ? 'selected' : '' ?>>Campus Services & Tutoring</option>
                </select>
            </div>

            <!-- Price Range -->
            <div class="filter-group">
                <label>Budget / Price (<?= $currency ?>)</label>
                <div class="price-input-row">
                    <input type="number" name="min_price" placeholder="Min" value="<?= htmlspecialchars($filters['min_price'] ?? '') ?>" class="filter-input-sm">
                    <span>–</span>
                    <input type="number" name="max_price" placeholder="Max" value="<?= htmlspecialchars($filters['max_price'] ?? '') ?>" class="filter-input-sm">
                </div>
            </div>

            <!-- Sort By -->
            <div class="filter-group">
                <label>Sort By</label>
                <select name="sort" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="newest" <?= ($filters['sort'] === 'newest') ? 'selected' : '' ?>>Newest Postings</option>
                    <option value="price_asc" <?= ($filters['sort'] === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= ($filters['sort'] === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="popular" <?= ($filters['sort'] === 'popular') ? 'selected' : '' ?>>Most Popular (Views)</option>
                </select>
            </div>

            <button type="submit" class="btn-filter-apply">Apply Filters</button>
        </form>

        <!-- Campus Safety Card in Sidebar -->
        <div class="safety-sidebar-card">
            <div class="safety-icon"><i class="fa-solid fa-user-shield"></i></div>
            <h4>Campus Meetup Protocol</h4>
            <ul>
                <li>Always meet in public campus zones.</li>
                <li>Inspect item condition before cash handover.</li>
                <li>Report suspicious activity to admin desk.</li>
            </ul>
        </div>
    </aside>

    <!-- Right Content: Discovery Sections & Listings -->
    <div class="marketplace-content">

        <!-- 1. RECOMMENDED FOR YOU SECTION (Content-Based & Collaborative Filtering) -->
        <?php if (!empty($recommendations) && empty($filters['search']) && empty($filters['category_id'])): ?>
            <section class="discovery-section recommendation-section">
                <div class="section-title-row">
                    <div>
                        <div class="section-badge"><i class="fa-solid fa-sparkles"></i> AI Recommendation Engine</div>
                        <h2 class="section-title">Recommended For You</h2>
                        <p class="section-sub">Computed in real-time from your search history, viewed items, and course affinities.</p>
                    </div>
                </div>

                <div class="products-grid">
                    <?php foreach ($recommendations as $item): ?>
                        <div class="product-card rec-card">
                            <div class="product-badge-strip">
                                <span class="badge-type badge-<?= strtolower($item['ListingType']) ?>"><?= $item['ListingType'] ?></span>
                                <?php if (!empty($item['Reason'])): ?>
                                    <span class="badge-rec-score" title="<?= htmlspecialchars($item['Reason']) ?>">
                                        <i class="fa-solid fa-bolt"></i> <?= htmlspecialchars(substr($item['Reason'], 0, 28)) ?>...
                                    </span>
                                <?php endif; ?>
                            </div>
                            <a href="/products/<?= $item['ProductID'] ?>" class="product-image-link">
                                <img src="<?= Storage::resolveUrl($item['ProductImage']) ?>" alt="<?= htmlspecialchars($item['ProductName']) ?>" class="product-img" loading="lazy">
                            </a>
                            <div class="product-body">
                                <div class="product-category-row">
                                    <span class="product-cat-name"><?= htmlspecialchars($item['CategoryName']) ?></span>
                                    <span class="product-condition-pill"><?= htmlspecialchars($item['ConditionStatus'] ?? 'Good') ?></span>
                                </div>
                                <h3 class="product-title">
                                    <a href="/products/<?= $item['ProductID'] ?>"><?= htmlspecialchars($item['ProductName']) ?></a>
                                </h3>
                                <div class="product-meta">
                                    <div class="product-price">
                                        <?php if ($item['ListingType'] === 'Donate'): ?>
                                            <span class="price-free">FREE / DONATION</span>
                                        <?php elseif ($item['ListingType'] === 'Exchange'): ?>
                                            <span class="price-exchange">EXCHANGE / SWAP</span>
                                        <?php else: ?>
                                            <?= $currency ?><?= number_format($item['Price'], 2) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="seller-pill">
                                        <i class="fa-solid fa-user-graduate"></i>
                                        <span><?= htmlspecialchars(explode(' ', $item['SellerName'])[0]) ?></span>
                                        <?php if (!empty($item['SellerVerified'])): ?>
                                            <i class="fa-solid fa-circle-check text-success" title="Verified Campus Member"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="product-location">
                                    <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['PickupLocation']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 2. FEATURED CAMPUS ITEMS SECTION -->
        <?php if (!empty($featuredItems) && empty($filters['search']) && empty($filters['category_id'])): ?>
            <section class="discovery-section">
                <div class="section-title-row">
                    <div>
                        <div class="section-badge badge-featured-tag"><i class="fa-solid fa-star"></i> Hand-Picked</div>
                        <h2 class="section-title">Featured Campus Listings</h2>
                    </div>
                </div>

                <div class="products-grid">
                    <?php foreach ($featuredItems as $item): ?>
                        <div class="product-card featured-border">
                            <div class="product-badge-strip">
                                <span class="badge-type badge-<?= strtolower($item['ListingType']) ?>"><?= $item['ListingType'] ?></span>
                                <span class="badge-featured"><i class="fa-solid fa-star"></i> Featured</span>
                            </div>
                            <a href="/products/<?= $item['ProductID'] ?>" class="product-image-link">
                                <img src="<?= Storage::resolveUrl($item['ProductImage']) ?>" alt="<?= htmlspecialchars($item['ProductName']) ?>" class="product-img" loading="lazy">
                            </a>
                            <div class="product-body">
                                <div class="product-category-row">
                                    <span class="product-cat-name"><?= htmlspecialchars($item['CategoryName']) ?></span>
                                    <span class="product-condition-pill"><?= htmlspecialchars($item['ConditionStatus'] ?? 'Good') ?></span>
                                </div>
                                <h3 class="product-title">
                                    <a href="/products/<?= $item['ProductID'] ?>"><?= htmlspecialchars($item['ProductName']) ?></a>
                                </h3>
                                <div class="product-meta">
                                    <div class="product-price">
                                        <?php if ($item['ListingType'] === 'Donate'): ?>
                                            <span class="price-free">FREE / DONATION</span>
                                        <?php elseif ($item['ListingType'] === 'Exchange'): ?>
                                            <span class="price-exchange">EXCHANGE / SWAP</span>
                                        <?php else: ?>
                                            <?= $currency ?><?= number_format($item['Price'], 2) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="seller-pill">
                                        <i class="fa-solid fa-user-graduate"></i>
                                        <span><?= htmlspecialchars(explode(' ', $item['SellerName'])[0]) ?></span>
                                        <?php if (!empty($item['SellerVerified'])): ?>
                                            <i class="fa-solid fa-circle-check text-success" title="Verified Campus Member"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="product-location">
                                    <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['PickupLocation']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 3. ALL / SEARCHED MARKETPLACE LISTINGS -->
        <section class="discovery-section">
            <div class="section-title-row">
                <div>
                    <h2 class="section-title">
                        <?php if (!empty($filters['search'])): ?>
                            Search Results for "<?= htmlspecialchars($filters['search']) ?>"
                        <?php elseif (!empty($filters['category_id'])): ?>
                            Category Listings
                        <?php else: ?>
                            All Campus Listings
                        <?php endif; ?>
                    </h2>
                    <p class="section-sub">Showing <?= count($listings) ?> of <?= $totalItems ?> verified items</p>
                </div>
            </div>

            <?php if (empty($listings)): ?>
                <div class="empty-state-card">
                    <div class="empty-state-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <h3>No items found matching your criteria</h3>
                    <p>Try clearing your price filters, selecting another category, or searching for broader terms.</p>
                    <a href="/" class="btn-primary-sm">Browse All Marketplace Items</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($listings as $item): ?>
                        <div class="product-card">
                            <div class="product-badge-strip">
                                <span class="badge-type badge-<?= strtolower($item['ListingType']) ?>"><?= $item['ListingType'] ?></span>
                                <?php if ($item['ConditionStatus']): ?>
                                    <span class="badge-condition"><?= htmlspecialchars($item['ConditionStatus']) ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="/products/<?= $item['ProductID'] ?>" class="product-image-link">
                                <img src="<?= Storage::resolveUrl($item['ProductImage']) ?>" alt="<?= htmlspecialchars($item['ProductName']) ?>" class="product-img" loading="lazy">
                            </a>
                            <div class="product-body">
                                <div class="product-category-row">
                                    <span class="product-cat-name"><?= htmlspecialchars($item['CategoryName']) ?></span>
                                    <span class="product-views"><i class="fa-regular fa-eye"></i> <?= $item['ViewsCount'] ?></span>
                                </div>
                                <h3 class="product-title">
                                    <a href="/products/<?= $item['ProductID'] ?>"><?= htmlspecialchars($item['ProductName']) ?></a>
                                </h3>
                                <div class="product-meta">
                                    <div class="product-price">
                                        <?php if ($item['ListingType'] === 'Donate'): ?>
                                            <span class="price-free">FREE DONATION</span>
                                        <?php elseif ($item['ListingType'] === 'Exchange'): ?>
                                            <span class="price-exchange">FOR SWAP</span>
                                        <?php else: ?>
                                            <?= $currency ?><?= number_format($item['Price'], 2) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="seller-pill">
                                        <i class="fa-solid fa-user-graduate"></i>
                                        <span><?= htmlspecialchars(explode(' ', $item['SellerName'])[0]) ?></span>
                                        <?php if (!empty($item['SellerVerified'])): ?>
                                            <i class="fa-solid fa-circle-check text-success" title="Verified Campus Member"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="product-location">
                                    <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['PickupLocation']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <?php if ($currentPage > 1): ?>
                            <a href="/?page=<?= $currentPage - 1 ?>&q=<?= urlencode($filters['search']) ?>&category=<?= $filters['category_id'] ?>&type=<?= $filters['listing_type'] ?>&sort=<?= $filters['sort'] ?>" class="page-link">&laquo; Prev</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="/?page=<?= $i ?>&q=<?= urlencode($filters['search']) ?>&category=<?= $filters['category_id'] ?>&type=<?= $filters['listing_type'] ?>&sort=<?= $filters['sort'] ?>" 
                               class="page-link <?= ($i === $currentPage) ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="/?page=<?= $currentPage + 1 ?>&q=<?= urlencode($filters['search']) ?>&category=<?= $filters['category_id'] ?>&type=<?= $filters['listing_type'] ?>&sort=<?= $filters['sort'] ?>" class="page-link">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </section>

    </div>
</div>
