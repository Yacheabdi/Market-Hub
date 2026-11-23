<?php
/**
 * Campus Market Hub - M-Pesa Integration Stub Controller
 * Production-ready placeholder for future Daraja STK Push & Webhook IPN handling.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\MpesaStub;

class MpesaController extends Controller {
    private MpesaStub $mpesa;

    public function __construct() {
        parent::__construct();
        $this->mpesa = new MpesaStub();
    }

    /**
     * API: Initiate STK Push (Simulation / Live)
     */
    public function initiate(): void {
        Auth::requireLogin();

        $phone  = $this->input('phone');
        $amount = (float)$this->input('amount', 0.0);
        $ref    = $this->input('reference', 'CAMPUS_PAY');

        if (empty($phone) || $amount <= 0) {
            $this->json(['success' => false, 'error' => 'Valid phone number and positive amount required.'], 400);
        }

        $result = $this->mpesa->initiateStkPush($phone, $amount, $ref);
        $this->json($result);
    }

    /**
     * Webhook: Callback URL listener from Safaricom Daraja
     */
    public function callback(): void {
        $rawPayload = file_get_contents('php://input');
        
        // Log IPN callback data for verification
        $logFile = dirname(__DIR__) . '/realtime/mpesa_ipn.log';
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $rawPayload . PHP_EOL, FILE_APPEND);

        $parsed = $this->mpesa->handleCallback($rawPayload);
        $this->json(['ResultCode' => 0, 'ResultDesc' => 'Callback received successfully', 'parsed' => $parsed]);
    }
}
