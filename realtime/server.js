/**
 * Campus Market Hub - Real-Time Socket.io Server
 * Enables instant chat, live typing indicators, and in-app message notifications.
 */

const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
app.use(cors());

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

const PORT = process.env.PORT || 3000;

// Track online users: userId => Set of socketIds
const onlineUsers = new Map();

io.on('connection', (socket) => {
    console.log(`[Socket.io] New client connected: ${socket.id}`);

    // User authentication & presence registration
    socket.on('register_user', (userId) => {
        if (!userId) return;
        const uid = String(userId);
        socket.userId = uid;
        socket.join(`user_${uid}`);

        if (!onlineUsers.has(uid)) {
            onlineUsers.set(uid, new Set());
        }
        onlineUsers.get(uid).add(socket.id);

        console.log(`[Socket.io] User ${uid} registered. Total online: ${onlineUsers.size}`);
        io.emit('user_presence_change', { userId: uid, isOnline: true });
    });

    // Join a product-specific negotiation thread
    socket.on('join_thread', ({ user1, user2, productId }) => {
        const sortedUsers = [user1, user2].sort((a, b) => a - b);
        const roomId = `room_p${productId || 0}_u${sortedUsers[0]}_u${sortedUsers[1]}`;
        socket.join(roomId);
        console.log(`[Socket.io] Socket ${socket.id} joined room: ${roomId}`);
    });

    // Broadcast chat message
    socket.on('send_message', (payload) => {
        const { senderId, receiverId, productId, messageText, messageId, dateSent } = payload;
        const sortedUsers = [senderId, receiverId].sort((a, b) => a - b);
        const roomId = `room_p${productId || 0}_u${sortedUsers[0]}_u${sortedUsers[1]}`;

        // Broadcast to everyone in the room except sender (who already rendered it optimistically)
        socket.to(roomId).emit('new_message', {
            MessageID: messageId,
            SenderID: senderId,
            ReceiverID: receiverId,
            ProductID: productId,
            MessageText: messageText,
            DateSent: dateSent || new Date().toISOString()
        });

        // Also ping the recipient's personal notification channel for notification badges
        socket.to(`user_${receiverId}`).emit('unread_notification', {
            senderId,
            productId,
            preview: messageText.substring(0, 40)
        });
    });

    // Typing indicators
    socket.on('typing_start', ({ receiverId, productId }) => {
        socket.to(`user_${receiverId}`).emit('user_typing', { senderId: socket.userId, productId });
    });

    socket.on('typing_stop', ({ receiverId, productId }) => {
        socket.to(`user_${receiverId}`).emit('user_stopped_typing', { senderId: socket.userId, productId });
    });

    // Disconnect
    socket.on('disconnect', () => {
        const uid = socket.userId;
        if (uid && onlineUsers.has(uid)) {
            const userSockets = onlineUsers.get(uid);
            userSockets.delete(socket.id);
            if (userSockets.size === 0) {
                onlineUsers.delete(uid);
                io.emit('user_presence_change', { userId: uid, isOnline: false });
            }
        }
        console.log(`[Socket.io] Client disconnected: ${socket.id}`);
    });
});

app.get('/health', (req, res) => {
    res.json({
        status: 'online',
        uptime: process.uptime(),
        connectedUsers: onlineUsers.size
    });
});

server.listen(PORT, () => {
    console.log(`🚀 Campus Market Hub Real-Time Socket Server running on port ${PORT}`);
});
