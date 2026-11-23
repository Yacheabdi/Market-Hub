# Real-Time WebSocket Server (Socket.io)

This directory contains the real-time event server powering instant buyer-seller messaging, room joining, typing indicators, and presence updates for **Campus Market Hub**.

## Architecture & Graceful Fallback
- **Active Mode:** When running, frontend chat instantly connects via WebSockets on port 3000.
- **Graceful Fallback:** If this server is not started (e.g. running on a pure PHP/Apache/XAMPP environment without Node.js), the frontend chat **automatically switches to high-performance AJAX polling** (`/api/messages/poll`) every 3 seconds. The chat application never breaks!

## Setup & Running
```bash
cd realtime
npm install
npm start
```
By default, the server listens on `http://localhost:3000`.
To customize the port, define `PORT=3000` in your environment or `.env`.
