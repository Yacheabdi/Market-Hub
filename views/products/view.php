<?php
/**
 * Campus Market Hub - Product Detail View
 */
use App\Core\Auth;
use App\Core\Storage;

$currency = 'KSh ';
$isOwner = Auth::check() && ($currentUser['UserID'] == $product['UserID']);
?>

<div class="container product-detail-page">
    <!-- Breadcrumb -->
    <nav class="breadcrumb-nav">
        <a href="/"><i class="fa-solid fa-house"></i> Home</a>
        <i class="fa-solid fa-chevron-right separator"></i>
        <a href="/?category=<?= $product['CategoryID'] ?>"><?= htmlspecialchars($product['CategoryName']) ?></a>
        <i class="fa-solid fa-chevron-right separator"></i>
        <span><?= htmlspecialchars($product['ProductName']) ?></span>
    </nav>

    <div class="product-detail-layout">
        <!-- Left: Image & Description Showcase -->
        <div class="product-gallery-col">
            <div class="main-image-wrapper">
                <img src="<?= Storage::resolveUrl($product['ProductImage']) ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>" class="detail-main-img" id="detailImage">
                <div class="image-floating-badges">
                    <span class="badge-type badge-<?= strtolower($product['ListingType']) ?>"><?= $product['ListingType'] ?></span>
                    <span class="badge-status status-<?= strtolower($product['Status']) ?>"><?= $product['Status'] ?></span>
                </div>
            </div>

            <!-- Description Card -->
            <div class="content-card description-card">
                <h3><i class="fa-solid fa-circle-info"></i> Detailed Item Information</h3>
                <div class="description-body">
                    <?= nl2br(htmlspecialchars($product['Description'])) ?>
                </div>

                <?php if ($product['ListingType'] === 'Exchange' && !empty($product['ExchangePreferences'])): ?>
                    <div class="exchange-pref-box">
                        <div class="pref-title"><i class="fa-solid fa-repeat"></i> Seller's Exchange Preferences:</div>
                        <p><?= htmlspecialchars($product['ExchangePreferences']) ?></p>
                    </div>
                <?php endif; ?>

                <div class="specs-grid">
                    <div class="spec-item">
                        <span class="spec-label">Condition:</span>
                        <span class="spec-value"><?= htmlspecialchars($product['ConditionStatus']) ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Preferred Campus Pickup:</span>
                        <span class="spec-value"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($product['PickupLocation']) ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Listed On:</span>
                        <span class="spec-value"><?= date('M j, Y', strtotime($product['DatePosted'])) ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Platform Views:</span>
                        <span class="spec-value"><?= $product['ViewsCount'] ?> campus views</span>
                    </div>
                </div>
            </div>

            <!-- Campus Safety Advisory Card -->
            <div class="safety-advisory-card">
                <i class="fa-solid fa-shield-halved safety-advisory-icon"></i>
                <div>
                    <h4>Campus Safety First</h4>
                    <p>Always inspect the item in person in a busy campus location (e.g. University Library, Student Center, or Cafeteria). Never send cash deposits before physical handover.</p>
                </div>
            </div>
        </div>

        <!-- Right: Action & Seller Profile Column -->
        <div class="product-action-col">
            <!-- Price Card -->
            <div class="content-card price-action-card">
                <span class="listing-cat-label"><?= htmlspecialchars($product['CategoryName']) ?></span>
                <h1 class="detail-product-title"><?= htmlspecialchars($product['ProductName']) ?></h1>

                <div class="detail-price-box">
                    <?php if ($product['ListingType'] === 'Donate'): ?>
                        <div class="price-val free-val"><i class="fa-solid fa-heart"></i> 100% FREE DONATION</div>
                        <p class="price-sub">Donated to support students in need</p>
                    <?php elseif ($product['ListingType'] === 'Exchange'): ?>
                        <div class="price-val swap-val"><i class="fa-solid fa-repeat"></i> ITEM EXCHANGE</div>
                        <p class="price-sub">Propose an exchange with your items</p>
                    <?php else: ?>
                        <div class="price-val"><?= $currency ?><?= number_format($product['Price'], 2) ?></div>
                        <p class="price-sub">Fixed campus student-to-student price</p>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="detail-btn-stack">
                    <?php if ($isOwner): ?>
                        <a href="/products/edit/<?= $product['ProductID'] ?>" class="btn-primary-block">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Listing
                        </a>
                        <form action="/products/toggle-status/<?= $product['ProductID'] ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="status" value="<?= ($product['Status'] === 'Available') ? 'Sold' : 'Available' ?>">
                            <button type="submit" class="btn-outline-block">
                                <i class="fa-solid fa-check-double"></i> Mark as <?= ($product['Status'] === 'Available') ? 'Sold' : 'Available' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <?php if ($product['Status'] === 'Available'): ?>
                            <!-- Chat / Message Seller Button -->
                            <a href="/messages?to=<?= $product['UserID'] ?>&product_id=<?= $product['ProductID'] ?>" class="btn-chat-seller">
                                <i class="fa-solid fa-comment-dots"></i> Message Seller / Negotiate
                            </a>

                            <!-- Initiate Transaction Modal Trigger -->
                            <button type="button" class="btn-initiate-trans" onclick="openModal('transactionModal')">
                                <i class="fa-solid fa-handshake"></i> 
                                <?php if ($product['ListingType'] === 'Donate'): ?>
                                    Claim This Free Donation
                                <?php elseif ($product['ListingType'] === 'Exchange'): ?>
                                    Propose Campus Exchange
                                <?php elseif ($product['ListingType'] === 'Service'): ?>
                                    Book This Campus Service
                                <?php else: ?>
                                    Initiate Campus Purchase
                                <?php endif; ?>
                            </button>
                        <?php else: ?>
                            <div class="sold-notice">
                                <i class="fa-solid fa-lock"></i> This item has been marked as <strong><?= $product['Status'] ?></strong>.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <button type="button" class="btn-report-link" onclick="openModal('reportModal')">
                        <i class="fa-solid fa-flag"></i> Report Suspicious Listing
                    </button>
                </div>
            </div>

            <!-- Seller Trust Card -->
            <div class="content-card seller-trust-card">
                <div class="seller-card-header">
                    <div class="seller-avatar-large">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div class="seller-header-info">
                        <h3><?= htmlspecialchars($product['SellerName']) ?></h3>
                        <div class="seller-role-strip">
                            <span class="user-role-badge role-<?= strtolower($product['SellerRole']) ?>"><?= $product['SellerRole'] ?></span>
                            <?php if (!empty($product['SellerVerified'])): ?>
                                <span class="verified-badge-pill"><i class="fa-solid fa-circle-check"></i> Verified Campus Member</span>
                            <?php else: ?>
                                <span class="pending-badge-pill"><i class="fa-solid fa-clock"></i> Pending Verification</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="seller-details-list">
                    <div class="detail-row">
                        <span class="row-label"><i class="fa-solid fa-id-badge"></i> Campus ID:</span>
                        <span class="row-val"><?= htmlspecialchars($product['SellerRegNo']) ?></span>
                    </div>
                    <?php if (!empty($product['SellerDepartment'])): ?>
                        <div class="detail-row">
                            <span class="row-label"><i class="fa-solid fa-building-columns"></i> Faculty:</span>
                            <span class="row-val"><?= htmlspecialchars($product['SellerDepartment']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="row-label"><i class="fa-solid fa-phone"></i> Mobile/WhatsApp:</span>
                        <span class="row-val"><?= htmlspecialchars($product['SellerPhone'] ?? 'Contact via chat') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="row-label"><i class="fa-solid fa-star text-amber"></i> Trust Score:</span>
                        <span class="row-val"><strong><?= $sellerStats['avg_rating'] ?> / 5.0</strong> (<?= $sellerStats['review_count'] ?> peer reviews)</span>
                    </div>
                    <div class="detail-row">
                        <span class="row-label"><i class="fa-solid fa-handshake-simple"></i> Completed Trades:</span>
                        <span class="row-val"><?= $sellerStats['completed_trades'] ?> successful trades</span>
                    </div>
                </div>
            </div>

            <!-- Seller Peer Reviews -->
            <?php if (!empty($sellerReviews)): ?>
                <div class="content-card reviews-card">
                    <h4><i class="fa-solid fa-comments"></i> Recent Peer Reviews</h4>
                    <div class="reviews-list">
                        <?php foreach (array_slice($sellerReviews, 0, 3) as $rev): ?>
                            <div class="review-item">
                                <div class="review-rating">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i class="fa-solid fa-star <?= ($s <= ($rev['Rating'] ?? 5)) ? 'star-filled' : 'star-empty' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="reviewer-name"><?= htmlspecialchars($rev['ReporterName']) ?></span>
                                </div>
                                <p class="review-comment">"<?= htmlspecialchars($rev['Comment']) ?>"</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Related Category Items -->
    <?php if (!empty($relatedProducts)): ?>
        <section class="related-section">
            <h2 class="section-title">More in <?= htmlspecialchars($product['CategoryName']) ?></h2>
            <div class="products-grid">
                <?php foreach ($relatedProducts as $rel): ?>
                    <div class="product-card">
                        <a href="/products/<?= $rel['ProductID'] ?>" class="product-image-link">
                            <img src="<?= Storage::resolveUrl($rel['ProductImage']) ?>" alt="<?= htmlspecialchars($rel['ProductName']) ?>" class="product-img">
                        </a>
                        <div class="product-body">
                            <h3 class="product-title"><a href="/products/<?= $rel['ProductID'] ?>"><?= htmlspecialchars($rel['ProductName']) ?></a></h3>
                            <div class="product-meta">
                                <span class="product-price"><?= $currency ?><?= number_format($rel['Price'], 2) ?></span>
                                <span class="seller-pill"><?= htmlspecialchars(explode(' ', $rel['SellerName'])[0]) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- INITIATE TRANSACTION / CLAIM MODAL         -->
<!-- ========================================== -->
<div class="modal-overlay" id="transactionModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fa-solid fa-handshake"></i> Initiate Campus Transaction</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('transactionModal')">&times;</button>
        </div>
        <form action="/transactions/initiate" method="POST" class="modal-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="product_id" value="<?= $product['ProductID'] ?>">

            <div class="modal-item-summary">
                <img src="<?= Storage::resolveUrl($product['ProductImage']) ?>" alt="thumbnail" class="modal-thumb">
                <div>
                    <strong><?= htmlspecialchars($product['ProductName']) ?></strong>
                    <div class="text-muted">Listing Type: <?= $product['ListingType'] ?> | Seller: <?= htmlspecialchars($product['SellerName']) ?></div>
                </div>
            </div>

            <div class="form-group">
                <label>Transaction Type</label>
                <select name="transaction_type" class="form-control" required>
                    <?php if ($product['ListingType'] === 'Donate'): ?>
                        <option value="Donation" selected>Claim Free Campus Donation</option>
                    <?php elseif ($product['ListingType'] === 'Exchange'): ?>
                        <option value="Exchange" selected>Propose Item Exchange / Swap</option>
                    <?php elseif ($product['ListingType'] === 'Service'): ?>
                        <option value="Service" selected>Campus Service Booking</option>
                    <?php else: ?>
                        <option value="Purchase" selected>Campus Purchase (Meetup & Pay)</option>
                        <option value="Exchange">Offer an Exchange Instead</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Agreed / Proposed Amount (<?= $currency ?>)</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="<?= $product['Price'] ?>" required>
                <small class="form-text">0.00 for donations and direct exchanges.</small>
            </div>

            <div class="form-group">
                <label>Proposed Campus Meetup Location</label>
                <input type="text" name="meeting_location" class="form-control" value="<?= htmlspecialchars($product['PickupLocation']) ?>" placeholder="e.g. Main Library Discussion Room, Science Quad" required>
            </div>

            <div class="form-group">
                <label>Notes / Exchange Proposal Details</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Tell the seller what time suits you or describe items you're trading..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('transactionModal')">Cancel</button>
                <button type="submit" class="btn-primary-block">Confirm & Alert Seller</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- FRAUD / SUSPICIOUS LISTING REPORT MODAL    -->
<!-- ========================================== -->
<div class="modal-overlay" id="reportModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fa-solid fa-flag text-danger"></i> Report Listing to Campus Admin Desk</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('reportModal')">&times;</button>
        </div>
        <form action="/products/report/<?= $product['ProductID'] ?>" method="POST" class="modal-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="product_id" value="<?= $product['ProductID'] ?>">

            <div class="form-group">
                <label>Reason for Flagging</label>
                <select name="reason" class="form-control" required>
                    <option value="Suspected Fraud / Upfront Deposit Request">Suspected Fraud / Upfront Deposit Request</option>
                    <option value="Prohibited or Counterfeit Item">Prohibited or Counterfeit Item</option>
                    <option value="Inaccurate Condition or Misleading Price">Inaccurate Condition or Misleading Price</option>
                    <option value="Spam / Duplicate Listing">Spam / Duplicate Listing</option>
                    <option value="Impersonation / Fake Student ID">Impersonation / Fake Student ID</option>
                </select>
            </div>

            <div class="form-group">
                <label>Details & Evidence</label>
                <textarea name="details" class="form-control" rows="4" placeholder="Please describe what happened..." required></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('reportModal')">Cancel</button>
                <button type="submit" class="btn-danger-submit">Submit Report</button>
            </div>
        </form>
    </div>
</div>
