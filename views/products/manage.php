<?php
/**
 * Campus Market Hub - Manage My Listings View
 */
use App\Core\Storage;

$currency = 'KSh ';
?>

<div class="container manage-listings-page">
    <div class="page-header-flex">
        <div>
            <h2><i class="fa-solid fa-boxes-stacked text-primary"></i> Manage My Campus Listings</h2>
            <p>Update availability, edit details, or mark items as sold once your campus meetup is done.</p>
        </div>
        <a href="/products/create" class="btn-primary-sm">
            <i class="fa-solid fa-plus"></i> Post New Item
        </a>
    </div>

    <!-- Quick Stats Summary -->
    <div class="manage-stats-grid">
        <div class="stat-pill-card">
            <div class="stat-icon icon-blue"><i class="fa-solid fa-layer-group"></i></div>
            <div>
                <span class="stat-num"><?= count($listings) ?></span>
                <span class="stat-sub">Total Listings</span>
            </div>
        </div>
        <div class="stat-pill-card">
            <div class="stat-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <span class="stat-num"><?= $activeCount ?></span>
                <span class="stat-sub">Active in Marketplace</span>
            </div>
        </div>
        <div class="stat-pill-card">
            <div class="stat-icon icon-amber"><i class="fa-solid fa-handshake"></i></div>
            <div>
                <span class="stat-num"><?= $soldCount ?></span>
                <span class="stat-sub">Sold / Donated</span>
            </div>
        </div>
    </div>

    <?php if (empty($listings)): ?>
        <div class="empty-state-card">
            <div class="empty-state-icon"><i class="fa-solid fa-box-open"></i></div>
            <h3>You haven't posted any items yet</h3>
            <p>Declutter your hostel room or sell your semester textbooks to fellow campus students.</p>
            <a href="/products/create" class="btn-primary-sm"><i class="fa-solid fa-plus"></i> Post Your First Item</a>
        </div>
    <?php else: ?>
        <div class="content-card table-responsive-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Posted</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $item): ?>
                        <tr>
                            <td>
                                <div class="table-product-cell">
                                    <img src="<?= Storage::resolveUrl($item['ProductImage']) ?>" alt="thumb" class="table-thumb">
                                    <div>
                                        <a href="/products/<?= $item['ProductID'] ?>" class="table-item-title"><?= htmlspecialchars($item['ProductName']) ?></a>
                                        <div class="table-item-sub"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['PickupLocation']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($item['CategoryName']) ?></td>
                            <td><span class="badge-type badge-<?= strtolower($item['ListingType']) ?>"><?= $item['ListingType'] ?></span></td>
                            <td>
                                <strong>
                                    <?php if ($item['ListingType'] === 'Donate'): ?>
                                        <span class="text-success">Free</span>
                                    <?php elseif ($item['ListingType'] === 'Exchange'): ?>
                                        <span class="text-primary">Swap</span>
                                    <?php else: ?>
                                        <?= $currency ?><?= number_format($item['Price'], 2) ?>
                                    <?php endif; ?>
                                </strong>
                            </td>
                            <td>
                                <span class="status-pill status-<?= strtolower($item['Status']) ?>">
                                    <?= $item['Status'] ?>
                                </span>
                            </td>
                            <td><?= $item['ViewsCount'] ?></td>
                            <td><?= date('M j, Y', strtotime($item['DatePosted'])) ?></td>
                            <td class="text-right">
                                <div class="action-btn-group">
                                    <a href="/products/edit/<?= $item['ProductID'] ?>" class="btn-table-action" title="Edit Listing">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>

                                    <!-- Quick Toggle Sold/Available -->
                                    <form action="/products/toggle-status/<?= $item['ProductID'] ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="status" value="<?= ($item['Status'] === 'Available') ? 'Sold' : 'Available' ?>">
                                        <button type="submit" class="btn-table-action <?= ($item['Status'] === 'Available') ? 'btn-mark-sold' : 'btn-mark-avail' ?>" title="<?= ($item['Status'] === 'Available') ? 'Mark as Sold' : 'Mark as Available' ?>">
                                            <i class="fa-solid <?= ($item['Status'] === 'Available') ? 'fa-check' : 'fa-rotate-left' ?>"></i>
                                        </button>
                                    </form>

                                    <!-- Delete Button -->
                                    <form action="/products/delete/<?= $item['ProductID'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently remove this listing?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <button type="submit" class="btn-table-action text-danger" title="Delete Listing">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
