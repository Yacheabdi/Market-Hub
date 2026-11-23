/**
 * Campus Market Hub - Real-Time Chat Engine
 * Supports Socket.io WebSockets with Automatic High-Performance AJAX Polling Fallback.
 */

let lastMessageId = 0;
let pollingInterval = null;
let socket = null;

document.addEventListener('DOMContentLoaded', () => {
    const chatContainer = document.getElementById('chatMessagesContainer');
    if (!chatContainer) return;

    // Scroll to bottom of message history
    scrollToBottom();

    // Determine highest message ID present in DOM
    const bubbles = chatContainer.querySelectorAll('.chat-message-bubble');
    bubbles.forEach(b => {
        const id = parseInt(b.getAttribute('data-id') || '0', 10);
        if (id > lastMessageId) lastMessageId = id;
    });

    // Try connecting to Socket.io server if running
    initSocketConnection();

    // Always start resilient AJAX polling fallback (ensures messages work 100% on standard XAMPP)
    startPolling();
});

/**
 * Scroll chat view to newest messages
 */
function scrollToBottom() {
    const chatContainer = document.getElementById('chatMessagesContainer');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
}

/**
 * Handle user sending a new message
 */
async function handleMessageSubmit(e) {
    e.preventDefault();

    const input = document.getElementById('messageTextInput');
    const text = input.value.trim();
    if (!text) return;

    const receiverId = document.getElementById('receiverId')?.value;
    const productId  = document.getElementById('productId')?.value || 0;
    const csrfToken  = document.getElementById('csrfToken')?.value;

    if (!receiverId) return;

    // Clear input immediately for snappy responsiveness
    input.value = '';

    // 1. Optimistic UI: Append outgoing bubble immediately
    const tempId = 'temp-' + Date.now();
    appendMessageBubble({
        MessageID: tempId,
        SenderID: document.getElementById('currentUserId')?.value,
        MessageText: text,
        FormattedTime: 'Just now',
        IsMine: true
    });

    // 2. Persist message via backend API
    try {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('receiver_id', receiverId);
        formData.append('product_id', productId);
        formData.append('message_text', text);

        const response = await fetch('/api/messages/send', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();
        if (result.success) {
            lastMessageId = Math.max(lastMessageId, result.message_id);
            const tempBubble = document.getElementById('msg-' + tempId);
            if (tempBubble) {
                tempBubble.id = 'msg-' + result.message_id;
                tempBubble.setAttribute('data-id', result.message_id);
            }

            // Emit to socket server if connected
            if (socket && socket.connected) {
                socket.emit('send_message', {
                    messageId: result.message_id,
                    senderId: result.sender_id,
                    receiverId: result.receiver_id,
                    productId: result.product_id,
                    messageText: text,
                    dateSent: result.date_sent
                });
            }
        } else {
            alert('Failed to send message: ' + (result.error || 'Server error'));
        }
    } catch (err) {
        console.error('Network error sending message:', err);
    }
}

/**
 * Append message bubble to chat container
 */
function appendMessageBubble(msg) {
    const chatContainer = document.getElementById('chatMessagesContainer');
    if (!chatContainer) return;

    // Prevent duplicate rendering
    if (document.getElementById('msg-' + msg.MessageID)) return;

    const bubble = document.createElement('div');
    bubble.className = `chat-message-bubble ${msg.IsMine ? 'bubble-outgoing' : 'bubble-incoming'}`;
    bubble.id = 'msg-' + msg.MessageID;
    bubble.setAttribute('data-id', msg.MessageID);

    bubble.innerHTML = `
        <div class="bubble-sender-name">${msg.IsMine ? 'You' : (msg.SenderName || 'Counterpart')}</div>
        <div class="bubble-text">${escapeHtml(msg.MessageText).replace(/\n/g, '<br>')}</div>
        <div class="bubble-timestamp">${msg.FormattedTime || 'Now'}</div>
    `;

    chatContainer.appendChild(bubble);
    scrollToBottom();
}

/**
 * Resilient AJAX Long-Polling Engine (Falls back cleanly without Node/Socket.io)
 */
function startPolling() {
    if (pollingInterval) clearInterval(pollingInterval);

    const otherUserId = document.getElementById('receiverId')?.value;
    const productId = document.getElementById('productId')?.value;

    if (!otherUserId) return;

    pollingInterval = setInterval(async () => {
        try {
            const url = `/api/messages/poll?last_id=${lastMessageId}&other_user_id=${otherUserId}&product_id=${productId || ''}`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.success && data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    if (msg.MessageID > lastMessageId) {
                        lastMessageId = msg.MessageID;
                        appendMessageBubble(msg);
                    }
                });
            }
        } catch (err) {
            // Gracefully handle offline or network hiccups
        }
    }, 3000);
}

/**
 * Initialize optional Socket.io WebSockets
 */
function initSocketConnection() {
    // If socket.io CDN client script is included
    if (typeof io !== 'undefined') {
        try {
            socket = io('http://localhost:3000', { timeout: 3000 });
            const currentUid = document.getElementById('currentUserId')?.value;
            const otherUserId = document.getElementById('receiverId')?.value;
            const productId = document.getElementById('productId')?.value;

            socket.on('connect', () => {
                console.log('[Socket.io] Connected to campus chat server.');
                if (currentUid) {
                    socket.emit('register_user', currentUid);
                    if (otherUserId) {
                        socket.emit('join_thread', { user1: currentUid, user2: otherUserId, productId });
                    }
                }
            });

            socket.on('new_message', (payload) => {
                lastMessageId = Math.max(lastMessageId, payload.MessageID);
                appendMessageBubble({
                    MessageID: payload.MessageID,
                    SenderID: payload.SenderID,
                    MessageText: payload.MessageText,
                    FormattedTime: new Date(payload.DateSent).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    IsMine: false
                });
            });
        } catch (e) {
            console.log('[Socket.io] Local socket server not detected. Active fallback: AJAX Polling.');
        }
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
