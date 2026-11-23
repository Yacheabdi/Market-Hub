<?php
/**
 * Campus Market Hub - Real-Time Buyer-Seller Chat View
 */
use App\Core\Auth;
use App\Core\Storage;

$currency = 'KSh ';
$currentUid = Auth::id();
?>

<div class="container chat-page-container">
    <div class="chat-layout-card">
        <!-- ============================================== -->
        <!-- LEFT PANEL: CONVERSATIONS INBOX LIST           -->
        <!-- ============================================== -->
        <aside class="chat-sidebar">
            <div class="chat-sidebar-header">
                <h3><i class="fa-solid fa-comments"></i> Campus Messages</h3>
                <span class="chat-threads-count"><?= count($conversations) ?></span>
            </div>

            <div class="chat-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="conversationSearchInput" placeholder="Filter conversations..." onkeyup="filterConversations()">
            </div>

            <div class="conversation-list" id="conversationList">
                <?php if (empty($conversations)): ?>
                    <div class="empty-chat-sidebar">
                        <i class="fa-solid fa-comment-slash"></i>
                        <p>No messages yet. Click <strong>"Message Seller"</strong> on any marketplace listing to start negotiating.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <?php 
                            $isSelected = ($targetUserId == $conv['InterlocutorID'] && (!$productId || $productId == $conv['ProductID']));
                        ?>
                        <a href="/messages?to=<?= $conv['InterlocutorID'] ?>&product_id=<?= $conv['ProductID'] ?>" 
                           class="conversation-item <?= $isSelected ? 'active' : '' ?>"
                           data-name="<?= strtolower(htmlspecialchars($conv['InterlocutorName'])) ?>"
                           data-product="<?= strtolower(htmlspecialchars($conv['ProductName'] ?? '')) ?>">
                            
                            <div class="conv-avatar">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>

                            <div class="conv-meta">
                                <div class="conv-top-row">
                                    <span class="conv-name">
                                        <?= htmlspecialchars($conv['InterlocutorName']) ?>
                                        <?php if (!empty($conv['InterlocutorVerified'])): ?>
                                            <i class="fa-solid fa-circle-check text-success" title="Verified Campus Member"></i>
                                        <?php endif; ?>
                                    </span>
                                    <span class="conv-time"><?= date('M j', strtotime($conv['DateSent'])) ?></span>
                                </div>

                                <?php if (!empty($conv['ProductName'])): ?>
                                    <div class="conv-product-tag">
                                        <i class="fa-solid fa-tag"></i> <?= htmlspecialchars(substr($conv['ProductName'], 0, 26)) ?>...
                                    </div>
                                <?php endif; ?>

                                <div class="conv-preview-row">
                                    <span class="conv-preview"><?= htmlspecialchars(substr($conv['MessageText'], 0, 32)) ?>...</span>
                                    <?php if (!empty($conv['UnreadCount']) && $conv['UnreadCount'] > 0): ?>
                                        <span class="conv-unread-pill"><?= $conv['UnreadCount'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- ============================================== -->
        <!-- RIGHT PANEL: ACTIVE CHAT THREAD                -->
        <!-- ============================================== -->
        <section class="chat-main-area">
            <?php if ($activeUser): ?>
                <!-- Chat Header -->
                <div class="chat-main-header">
                    <div class="chat-user-info">
                        <div class="chat-user-avatar">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <div>
                            <div class="chat-user-name">
                                <?= htmlspecialchars($activeUser['FullName']) ?>
                                <?php if (!empty($activeUser['IsVerified'])): ?>
                                    <span class="verified-tag"><i class="fa-solid fa-circle-check"></i> Verified</span>
                                <?php endif; ?>
                            </div>
                            <div class="chat-user-sub">
                                <?= htmlspecialchars($activeUser['Role']) ?> &bull; <?= htmlspecialchars($activeUser['Registration_StaffNo']) ?>
                                <?php if (!empty($activeUser['CampusDepartment'])): ?>
                                    &bull; <?= htmlspecialchars($activeUser['CampusDepartment']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Product Context Pill in Header -->
                    <?php if ($activeProduct): ?>
                        <a href="/products/<?= $activeProduct['ProductID'] ?>" class="chat-product-preview-pill" title="View Full Listing Details">
                            <img src="<?= Storage::resolveUrl($activeProduct['ProductImage']) ?>" alt="thumb" class="chat-prod-thumb">
                            <div class="chat-prod-info">
                                <span class="chat-prod-title"><?= htmlspecialchars($activeProduct['ProductName']) ?></span>
                                <span class="chat-prod-price">
                                    <?php if ($activeProduct['ListingType'] === 'Donate'): ?>
                                        Free Donation
                                    <?php elseif ($activeProduct['ListingType'] === 'Exchange'): ?>
                                        Exchange
                                    <?php else: ?>
                                        <?= $currency ?><?= number_format($activeProduct['Price'], 2) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Campus Meetup Safety Reminder Strip -->
                <div class="chat-safety-reminder">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span><strong>Campus Safety:</strong> Arrange handovers in busy daytime spots (e.g., Library Foyer or Student Cafeteria). Never transfer money before inspecting the item.</span>
                </div>

                <!-- Messages Scrollable Body -->
                <div class="chat-messages-container" id="chatMessagesContainer">
                    <?php if (empty($activeThread)): ?>
                        <div class="empty-thread-state">
                            <i class="fa-regular fa-comment-dots"></i>
                            <p>No previous messages in this conversation. Start the negotiation below!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activeThread as $msg): ?>
                            <?php $isMine = ($msg['SenderID'] == $currentUid); ?>
                            <div class="chat-message-bubble <?= $isMine ? 'bubble-outgoing' : 'bubble-incoming' ?>" id="msg-<?= $msg['MessageID'] ?>" data-id="<?= $msg['MessageID'] ?>">
                                <div class="bubble-sender-name"><?= $isMine ? 'You' : htmlspecialchars($msg['SenderName']) ?></div>
                                <div class="bubble-text"><?= nl2br(htmlspecialchars($msg['MessageText'])) ?></div>
                                <div class="bubble-timestamp"><?= date('g:i A', strtotime($msg['DateSent'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Live Typing Indicator -->
                    <div id="typingIndicator" class="typing-indicator hidden">
                        <span></span><span></span><span></span>
                        <small id="typingText">Typing...</small>
                    </div>
                </div>

                <!-- Quick Negotiation Shortcut Pills -->
                <div class="chat-quick-actions">
                    <button type="button" class="quick-pill" onclick="sendQuickMessage('Hi! Is this item still available for inspection?')">
                        <i class="fa-solid fa-hand-wave"></i> Still available?
                    </button>
                    <button type="button" class="quick-pill" onclick="sendQuickMessage('Can we meet at the Main Library tomorrow afternoon?')">
                        <i class="fa-solid fa-location-dot"></i> Meet at Library?
                    </button>
                    <button type="button" class="quick-pill" onclick="sendQuickMessage('Would you consider a slight student discount?')">
                        <i class="fa-solid fa-tag"></i> Student discount?
                    </button>
                    <button type="button" class="quick-pill" onclick="sendQuickMessage('What time works best for you to complete the handover?')">
                        <i class="fa-solid fa-clock"></i> Schedule meetup time
                    </button>
                </div>

                <!-- Chat Input Form -->
                <form id="chatForm" class="chat-input-area" onsubmit="handleMessageSubmit(event)">
                    <input type="hidden" id="csrfToken" value="<?= $csrfToken ?>">
                    <input type="hidden" id="receiverId" value="<?= $activeUser['UserID'] ?>">
                    <input type="hidden" id="productId" value="<?= $productId ?>">
                    <input type="hidden" id="currentUserId" value="<?= $currentUid ?>">

                    <div class="input-wrapper">
                        <textarea id="messageTextInput" class="chat-textarea" placeholder="Type your message, offer, or meetup proposal..." rows="1" required onkeydown="handleEnterKey(event)"></textarea>
                    </div>

                    <button type="submit" class="btn-send-message" id="sendBtn" title="Send Message">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>

            <?php else: ?>
                <!-- No Conversation Selected State -->
                <div class="no-selection-state">
                    <div class="no-selection-icon">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>
                    <h3>Campus Messages & Negotiation Hub</h3>
                    <p>Select an ongoing conversation from the left inbox or visit any marketplace listing to message the student or staff seller directly.</p>
                    <a href="/" class="btn-primary-sm"><i class="fa-solid fa-compass"></i> Explore Campus Listings</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<script>
function filterConversations() {
    const q = document.getElementById('conversationSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('.conversation-item');
    items.forEach(item => {
        const name = item.getAttribute('data-name') || '';
        const prod = item.getAttribute('data-product') || '';
        if (name.includes(q) || prod.includes(q)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function handleEnterKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('chatForm').dispatchEvent(new Event('submit'));
    }
}

function sendQuickMessage(text) {
    const input = document.getElementById('messageTextInput');
    if (input) {
        input.value = text;
        document.getElementById('chatForm').dispatchEvent(new Event('submit'));
    }
}
</script>
