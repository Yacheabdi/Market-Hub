<?php
/**
 * Campus Market Hub - M-Pesa Daraja API Configuration Stub
 * Prepared for future cashless checkout & campus escrow payments.
 */

return [
    'enabled'           => filter_var(getenv('MPESA_ENABLED') ?: false, FILTER_VALIDATE_BOOLEAN),
    'env'               => getenv('MPESA_ENV') ?: 'sandbox', // 'sandbox' or 'live'
    'shortcode'         => getenv('MPESA_SHORTCODE') ?: '174379',
    'passkey'           => getenv('MPESA_PASSKEY') ?: 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
    'consumer_key'      => getenv('MPESA_CONSUMER_KEY') ?: '',
    'consumer_secret'   => getenv('MPESA_CONSUMER_SECRET') ?: '',
    'callback_url'      => getenv('MPESA_CALLBACK_URL') ?: 'http://localhost:8000/api/mpesa/callback',
    'timeout_url'       => getenv('MPESA_TIMEOUT_URL') ?: 'http://localhost:8000/api/mpesa/timeout',
    'initiator_name'    => getenv('MPESA_INITIATOR') ?: 'campus_admin',
    'sandbox_auth_url'  => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
    'live_auth_url'     => 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
    'sandbox_stk_url'   => 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
    'live_stk_url'      => 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
];
