<?php
/**
 * Campus Market Hub - User Profile Dashboard View
 */
use App\Core\Auth;
use App\Core\Storage;

$currency = 'KSh ';
?>

<div class="container profile-page">
    <!-- Profile Hero Card -->
    <div class="content-card profile-hero-card">
        <div class="profile-hero-content">
            <div class="profile-avatar-circle">
                <i class="fa-solid fa-user-graduate"></i>
            </div>
            <div class="profile-meta-info">
                <div class="profile-name-row">
                    <h2><?= htmlspecialchars($user['FullName']) ?></h2>
                    <span class="user-role-badge role-<?= strtolower($user['Role']) ?>"><?= $user['Role'] ?></span>
                    <?php if (!empty($user['IsVerified'])): ?>
                        <span class="verified-pill"><i class="fa-solid fa-circle-check"></i> Verified Campus Member</span>
                    <?php else: ?>
                        <span class="unverified-pill"><i class="fa-solid fa-clock"></i> Verification Pending Admin KYC</span>
                    <?php endif; ?>
                </div>

                <div class="profile-sub-details">
                    <span><i class="fa-solid fa-id-card"></i> <strong><?= htmlspecialchars($user['Registration_StaffNo']) ?></strong></span>
                    <span><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($user['Email']) ?></span>
                    <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($user['PhoneNumber'] ?? 'No phone added') ?></span>
                    <?php if (!empty($user['CampusDepartment'])): ?>
                        <span><i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($user['CampusDepartment']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($user['Bio'])): ?>
                    <p class="profile-bio-text">"<?= htmlspecialchars($user['Bio']) ?>"</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Statistics Matrix -->
        <div class="profile-stats-matrix">
            <div class="matrix-stat-item">
                <span class="matrix-num"><?= $stats['total_listings'] ?></span>
                <span class="matrix-label">Items Listed</span>
            </div>
            <div class="matrix-stat-item">
                <span class="matrix-num"><?= $stats['items_sold'] ?></span>
                <span class="matrix-label">Sold / Donated</span>
            </div>
            <div class="matrix-stat-item">
                <span class="matrix-num"><?= $stats['completed_trades'] ?></span>
                <span class="matrix-label">Trades Completed</span>
            </div>
            <div class="matrix-stat-item">
                <span class="matrix-num text-amber"><i class="fa-solid fa-star"></i> <?= $stats['avg_rating'] ?></span>
                <span class="matrix-label">Trust Score (<?= $stats['review_count'] ?> rev)</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="profile-tabs-wrapper">
        <div class="tabs-nav-bar" id="profileTabs">
            <button class="profile-tab-btn active" onclick="switchProfileTab('tab-settings', this)">
                <i class="fa-solid fa-user-pen"></i> Edit Profile
            </button>
            <button class="profile-tab-btn" onclick="switchProfileTab('tab-security', this)">
                <i class="fa-solid fa-lock"></i> Security & Password
            </button>
            <button class="profile-tab-btn" onclick="switchProfileTab('tab-recommendations', this)">
                <i class="fa-solid fa-sparkles text-primary"></i> My AI Recommendations (<?= count($recommendations) ?>)
            </button>
            <button class="profile-tab-btn" onclick="switchProfileTab('tab-trades', this)">
                <i class="fa-solid fa-receipt"></i> Recent Trades (<?= count($myTransactions) ?>)
            </button>
        </div>

        <!-- ============================================== -->
        <!-- TAB 1: EDIT PROFILE                            -->
        <!-- ============================================== -->
        <div class="profile-tab-pane active" id="tab-settings">
            <form action="/profile/update" method="POST" class="content-card modern-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <h3 class="form-pane-title"><i class="fa-solid fa-user-gear"></i> Personal & Campus Contact Details</h3>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" value="<?= htmlspecialchars($user['FullName']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone / WhatsApp for Meetups</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['PhoneNumber'] ?? '') ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="department">School / Faculty / Department</label>
                        <input type="text" id="department" name="department" class="form-control" value="<?= htmlspecialchars($user['CampusDepartment'] ?? '') ?>" placeholder="e.g. School of Business, Economics Dept">
                    </div>

                    <div class="form-group full-width">
                        <label for="bio">Short Campus Bio</label>
                        <textarea id="bio" name="bio" rows="3" class="form-control" placeholder="Share what course you are pursuing, your campus hobbies, or what gear you trade..."><?= htmlspecialchars($user['Bio'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-actions-bar">
                    <button type="submit" class="btn-submit-post">
                        <i class="fa-solid fa-floppy-disk"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- ============================================== -->
        <!-- TAB 2: SECURITY & PASSWORD                     -->
        <!-- ============================================== -->
        <div class="profile-tab-pane hidden" id="tab-security">
            <form action="/profile/change-password" method="POST" class="content-card modern-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <h3 class="form-pane-title"><i class="fa-solid fa-shield-keyhole"></i> Change Account Password</h3>

                <div class="form-group max-w-md">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>

                <div class="form-group max-w-md">
                    <label for="new_password">New Password (Min. 6 chars) *</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>

                <div class="form-group max-w-md">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <div class="form-actions-bar">
                    <button type="submit" class="btn-submit-post">
                        <i class="fa-solid fa-key"></i> Update Password
                    </button>
                </div>
            </form>
        </div>

        <!-- ============================================== -->
        <!-- TAB 3: PERSONALIZED RECOMMENDATIONS            -->
        <!-- ============================================== -->
        <div class="profile-tab-pane hidden" id="tab-recommendations">
            <div class="content-card">
                <div class="page-header-flex">
                    <div>
                        <h3><i class="fa-solid fa-sparkles text-primary"></i> Tailored Recommendations for Your Profile</h3>
                        <p>Computed by our collaborative & content-based filtering engine based on your activity logs.</p>
                    </div>
                    <form action="/api/recommendations/recompute" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <button type="submit" class="btn-outline-sm">
                            <i class="fa-solid fa-arrows-rotate"></i> Recompute AI Scores
                        </button>
                    </form>
                </div>

                <?php if (empty($recommendations)): ?>
                    <p class="text-muted">No recommendations generated yet. Start exploring and viewing items in the marketplace to feed the algorithm.</p>
                <?php else: ?>
                    <div class="products-grid mt-4">
                        <?php foreach ($recommendations as $rec): ?>
                            <div class="product-card">
                                <div class="product-badge-strip">
                                    <span class="badge-type badge-<?= strtolower($rec['ListingType']) ?>"><?= $rec['ListingType'] ?></span>
                                    <span class="badge-rec-score" title="<?= htmlspecialchars($rec['Reason'] ?? '') ?>">
                                        <i class="fa-solid fa-bolt"></i> <?= round(($rec['RecommendationScore'] ?? 0.8) * 100) ?>% Match
                                    </span>
                                </div>
                                <a href="/products/<?= $rec['ProductID'] ?>" class="product-image-link">
                                    <img src="<?= Storage::resolveUrl($rec['ProductImage']) ?>" alt="thumb" class="product-img">
                                </a>
                                <div class="product-body">
                                    <span class="product-cat-name"><?= htmlspecialchars($rec['CategoryName']) ?></span>
                                    <h3 class="product-title"><a href="/products/<?= $rec['ProductID'] ?>"><?= htmlspecialchars($rec['ProductName']) ?></a></h3>
                                    <div class="product-meta">
                                        <span class="product-price"><?= $currency ?><?= number_format($rec['Price'], 2) ?></span>
                                        <span class="seller-pill"><?= htmlspecialchars(explode(' ', $rec['SellerName'])[0]) ?></span>
                                    </div>
                                    <?php if (!empty($rec['Reason'])): ?>
                                        <div class="rec-reason-text"><i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($rec['Reason']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- TAB 4: RECENT TRADES                           -->
        <!-- ============================================== -->
        <div class="profile-tab-pane hidden" id="tab-trades">
            <div class="content-card">
                <div class="page-header-flex">
                    <h3><i class="fa-solid fa-receipt"></i> Transaction History</h3>
                    <a href="/transactions" class="btn-outline-sm">Full Transaction Hub</a>
                </div>

                <?php if (empty($myTransactions)): ?>
                    <p class="text-muted">You have not engaged in any marketplace trades yet.</p>
                <?php else: ?>
                    <div class="table-responsive-card">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ref</th>
                                    <th>Item</th>
                                    <th>Counterpart</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($myTransactions, 0, 8) as $tr): ?>
                                    <tr>
                                        <td><strong>#TX-<?= $tr['TransactionID'] ?></strong></td>
                                        <td><?= htmlspecialchars($tr['ProductName']) ?></td>
                                        <td><?= htmlspecialchars(($tr['BuyerID'] == Auth::id()) ? $tr['SellerName'] : $tr['BuyerName']) ?></td>
                                        <td><?= $tr['TransactionType'] ?></td>
                                        <td><span class="status-pill status-<?= strtolower($tr['TransactionStatus']) ?>"><?= $tr['TransactionStatus'] ?></span></td>
                                        <td><?= date('M j, Y', strtotime($tr['TransactionDate'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchProfileTab(paneId, btn) {
    document.querySelectorAll('.profile-tab-pane').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.profile-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(paneId).classList.remove('hidden');
    btn.classList.add('active');
}
</script>
