<?php
/**
 * Campus Market Hub - Transaction Tracking View
 */
use App\Core\Auth;
use App\Core\Storage;

$currency = 'KSh ';
$currentUid = Auth::id();
?>

<div class="container transactions-page">
    <div class="page-header-flex">
        <div>
            <h2><i class="fa-solid fa-receipt text-primary"></i> Campus Trades & Transactions Tracker</h2>
            <p>Track your student purchases, equipment swaps, donation claims, and service agreements from initiation to completion.</p>
        </div>
        <a href="/" class="btn-outline-sm"><i class="fa-solid fa-compass"></i> Explore Marketplace</a>
    </div>

    <!-- Status Filter Tabs -->
    <div class="transaction-filter-tabs">
        <a href="/transactions" class="tab-btn <?= ($activeFilter === 'all') ? 'active' : '' ?>">
            <i class="fa-solid fa-list-check"></i> All Transactions
        </a>
        <a href="/transactions?status=Pending" class="tab-btn <?= ($activeFilter === 'Pending') ? 'active' : '' ?>">
            <i class="fa-solid fa-clock text-amber"></i> Pending Handover
        </a>
        <a href="/transactions?status=Completed" class="tab-btn <?= ($activeFilter === 'Completed') ? 'active' : '' ?>">
            <i class="fa-solid fa-circle-check text-success"></i> Completed Trades
        </a>
        <a href="/transactions?status=Cancelled" class="tab-btn <?= ($activeFilter === 'Cancelled') ? 'active' : '' ?>">
            <i class="fa-solid fa-circle-xmark text-danger"></i> Cancelled
        </a>
    </div>

    <?php if (empty($transactions)): ?>
        <div class="empty-state-card">
            <div class="empty-state-icon"><i class="fa-solid fa-handshake-slash"></i></div>
            <h3>No transactions recorded under this status</h3>
            <p>Once you initiate a purchase, accept an exchange offer, or claim a donation, your audit trail appears here.</p>
            <a href="/" class="btn-primary-sm">Browse Active Campus Listings</a>
        </div>
    <?php else: ?>
        <div class="transactions-list">
            <?php foreach ($transactions as $t): ?>
                <?php 
                    $isBuyer = ($t['BuyerID'] == $currentUid);
                    $counterpartName = $isBuyer ? $t['SellerName'] : $t['BuyerName'];
                    $counterpartRole = $isBuyer ? 'Seller' : 'Buyer';
                    $counterpartPhone = $isBuyer ? $t['SellerPhone'] : $t['BuyerPhone'];
                    $status = $t['TransactionStatus'];
                ?>
                <div class="content-card transaction-card status-border-<?= strtolower($status) ?>">
                    <div class="trans-card-header">
                        <div class="trans-id-col">
                            <span class="trans-id-tag">REF #TX-<?= $t['TransactionID'] ?></span>
                            <span class="trans-date"><i class="fa-regular fa-calendar"></i> <?= date('M j, Y - g:i A', strtotime($t['TransactionDate'])) ?></span>
                        </div>
                        <div class="trans-status-badge badge-<?= strtolower($status) ?>">
                            <i class="fa-solid <?= ($status === 'Completed') ? 'fa-circle-check' : (($status === 'Pending') ? 'fa-clock' : 'fa-circle-xmark') ?>"></i>
                            <?= $status ?>
                        </div>
                    </div>

                    <!-- Visual Timeline Tracker -->
                    <div class="trans-timeline-bar">
                        <div class="step completed">
                            <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                            <span>Initiated</span>
                        </div>
                        <div class="step-line <?= ($status === 'Completed' || $status === 'Pending') ? 'active' : '' ?>"></div>
                        <div class="step <?= ($status === 'Completed' || $status === 'Pending') ? 'completed' : '' ?>">
                            <div class="step-circle"><i class="fa-solid <?= ($status === 'Completed') ? 'fa-check' : 'fa-handshake' ?>"></i></div>
                            <span>Meetup & Inspection</span>
                        </div>
                        <div class="step-line <?= ($status === 'Completed') ? 'active' : '' ?>"></div>
                        <div class="step <?= ($status === 'Completed') ? 'completed' : (($status === 'Cancelled') ? 'cancelled' : '') ?>">
                            <div class="step-circle"><i class="fa-solid <?= ($status === 'Completed') ? 'fa-circle-check' : (($status === 'Cancelled') ? 'fa-xmark' : 'fa-lock') ?>"></i></div>
                            <span><?= ($status === 'Cancelled') ? 'Cancelled' : 'Handover Completed' ?></span>
                        </div>
                    </div>

                    <div class="trans-card-body">
                        <!-- Product Info -->
                        <div class="trans-product-info">
                            <img src="<?= Storage::resolveUrl($t['ProductImage']) ?>" alt="thumb" class="trans-prod-thumb">
                            <div>
                                <h4 class="trans-prod-title">
                                    <a href="/products/<?= $t['ProductID'] ?>"><?= htmlspecialchars($t['ProductName']) ?></a>
                                </h4>
                                <div class="trans-tags">
                                    <span class="badge-type badge-<?= strtolower($t['ListingType']) ?>"><?= $t['ListingType'] ?></span>
                                    <span class="trans-role-pill <?= $isBuyer ? 'role-buyer' : 'role-seller' ?>">
                                        You are the <?= $isBuyer ? 'Buyer / Receiver' : 'Seller / Giver' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Trade Details & Meetup Spot -->
                        <div class="trans-meta-grid">
                            <div class="meta-item">
                                <span class="meta-label">Agreed Value:</span>
                                <span class="meta-val highlight-val">
                                    <?php if ($t['TransactionType'] === 'Donation'): ?>
                                        FREE (Donation)
                                    <?php elseif ($t['TransactionType'] === 'Exchange'): ?>
                                        ITEM SWAP
                                    <?php else: ?>
                                        <?= $currency ?><?= number_format($t['Amount'], 2) ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="meta-item">
                                <span class="meta-label">Campus Counterpart:</span>
                                <span class="meta-val">
                                    <strong><?= htmlspecialchars($counterpartName) ?></strong> (<?= $counterpartRole ?>)
                                    <?php if ($counterpartPhone): ?>
                                        <br><small><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($counterpartPhone) ?></small>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="meta-item full-width">
                                <span class="meta-label"><i class="fa-solid fa-location-dot"></i> Meeting Spot:</span>
                                <span class="meta-val"><?= htmlspecialchars($t['MeetingLocation'] ?: 'Main Campus Student Center') ?></span>
                            </div>

                            <?php if (!empty($t['Notes'])): ?>
                                <div class="meta-item full-width">
                                    <span class="meta-label"><i class="fa-solid fa-comment-dots"></i> Proposal Notes:</span>
                                    <span class="meta-val italic-val">"<?= htmlspecialchars($t['Notes']) ?>"</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Controls -->
                    <div class="trans-card-footer">
                        <div class="trans-footer-left">
                            <a href="/messages?to=<?= $isBuyer ? $t['SellerID'] : $t['BuyerID'] ?>&product_id=<?= $t['ProductID'] ?>" class="btn-chat-link">
                                <i class="fa-solid fa-comment"></i> Open Meetup Chat
                            </a>
                        </div>

                        <div class="trans-footer-actions">
                            <?php if ($status === 'Pending'): ?>
                                <!-- Mark Completed Button -->
                                <form action="/transactions/status/<?= $t['TransactionID'] ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="status" value="Completed">
                                    <button type="submit" class="btn-success-sm" onclick="return confirm('Confirm that physical item handover and inspection was completed safely on campus?');">
                                        <i class="fa-solid fa-check"></i> Confirm Trade Completed
                                    </button>
                                </form>

                                <!-- Cancel Button -->
                                <form action="/transactions/status/<?= $t['TransactionID'] ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="status" value="Cancelled">
                                    <button type="submit" class="btn-cancel-sm" onclick="return confirm('Are you sure you want to cancel this transaction?');">
                                        <i class="fa-solid fa-xmark"></i> Cancel Trade
                                    </button>
                                </form>

                            <?php elseif ($status === 'Completed'): ?>
                                <button type="button" class="btn-review-sm" onclick="openReviewModal(<?= $t['TransactionID'] ?>, <?= $isBuyer ? $t['SellerID'] : $t['BuyerID'] ?>, '<?= htmlspecialchars($counterpartName) ?>', <?= $t['ProductID'] ?>)">
                                    <i class="fa-solid fa-star"></i> Leave Peer Rating & Review
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- PEER RATING & REVIEW MODAL                 -->
<!-- ========================================== -->
<div class="modal-overlay" id="reviewModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fa-solid fa-star text-amber"></i> Review Your Campus Trade Counterpart</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('reviewModal')">&times;</button>
        </div>
        <form action="/transactions/review" method="POST" class="modal-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="transaction_id" id="revTransId" value="">
            <input type="hidden" name="target_user_id" id="revTargetId" value="">
            <input type="hidden" name="product_id" id="revProductId" value="">

            <p>Share your honest experience with <strong id="revTargetName">fellow student</strong> to help keep our campus marketplace trusted.</p>

            <div class="form-group">
                <label>Overall Trust Rating</label>
                <div class="star-rating-select">
                    <input type="radio" id="star5" name="rating" value="5" checked><label for="star5" title="5 stars"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star"><i class="fa-solid fa-star"></i></label>
                </div>
            </div>

            <div class="form-group">
                <label>Review Headline</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Punctual, honest, and great condition!" required>
            </div>

            <div class="form-group">
                <label>Detailed Feedback</label>
                <textarea name="comment" class="form-control" rows="4" placeholder="How was the communication, item condition, and meetup punctuality?" required></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('reviewModal')">Cancel</button>
                <button type="submit" class="btn-primary-block">Publish Campus Review</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(transId, targetId, targetName, productId) {
    document.getElementById('revTransId').value = transId;
    document.getElementById('revTargetId').value = targetId;
    document.getElementById('revProductId').value = productId;
    document.getElementById('revTargetName').innerText = targetName;
    openModal('reviewModal');
}
</script>
