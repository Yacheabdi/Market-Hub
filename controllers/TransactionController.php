<?php
/**
 * Campus Market Hub - Transaction Tracking & Review Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\Message;
use App\Models\ComplaintReview;
use App\Models\UserActivityLog;

class TransactionController extends Controller {
    /**
     * Transactions Tracking View (/transactions)
     */
    public function index(): void {
        Auth::requireLogin();
        $status = $this->input('status'); // Optional filter: Pending, Completed, Cancelled
        
        $transactions = Transaction::getUserTransactions(Auth::id(), $status);

        $this->view('transactions/index', [
            'pageTitle'    => 'Campus Transactions & Exchange Tracker - Campus Market Hub',
            'transactions' => $transactions,
            'activeFilter' => $status ?: 'all'
        ]);
    }

    /**
     * Initiate a Transaction (Purchase / Exchange / Claim Donation / Book Service)
     */
    public function initiate(): void {
        Auth::requireLogin();
        $this->validateCsrf();

        $productId   = (int)$this->input('product_id');
        $transType   = $this->input('transaction_type', 'Purchase');
        $amount      = (float)$this->input('amount', 0.0);
        $location    = $this->input('meeting_location', 'Main Campus Student Center');
        $notes       = $this->input('notes', '');

        $product = Product::find($productId);
        if (!$product || $product['Status'] !== 'Available') {
            $this->setFlash('error', 'This campus listing is no longer available.');
            $this->redirect('/');
        }

        if ($product['UserID'] == Auth::id()) {
            $this->setFlash('error', 'You cannot initiate a transaction on your own listing.');
            $this->redirect('/products/' . $productId);
        }

        // Adjust price for donations/exchanges
        if (in_array($transType, ['Donation', 'Exchange'])) {
            $amount = 0.00;
        }

        $transId = Transaction::create([
            'ProductID'         => $productId,
            'BuyerID'           => Auth::id(),
            'SellerID'          => $product['UserID'],
            'TransactionType'   => $transType,
            'Amount'            => $amount,
            'MeetingLocation'   => $location,
            'Notes'             => $notes,
            'TransactionDate'   => date('Y-m-d H:i:s'),
            'TransactionStatus' => 'Pending'
        ]);

        // Automatically trigger a chat message to the seller alerting them of the proposed trade
        $notificationMsg = "Hi! I initiated a {$transType} request for '{$product['ProductName']}' (Ref #TX-{$transId}). Proposed meetup: {$location}. Notes: " . ($notes ?: 'Looking forward to meeting on campus!');
        Message::send(Auth::id(), (int)$product['UserID'], $productId, $notificationMsg);

        // Record activity log for recommendation scoring
        UserActivityLog::record(
            Auth::id(),
            'transaction_init',
            $productId,
            (int)$product['CategoryID']
        );

        $this->setFlash('success', "Transaction #TX-{$transId} initiated! We have notified the seller via campus chat.");
        $this->redirect('/transactions');
    }

    /**
     * Update Transaction Lifecycle Status
     */
    public function updateStatus(array $params = []): void {
        Auth::requireLogin();
        $this->validateCsrf();

        $id = (int)($params['id'] ?? $this->input('transaction_id'));
        $status = $this->input('status'); // 'Completed' or 'Cancelled'

        $trans = Transaction::getDetails($id);
        if (!$trans) {
            $this->setFlash('error', 'Transaction record not found.');
            $this->redirect('/transactions');
        }

        $userId = Auth::id();
        $isAuthorized = ($userId == $trans['BuyerID'] || $userId == $trans['SellerID'] || Auth::isAdmin());

        if (!$isAuthorized) {
            $this->setFlash('error', 'Unauthorized to modify this transaction.');
            $this->redirect('/transactions');
        }

        if (in_array($status, ['Completed', 'Cancelled'])) {
            Transaction::setStatus($id, $status);

            // Notify counterpart
            $counterpartId = ($userId == $trans['BuyerID']) ? $trans['SellerID'] : $trans['BuyerID'];
            $statusNotice = ($status === 'Completed') 
                ? "Transaction #TX-{$id} has been marked as COMPLETED! Thank you for trading safely on Campus Market Hub."
                : "Transaction #TX-{$id} has been CANCELLED.";
            Message::send($userId, (int)$counterpartId, (int)$trans['ProductID'], $statusNotice);

            $this->setFlash('success', "Transaction #TX-{$id} status updated to {$status}.");
        }

        $this->redirect('/transactions');
    }

    /**
     * Submit Counterpart Peer Rating & Review
     */
    public function submitReview(): void {
        Auth::requireLogin();
        $this->validateCsrf();

        $transId      = (int)$this->input('transaction_id');
        $targetUserId = (int)$this->input('target_user_id');
        $rating       = max(1, min(5, (int)$this->input('rating', 5)));
        $title        = $this->input('title', 'Campus Peer Review');
        $comment      = $this->input('comment');

        if (empty($comment)) {
            $this->setFlash('error', 'Please write a brief feedback comment.');
            $this->redirect('/transactions');
        }

        ComplaintReview::submit([
            'ReporterID'   => Auth::id(),
            'TargetUserID' => $targetUserId,
            'ProductID'    => (int)$this->input('product_id', null),
            'Type'         => 'Review',
            'Rating'       => $rating,
            'Title'        => $title,
            'Comment'      => $comment,
            'Status'       => 'Resolved' // Reviews default resolved unless reported
        ]);

        $this->setFlash('success', 'Thank you! Your campus peer review and trust rating have been published.');
        $this->redirect('/transactions');
    }
}
