<?php
/**
 * Campus Market Hub - Cloudinary Configuration
 * Used for production cloud media hosting with seamless fallback to local disk storage.
 */

return [
    'enabled'     => !empty(getenv('CLOUDINARY_CLOUD_NAME')) && !empty(getenv('CLOUDINARY_API_KEY')),
    'cloud_name'  => getenv('CLOUDINARY_CLOUD_NAME') ?: '',
    'api_key'     => getenv('CLOUDINARY_API_KEY') ?: '',
    'api_secret'  => getenv('CLOUDINARY_API_SECRET') ?: '',
    'upload_preset' => getenv('CLOUDINARY_UPLOAD_PRESET') ?: 'campus_market_hub',
    'folder'      => 'campus_market_products',
];
