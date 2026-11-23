<?php
/**
 * Campus Market Hub - Edit Listing View
 */
use App\Core\Storage;
?>

<div class="container form-page-container">
    <div class="form-header-bar">
        <div>
            <h2><i class="fa-solid fa-pen-to-square text-primary"></i> Edit Listing</h2>
            <p>Modify listing details or update availability status.</p>
        </div>
        <a href="/products/manage" class="btn-outline-sm"><i class="fa-solid fa-boxes-stacked"></i> Back to My Listings</a>
    </div>

    <form action="/products/edit/<?= $product['ProductID'] ?>" method="POST" enctype="multipart/form-data" class="content-card modern-form">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="form-grid-2">
            <!-- Availability Status Toggle -->
            <div class="form-group full-width">
                <label>Listing Status</label>
                <div class="status-selector-row">
                    <label class="status-pill-radio">
                        <input type="radio" name="status" value="Available" <?= ($product['Status'] === 'Available') ? 'checked' : '' ?>>
                        <span class="status-pill status-available"><i class="fa-solid fa-circle-check"></i> Available</span>
                    </label>
                    <label class="status-pill-radio">
                        <input type="radio" name="status" value="Reserved" <?= ($product['Status'] === 'Reserved') ? 'checked' : '' ?>>
                        <span class="status-pill status-reserved"><i class="fa-solid fa-clock"></i> Reserved (Meetup Agreed)</span>
                    </label>
                    <label class="status-pill-radio">
                        <input type="radio" name="status" value="Sold" <?= ($product['Status'] === 'Sold') ? 'checked' : '' ?>>
                        <span class="status-pill status-sold"><i class="fa-solid fa-lock"></i> Sold / Completed</span>
                    </label>
                </div>
            </div>

            <!-- Product Title -->
            <div class="form-group full-width">
                <label for="product_name">Item / Service Title *</label>
                <input type="text" id="product_name" name="product_name" class="form-control" value="<?= htmlspecialchars($product['ProductName']) ?>" required>
            </div>

            <!-- Category -->
            <div class="form-group">
                <label for="category_id">Marketplace Category *</label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['CategoryID'] ?>" <?= ($cat['CategoryID'] == $product['CategoryID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['CategoryName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Price -->
            <div class="form-group">
                <label for="price">Price (KSh)</label>
                <input type="number" id="price" name="price" step="0.01" class="form-control" value="<?= $product['Price'] ?>" required>
            </div>

            <!-- Listing Type -->
            <div class="form-group">
                <label for="listing_type">Listing Type</label>
                <select id="listing_type" name="listing_type" class="form-control" required>
                    <option value="Sell" <?= ($product['ListingType'] === 'Sell') ? 'selected' : '' ?>>Sell</option>
                    <option value="Exchange" <?= ($product['ListingType'] === 'Exchange') ? 'selected' : '' ?>>Exchange</option>
                    <option value="Donate" <?= ($product['ListingType'] === 'Donate') ? 'selected' : '' ?>>Donate</option>
                    <option value="Service" <?= ($product['ListingType'] === 'Service') ? 'selected' : '' ?>>Service</option>
                </select>
            </div>

            <!-- Condition -->
            <div class="form-group">
                <label for="condition_status">Condition</label>
                <select id="condition_status" name="condition_status" class="form-control">
                    <option value="Brand New" <?= ($product['ConditionStatus'] === 'Brand New') ? 'selected' : '' ?>>Brand New</option>
                    <option value="Like New" <?= ($product['ConditionStatus'] === 'Like New') ? 'selected' : '' ?>>Like New</option>
                    <option value="Good" <?= ($product['ConditionStatus'] === 'Good') ? 'selected' : '' ?>>Good</option>
                    <option value="Fair" <?= ($product['ConditionStatus'] === 'Fair') ? 'selected' : '' ?>>Fair</option>
                </select>
            </div>

            <!-- Pickup Location -->
            <div class="form-group full-width">
                <label for="pickup_location">Campus Pickup Spot</label>
                <input type="text" id="pickup_location" name="pickup_location" class="form-control" value="<?= htmlspecialchars($product['PickupLocation']) ?>" required>
            </div>

            <!-- Exchange Preferences -->
            <div class="form-group full-width">
                <label for="exchange_preferences">Exchange Preferences</label>
                <input type="text" id="exchange_preferences" name="exchange_preferences" class="form-control" value="<?= htmlspecialchars($product['ExchangePreferences'] ?? '') ?>" placeholder="e.g. Willing to swap for a scientific calculator...">
            </div>

            <!-- Description -->
            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" class="form-control" required><?= htmlspecialchars($product['Description']) ?></textarea>
            </div>

            <!-- Current Image & Replacement -->
            <div class="form-group full-width">
                <label>Current Photo</label>
                <div class="edit-image-row">
                    <img src="<?= Storage::resolveUrl($product['ProductImage']) ?>" alt="Current image" class="edit-current-thumb">
                    <div>
                        <p><strong>Replace Photo (Optional)</strong></p>
                        <input type="file" name="product_image" accept="image/*" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions-bar">
            <a href="/products/manage" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-submit-post">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </div>
    </form>
</div>
