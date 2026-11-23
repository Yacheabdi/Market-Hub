<?php
/**
 * Campus Market Hub - Administrator & Fraud Control Desk
 */
use App\Core\Storage;

$currency = 'KSh ';
?>

<div class="container admin-page">
    <div class="admin-header-bar">
        <div>
            <div class="admin-badge"><i class="fa-solid fa-shield-halved"></i> Campus Security & Operations</div>
            <h2>Campus Market Hub - Administrator Control Panel</h2>
            <p>Monitor platform safety, verify student and staff accounts, moderate suspicious listings, and resolve fraud reports.</p>
        </div>
        <div class="admin-time-pill">
            <i class="fa-solid fa-clock"></i> Server Time: <?= date('Y-m-d H:i:s') ?>
        </div>
    </div>

    <!-- Executive Metric KPI Cards -->
    <div class="admin-kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon icon-blue"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-meta">
                <span class="kpi-value"><?= $metrics['total_users'] ?></span>
                <span class="kpi-title">Campus Members</span>
                <span class="kpi-sub"><?= $metrics['verified_users'] ?> Verified Students/Staff</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-green"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div class="kpi-meta">
                <span class="kpi-value"><?= $metrics['active_listings'] ?></span>
                <span class="kpi-title">Active Listings</span>
                <span class="kpi-sub"><?= $metrics['sold_listings'] ?> Items Sold / Exchanged</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-purple"><i class="fa-solid fa-handshake-simple"></i></div>
            <div class="kpi-meta">
                <span class="kpi-value"><?= $metrics['completed_trades'] ?></span>
                <span class="kpi-title">Completed Trades</span>
                <span class="kpi-sub"><?= $currency ?><?= number_format($metrics['total_volume'], 2) ?> Volume</span>
            </div>
        </div>

        <div class="kpi-card <?= ($metrics['pending_reports'] > 0) ? 'kpi-alert' : '' ?>">
            <div class="kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="kpi-meta">
                <span class="kpi-value"><?= $metrics['pending_reports'] ?></span>
                <span class="kpi-title">Fraud Reports Pending</span>
                <span class="kpi-sub">Campus Safety Review Desk</span>
            </div>
        </div>
    </div>

    <!-- Admin Sub-Navigation Tabs -->
    <div class="admin-tabs-nav">
        <a href="/admin?tab=overview" class="admin-tab-link <?= ($activeTab === 'overview' || $activeTab === 'users') ? 'active' : '' ?>">
            <i class="fa-solid fa-users-gear"></i> University User Verification (<?= count($users) ?>)
        </a>
        <a href="/admin?tab=listings" class="admin-tab-link <?= ($activeTab === 'listings') ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i> Listing Moderation (<?= count($listings) ?>)
        </a>
        <a href="/admin?tab=reports" class="admin-tab-link <?= ($activeTab === 'reports') ? 'active' : '' ?>">
            <i class="fa-solid fa-flag text-danger"></i> Fraud & Scam Reports (<?= count($fraudReports) ?>)
        </a>
    </div>

    <!-- ============================================== -->
    <!-- TAB 1: USER VERIFICATION & MANAGEMENT          -->
    <!-- ============================================== -->
    <?php if ($activeTab === 'overview' || $activeTab === 'users'): ?>
        <div class="content-card">
            <div class="page-header-flex">
                <div>
                    <h3><i class="fa-solid fa-id-card"></i> Student & Staff KYC Account Management</h3>
                    <p>Verify registration credentials or revoke marketplace privileges.</p>
                </div>

                <form action="/admin" method="GET" class="admin-search-inline">
                    <input type="hidden" name="tab" value="users">
                    <input type="text" name="user_search" placeholder="Search name, RegNo, or email..." value="<?= htmlspecialchars($_GET['user_search'] ?? '') ?>" class="form-control-sm">
                    <select name="user_role" class="form-control-sm" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <option value="Student" <?= (($_GET['user_role'] ?? '') === 'Student') ? 'selected' : '' ?>>Students</option>
                        <option value="Staff" <?= (($_GET['user_role'] ?? '') === 'Staff') ? 'selected' : '' ?>>Staff</option>
                    </select>
                    <button type="submit" class="btn-primary-sm">Filter</button>
                </form>
            </div>

            <div class="table-responsive-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Reg / Staff ID</th>
                            <th>Campus Email</th>
                            <th>Role</th>
                            <th>Faculty / Department</th>
                            <th>KYC Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>#<?= $u['UserID'] ?></td>
                                <td><strong><?= htmlspecialchars($u['FullName']) ?></strong></td>
                                <td><code><?= htmlspecialchars($u['Registration_StaffNo']) ?></code></td>
                                <td><?= htmlspecialchars($u['Email']) ?></td>
                                <td><span class="user-role-badge role-<?= strtolower($u['Role']) ?>"><?= $u['Role'] ?></span></td>
                                <td><?= htmlspecialchars($u['CampusDepartment'] ?: 'Unspecified') ?></td>
                                <td>
                                    <?php if (!empty($u['IsVerified'])): ?>
                                        <span class="badge-status-verified"><i class="fa-solid fa-circle-check"></i> Verified</span>
                                    <?php else: ?>
                                        <span class="badge-status-unverified"><i class="fa-solid fa-clock"></i> Unverified</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <form action="/admin/users/toggle-verification/<?= $u['UserID'] ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <button type="submit" class="btn-table-action <?= empty($u['IsVerified']) ? 'btn-verify-action' : 'btn-revoke-action' ?>" 
                                                title="<?= empty($u['IsVerified']) ? 'Verify University ID' : 'Revoke Verification' ?>">
                                            <i class="fa-solid <?= empty($u['IsVerified']) ? 'fa-user-check' : 'fa-user-xmark' ?>"></i>
                                            <?= empty($u['IsVerified']) ? 'Verify' : 'Revoke' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ============================================== -->
    <!-- TAB 2: LISTINGS MODERATION                     -->
    <!-- ============================================== -->
    <?php elseif ($activeTab === 'listings'): ?>
        <div class="content-card">
            <div class="page-header-flex">
                <div>
                    <h3><i class="fa-solid fa-boxes-stacked"></i> Marketplace Listing Moderation</h3>
                    <p>Review items, feature campus picks, or remove counterfeit, prohibited, or spam listings.</p>
                </div>
            </div>

            <div class="table-responsive-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Seller</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th class="text-right">Moderate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $item): ?>
                            <tr>
                                <td>
                                    <div class="table-product-cell">
                                        <img src="<?= Storage::resolveUrl($item['ProductImage']) ?>" alt="thumb" class="table-thumb">
                                        <div>
                                            <a href="/products/<?= $item['ProductID'] ?>" target="_blank" class="table-item-title"><?= htmlspecialchars($item['ProductName']) ?></a>
                                            <div class="table-item-sub"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['PickupLocation']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($item['CategoryName']) ?></td>
                                <td><?= htmlspecialchars($item['SellerName']) ?></td>
                                <td><span class="badge-type badge-<?= strtolower($item['ListingType']) ?>"><?= $item['ListingType'] ?></span></td>
                                <td>
                                    <?php if ($item['ListingType'] === 'Donate'): ?>
                                        Free
                                    <?php elseif ($item['ListingType'] === 'Exchange'): ?>
                                        Swap
                                    <?php else: ?>
                                        <?= $currency ?><?= number_format($item['Price'], 2) ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-pill status-<?= strtolower($item['Status']) ?>"><?= $item['Status'] ?></span></td>
                                <td>
                                    <form action="/admin/listings/toggle-featured/<?= $item['ProductID'] ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <button type="submit" class="btn-star-toggle <?= !empty($item['IsFeatured']) ? 'active' : '' ?>" title="Toggle Featured Item">
                                            <i class="fa-solid fa-star"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-right">
                                    <form action="/admin/listings/delete/<?= $item['ProductID'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('Permanently remove this listing from the marketplace?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <button type="submit" class="btn-danger-sm" title="Remove Listing">
                                            <i class="fa-solid fa-trash-can"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ============================================== -->
    <!-- TAB 3: FRAUD & COMPLAINT REPORTS DESK          -->
    <!-- ============================================== -->
    <?php elseif ($activeTab === 'reports'): ?>
        <div class="content-card">
            <div class="page-header-flex">
                <div>
                    <h3><i class="fa-solid fa-shield-virus text-danger"></i> Fraud Reports & Peer Disciplinary Desk</h3>
                    <p>Examine complaints submitted by students or staff regarding scams, upfront deposits, or fake listings.</p>
                </div>
            </div>

            <?php if (empty($fraudReports)): ?>
                <div class="empty-state-card">
                    <div class="empty-state-icon"><i class="fa-solid fa-shield-check text-success"></i></div>
                    <h3>No active complaints filed</h3>
                    <p>The campus marketplace community is operating safely with no pending moderation tickets.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive-card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Reason / Title</th>
                                <th>Reporting Student</th>
                                <th>Flagged User / Item</th>
                                <th>Report Details</th>
                                <th>Status</th>
                                <th class="text-right">Resolution Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fraudReports as $rep): ?>
                                <tr>
                                    <td><strong>#R-<?= $rep['ReviewID'] ?></strong></td>
                                    <td><strong class="text-danger"><?= htmlspecialchars($rep['Title'] ?? 'Fraud Complaint') ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($rep['ReporterName']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($rep['ReporterRegNo']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($rep['ProductName'])): ?>
                                            <a href="/products/<?= $rep['ProductID'] ?>" target="_blank">
                                                <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($rep['ProductName']) ?>
                                            </a><br>
                                        <?php endif; ?>
                                        <small>Seller: <?= htmlspecialchars($rep['AccusedName'] ?? 'Unknown') ?></small>
                                    </td>
                                    <td>
                                        <p class="report-comment-preview">"<?= htmlspecialchars($rep['Comment']) ?>"</p>
                                    </td>
                                    <td>
                                        <span class="badge-report-<?= strtolower($rep['Status']) ?>"><?= $rep['Status'] ?></span>
                                    </td>
                                    <td class="text-right">
                                        <?php if ($rep['Status'] === 'Pending'): ?>
                                            <div class="action-btn-group">
                                                <!-- Take down listing & resolve -->
                                                <?php if (!empty($rep['ProductID'])): ?>
                                                    <form action="/admin/reports/resolve/<?= $rep['ReviewID'] ?>" method="POST" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                        <input type="hidden" name="action" value="TakeDown">
                                                        <button type="submit" class="btn-danger-sm" title="Remove Listing and Resolve Report" onclick="return confirm('Take down the reported item and resolve this complaint?');">
                                                            <i class="fa-solid fa-ban"></i> Take Down Item
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Dismiss -->
                                                <form action="/admin/reports/resolve/<?= $rep['ReviewID'] ?>" method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="action" value="Dismissed">
                                                    <button type="submit" class="btn-cancel-sm" title="Dismiss Report">
                                                        <i class="fa-solid fa-xmark"></i> Dismiss
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted"><i class="fa-solid fa-check"></i> Action Completed</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
