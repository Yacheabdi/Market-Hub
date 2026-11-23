<?php
/**
 * Campus Market Hub - Create New Listing View
 */
?>

<div class="container form-page-container">
    <div class="form-header-bar">
        <div>
            <h2><i class="fa-solid fa-plus-circle text-primary"></i> Post a Campus Listing</h2>
            <p>Share, trade, or sell items within your verified university community.</p>
        </div>
        <a href="/products/manage" class="btn-outline-sm"><i class="fa-solid fa-boxes-stacked"></i> My Listings</a>
    </div>

    <form action="/products/create" method="POST" enctype="multipart/form-data" class="content-card modern-form" id="createProductForm">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="form-grid-2">
            <!-- Listing Type Cards -->
            <div class="form-group full-width">
                <label>Listing Purpose / Model</label>
                <div class="listing-type-selector">
                    <label class="type-radio-card">
                        <input type="radio" name="listing_type" value="Sell" checked onchange="handleTypeChange('Sell')">
                        <div class="type-radio-inner">
                            <i class="fa-solid fa-tag"></i>
                            <strong>For Sale</strong>
                            <small>Set a fair student price</small>
                        </div>
                    </label>

                    <label class="type-radio-card">
                        <input type="radio" name="listing_type" value="Exchange" onchange="handleTypeChange('Exchange')">
                        <div class="type-radio-inner">
                            <i class="fa-solid fa-repeat"></i>
                            <strong>Item Exchange</strong>
                            <small>Swap for items you need</small>
                        </div>
                    </label>

                    <label class="type-radio-card">
                        <input type="radio" name="listing_type" value="Donate" onchange="handleTypeChange('Donate')">
                        <div class="type-radio-inner">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                            <strong>Free / Donate</strong>
                            <small>Help fellow campus peers</small>
                        </div>
                    </label>

                    <label class="type-radio-card">
                        <input type="radio" name="listing_type" value="Service" onchange="handleTypeChange('Service')">
                        <div class="type-radio-inner">
                            <i class="fa-solid fa-user-graduate"></i>
                            <strong>Peer Service</strong>
                            <small>Tutoring, repairs, skills</small>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Product Title -->
            <div class="form-group full-width">
                <label for="product_name">Item / Service Title *</label>
                <input type="text" id="product_name" name="product_name" class="form-control" placeholder="e.g. Calculus: Early Transcendentals (8th Ed) or Casio FX-991EX Calculator" required>
            </div>

            <!-- Category -->
            <div class="form-group">
                <label for="category_id">Marketplace Category *</label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['CategoryID'] ?>"><?= htmlspecialchars($cat['CategoryName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Price (KES) -->
            <div class="form-group" id="priceGroup">
                <label for="price">Price (KSh) *</label>
                <input type="number" id="price" name="price" step="0.01" class="form-control" placeholder="e.g. 1500.00" required>
                <small class="form-text" id="priceHelp">Fixed price for campus buyer</small>
            </div>

            <!-- Condition -->
            <div class="form-group">
                <label for="condition_status">Item Condition *</label>
                <select id="condition_status" name="condition_status" class="form-control" required>
                    <option value="Brand New">Brand New (Unopened / Mint)</option>
                    <option value="Like New" selected>Like New (Used gently, clean)</option>
                    <option value="Good">Good (Working with minor cosmetic wear)</option>
                    <option value="Fair">Fair (Noticeable wear, fully functional)</option>
                </select>
            </div>

            <!-- Pickup Location -->
            <div class="form-group">
                <label for="pickup_location">Preferred Campus Meetup Spot *</label>
                <input type="text" id="pickup_location" name="pickup_location" class="form-control" placeholder="e.g. Main Library Gate, Student Center, Nyayo Hostels" value="Main Campus Student Center" required>
            </div>

            <!-- Exchange Preferences (Only visible when Exchange selected) -->
            <div class="form-group full-width hidden" id="exchangePrefGroup">
                <label for="exchange_preferences">What would you like to exchange this for?</label>
                <input type="text" id="exchange_preferences" name="exchange_preferences" class="form-control" placeholder="e.g. Looking for a Scientific Calculator or Year 2 Medicine text">
            </div>

            <!-- Description -->
            <div class="form-group full-width">
                <label for="description">Item Description & Specifications *</label>
                <textarea id="description" name="description" rows="5" class="form-control" placeholder="Describe the item condition, edition, included accessories, reasons for selling, and pickup availability..." required></textarea>
            </div>

            <!-- Product Image Upload -->
            <div class="form-group full-width">
                <label>Item Photograph</label>
                <div class="image-upload-dropzone" onclick="document.getElementById('product_image').click()">
                    <i class="fa-solid fa-cloud-arrow-up dropzone-icon"></i>
                    <p class="dropzone-text"><strong>Click to upload photo</strong> or drag and drop image here</p>
                    <span class="dropzone-hint">PNG, JPG, WEBP up to 5MB (Hosted automatically on Cloudinary / Local Disk)</span>
                    <input type="file" id="product_image" name="product_image" accept="image/*" class="file-input-hidden" onchange="previewUploadImage(this)">
                </div>
                <!-- Image Preview Container -->
                <div id="imagePreviewWrapper" class="image-preview-wrapper hidden">
                    <img id="imagePreview" src="#" alt="Upload preview" class="image-preview-img">
                    <button type="button" class="btn-remove-preview" onclick="removeImagePreview()">&times; Remove Photo</button>
                </div>
            </div>
        </div>

        <div class="form-actions-bar">
            <a href="/" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-submit-post">
                <i class="fa-solid fa-paper-plane"></i> Publish Campus Listing
            </button>
        </div>
    </form>
</div>

<script>
function handleTypeChange(type) {
    const priceGroup = document.getElementById('priceGroup');
    const priceInput = document.getElementById('price');
    const exchangeGroup = document.getElementById('exchangePrefGroup');

    if (type === 'Donate') {
        priceGroup.classList.add('opacity-50');
        priceInput.value = '0.00';
        priceInput.readOnly = true;
        exchangeGroup.classList.add('hidden');
    } else if (type === 'Exchange') {
        priceGroup.classList.add('opacity-50');
        priceInput.value = '0.00';
        priceInput.readOnly = true;
        exchangeGroup.classList.remove('hidden');
    } else {
        priceGroup.classList.remove('opacity-50');
        priceInput.readOnly = false;
        if (priceInput.value === '0.00') priceInput.value = '';
        exchangeGroup.classList.add('hidden');
    }
}

function previewUploadImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreviewWrapper').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImagePreview() {
    document.getElementById('product_image').value = '';
    document.getElementById('imagePreviewWrapper').classList.add('hidden');
}
</script>
