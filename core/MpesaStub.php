<?php
/**
 * Campus Market Hub - Safaricom Daraja M-Pesa API Integration Stub
 * Designed for future campus cashless escrow payments (v2).
 */

namespace App\Core;

class MpesaStub {
    private array $config;

    public function __construct() {
        $this->config = require dirname(__DIR__) . '/config/mpesa.php';
    }

    /**
     * Check if live M-Pesa gateway is enabled
     */
    public function isEnabled(): bool {
        return !empty($this->config['enabled']) && !empty($this->config['consumer_key']);
    }

    /**
     * Initiate STK Push (Lipa Na M-Pesa Online)
     * In v1 sandbox/stub mode, returns simulated success with checkout request ID.
     */
    public function initiateStkPush(string $phoneNumber, float $amount, string $accountReference, string $transactionDesc = 'Campus Marketplace Purchase'): array {
        // Sanitize phone number (standard format: 2547XXXXXXXX)
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '+254')) {
            $phone = substr($phone, 1);
        }

        // STK Push Simulation Mode (v1 out-of-scope for live debit, verified stub)
        if (!$this->isEnabled()) {
            $checkoutRequestId = 'ws_CO_' . date('dmYHis') . '_' . rand(10000, 99999);
            return [
                'success'           => true,
                'status'            => 'SIMULATED',
                'ResponseCode'      => '0',
                'ResponseDescription' => 'Success. Request accepted for processing in campus simulation mode.',
                'MerchantRequestID' => 'MR_' . uniqid(),
                'CheckoutRequestID' => $checkoutRequestId,
                'CustomerMessage'   => 'Success. STK prompt simulated for ' . $phone . ' for KSh ' . number_format($amount, 2),
                'amount'            => $amount,
                'phone'             => $phone,
                'reference'         => $accountReference
            ];
        }

        // Live Daraja Gateway Flow
        try {
            $token = $this->generateAccessToken();
            $timestamp = date('YmdHis');
            $password = base64_encode($this->config['shortcode'] . $this->config['passkey'] . $timestamp);
            $url = $this->config['env'] === 'live' ? $this->config['live_stk_url'] : $this->config['sandbox_stk_url'];

            $curl_post_data = [
                'BusinessShortCode' => $this->config['shortcode'],
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'TransactionType'   => 'CustomerPayBillOnline',
                'Amount'            => round($amount),
                'PartyA'            => $phone,
                'PartyB'            => $this->config['shortcode'],
                'PhoneNumber'       => $phone,
                'CallBackURL'       => $this->config['callback_url'],
                'AccountReference'  => substr($accountReference, 0, 12),
                'TransactionDesc'   => substr($transactionDesc, 0, 20)
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization:Bearer ' . $token]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            return [
                'success' => isset($result['ResponseCode']) && $result['ResponseCode'] == '0',
                'data'    => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Generate OAuth Bearer Access Token from Safaricom Daraja
     */
    private function generateAccessToken(): string {
        $url = $this->config['env'] === 'live' ? $this->config['live_auth_url'] : $this->config['sandbox_auth_url'];
        $credentials = base64_encode($this->config['consumer_key'] . ':' . $this->config['consumer_secret']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['access_token'] ?? '';
    }

    /**
     * Parse Daraja Callback IPN
     */
    public function handleCallback(string $rawJson): array {
        $data = json_decode($rawJson, true);
        if (!$data || !isset($data['Body']['stkCallback'])) {
            return ['status' => 'INVALID_PAYLOAD'];
        }

        $callback = $data['Body']['stkCallback'];
        $resultCode = $callback['ResultCode'] ?? -1;
        $isSuccess = ($resultCode === 0);

        return [
            'status'            => $isSuccess ? 'COMPLETED' : 'FAILED',
            'result_code'       => $resultCode,
            'result_desc'       => $callback['ResultDesc'] ?? '',
            'merchant_request'  => $callback['MerchantRequestID'] ?? '',
            'checkout_request'  => $callback['CheckoutRequestID'] ?? '',
            'meta'              => $callback['CallbackMetadata'] ?? []
        ];
    }
}
