<?php
/**
 * Campus Market Hub - Application Configuration
 */

return [
    'name'        => getenv('APP_NAME') ?: 'Campus Market Hub',
    'env'         => getenv('APP_ENV') ?: 'development',
    'debug'       => getenv('APP_DEBUG') !== false ? filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN) : true,
    'url'         => getenv('APP_URL') ?: 'http://localhost:8000',
    'timezone'    => 'Africa/Nairobi',
    'currency'    => 'KES', // Default campus currency, can be changed to USD/EUR
    'currency_symbol' => 'KSh ',
    
    // University Verification Rules
    'allowed_email_domains' => [
        'edu',
        'ac.ke',
        'ku.ac.ke',
        'uonbi.ac.ke',
        'strathmore.edu',
        'jkuat.ac.ke',
        'mku.ac.ke',
        'usiu.ac.ke',
        'campus.edu'
    ],
    
    // Upload settings
    'upload' => [
        'max_file_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'local_path'    => dirname(__DIR__) . '/public/uploads/products',
        'web_path'      => '/uploads/products/'
    ],

    // Realtime chat settings
    'socket' => [
        'enabled' => true,
        'url'     => getenv('SOCKET_SERVER_URL') ?: 'http://localhost:3000',
        'poll_fallback_ms' => 3000 // Fallback AJAX polling frequency
    ]
];
