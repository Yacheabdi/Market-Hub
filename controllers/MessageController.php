<?php
/**
 * Campus Market Hub - Real-Time Buyer-Seller Messaging Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use App\Models\UserActivityLog;

class MessageController extends Controller {
    /**
     * Chat Hub & Thread View (/messages)
     */
    public function index(): void {
        Auth::requireLogin();
        $currentUserId = Auth::id();

        // Target interlocutor and product from query parameters (e.g., /messages?to=3&product_id=4)
        $targetUserId = (int)$this->input('to', 0);
        $productId    = (int)$this->input('product_id', 0);

        // Fetch all conversations for current user
        $conversations = Message::getConversationsForUser($currentUserId);

        // If target user is not specified, default to first conversation
        if (!$targetUserId && !empty($conversations)) {
            $targetUserId = (int)$conversations[0]['InterlocutorID'];
            $productId    = (int)$conversations[0]['ProductID'];
        }

        $activeThread = [];
        $activeUser   = null;
        $activeProduct = null;

        if ($targetUserId) {
            $activeUser = User::find($targetUserId);
            if ($productId) {
                $activeProduct = Product::getByIdWithDetails($productId);
            }

            // Retrieve thread messages
            $activeThread = Message::getThread($currentUserId, $targetUserId, $productId ?: null);

            // Mark unread messages as read
            Message::markThreadRead($currentUserId, $targetUserId, $productId ?: null);
        }

        $this->view('messages/index', [
            'pageTitle'     => 'Campus Messages & Meetup Chat - Campus Market Hub',
            'conversations' => $conversations,
            'activeThread'  => $activeThread,
            'activeUser'    => $activeUser,
            'activeProduct' => $activeProduct,
            'targetUserId'  => $targetUserId,
            'productId'     => $productId
        ]);
    }

    /**
     * Send Message API Endpoint (/api/messages/send)
     */
    public function send(): void {
        Auth::requireLogin();
        $this->validateCsrf();

        $receiverId  = (int)$this->input('receiver_id');
        $productId   = (int)$this->input('product_id', 0);
        $messageText = trim($this->input('message_text', ''));

        if (!$receiverId || empty($messageText)) {
            $this->json(['success' => false, 'error' => 'Receiver ID and message content are required.'], 400);
        }

        if ($receiverId === Auth::id()) {
            $this->json(['success' => false, 'error' => 'Cannot send messages to yourself.'], 400);
        }

        $messageId = Message::send(Auth::id(), $receiverId, $productId ?: null, $messageText);

        // Track messaging interaction for recommendation scoring
        UserActivityLog::record(
            Auth::id(),
            'message_sent',
            $productId ?: null
        );

        $this->json([
            'success'     => true,
            'message_id'  => $messageId,
            'sender_id'   => Auth::id(),
            'receiver_id' => $receiverId,
            'product_id'  => $productId,
            'text'        => htmlspecialchars($messageText),
            'date_sent'   => date('Y-m-d H:i:s'),
            'formatted_time' => date('g:i A')
        ]);
    }

    /**
     * AJAX Long-Polling Endpoint for Incoming Messages (/api/messages/poll)
     */
    public function poll(): void {
        Auth::requireLogin();
        $currentUserId = Auth::id();

        $lastId       = (int)$this->input('last_id', 0);
        $otherUserId  = (int)$this->input('other_user_id', 0);
        $productId    = (int)$this->input('product_id', 0);

        // Query newly received messages
        $newMessages = Message::pollNewMessages(
            $currentUserId,
            $lastId,
            $otherUserId ?: null,
            $productId ?: null
        );

        // If new messages found, mark them read
        if (!empty($newMessages) && $otherUserId) {
            Message::markThreadRead($currentUserId, $otherUserId, $productId ?: null);
        }

        $formatted = array_map(function($m) use ($currentUserId) {
            return [
                'MessageID'      => (int)$m['MessageID'],
                'SenderID'       => (int)$m['SenderID'],
                'ReceiverID'     => (int)$m['ReceiverID'],
                'ProductID'      => (int)$m['ProductID'],
                'MessageText'    => htmlspecialchars($m['MessageText']),
                'DateSent'       => $m['DateSent'],
                'FormattedTime'  => date('g:i A', strtotime($m['DateSent'])),
                'IsMine'         => ((int)$m['SenderID'] === $currentUserId)
            ];
        }, $newMessages);

        $this->json([
            'success'      => true,
            'messages'     => $formatted,
            'unread_total' => Message::getUnreadCount($currentUserId)
        ]);
    }

    /**
     * Unread count badge API (/api/messages/unread-count)
     */
    public function unreadCount(): void {
        if (!Auth::check()) {
            $this->json(['unread' => 0]);
        }

        $count = Message::getUnreadCount(Auth::id());
        $this->json(['unread' => $count]);
    }
}
