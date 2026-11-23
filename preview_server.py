#!/usr/bin/env python3
"""
Campus Market Hub - Interactive Preview Server
Pure Python 3 standard library (no pip dependencies required).
Powers live browser preview, testing, and full interactive UI demo on macOS.
"""

import os
import sys
import json
import sqlite3
import urllib.parse
from http.server import HTTPServer, BaseHTTPRequestHandler
from http import cookies
import mimetypes

PORT = 8000
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_FILE = os.path.join(BASE_DIR, 'preview.db')

def init_db():
    conn = sqlite3.connect(DB_FILE)
    c = conn.cursor()
    c.executescript('''
    CREATE TABLE IF NOT EXISTS users (
        UserID INTEGER PRIMARY KEY AUTOINCREMENT,
        FullName TEXT NOT NULL,
        Registration_StaffNo TEXT UNIQUE NOT NULL,
        Email TEXT UNIQUE NOT NULL,
        Password TEXT NOT NULL,
        PhoneNumber TEXT,
        Role TEXT DEFAULT 'Student',
        IsVerified INTEGER DEFAULT 0,
        CampusDepartment TEXT,
        Bio TEXT,
        CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS categories (
        CategoryID INTEGER PRIMARY KEY AUTOINCREMENT,
        CategoryName TEXT UNIQUE NOT NULL,
        CategorySlug TEXT UNIQUE NOT NULL,
        Icon TEXT DEFAULT 'tag',
        Description TEXT,
        DisplayOrder INTEGER DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS products (
        ProductID INTEGER PRIMARY KEY AUTOINCREMENT,
        UserID INTEGER NOT NULL,
        CategoryID INTEGER NOT NULL,
        ProductName TEXT NOT NULL,
        Description TEXT NOT NULL,
        Price REAL DEFAULT 0.0,
        ListingType TEXT DEFAULT 'Sell',
        ProductImage TEXT NOT NULL,
        ConditionStatus TEXT DEFAULT 'Good',
        PickupLocation TEXT DEFAULT 'Main Campus Student Center',
        Status TEXT DEFAULT 'Available',
        ViewsCount INTEGER DEFAULT 0,
        IsFeatured INTEGER DEFAULT 0,
        ExchangePreferences TEXT,
        DatePosted DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (UserID) REFERENCES users(UserID),
        FOREIGN KEY (CategoryID) REFERENCES categories(CategoryID)
    );

    CREATE TABLE IF NOT EXISTS messages (
        MessageID INTEGER PRIMARY KEY AUTOINCREMENT,
        SenderID INTEGER NOT NULL,
        ReceiverID INTEGER NOT NULL,
        ProductID INTEGER,
        MessageText TEXT NOT NULL,
        DateSent DATETIME DEFAULT CURRENT_TIMESTAMP,
        IsRead INTEGER DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS transactions (
        TransactionID INTEGER PRIMARY KEY AUTOINCREMENT,
        ProductID INTEGER NOT NULL,
        BuyerID INTEGER NOT NULL,
        SellerID INTEGER NOT NULL,
        TransactionType TEXT DEFAULT 'Purchase',
        Amount REAL DEFAULT 0.0,
        MeetingLocation TEXT,
        Notes TEXT,
        TransactionDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        TransactionStatus TEXT DEFAULT 'Pending',
        CompletedAt DATETIME
    );

    CREATE TABLE IF NOT EXISTS recommendations (
        RecommendationID INTEGER PRIMARY KEY AUTOINCREMENT,
        UserID INTEGER NOT NULL,
        ProductID INTEGER NOT NULL,
        RecommendationScore REAL DEFAULT 0.0,
        Reason TEXT,
        DateGenerated DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS complaints_reviews (
        ReviewID INTEGER PRIMARY KEY AUTOINCREMENT,
        ReporterID INTEGER NOT NULL,
        TargetUserID INTEGER,
        ProductID INTEGER,
        Type TEXT DEFAULT 'Review',
        Rating INTEGER,
        Title TEXT,
        Comment TEXT NOT NULL,
        Status TEXT DEFAULT 'Pending',
        AdminNotes TEXT,
        CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ''')

    # Seed data if users table is empty
    c.execute("SELECT COUNT(*) FROM users")
    if c.fetchone()[0] == 0:
        c.executemany('''
        INSERT INTO users (UserID, FullName, Registration_StaffNo, Email, Password, PhoneNumber, Role, IsVerified, CampusDepartment, Bio)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ''', [
            (1, 'System Administrator', 'ADMIN/2026/001', 'admin@campus.edu', 'Password@123', '+254711000000', 'Admin', 1, 'ICT Directorate', 'Campus Marketplace Safety & Operations Administrator.'),
            (2, 'John Doe Mwangi', 'SCM211-0024/2023', 'john.doe@student.campus.edu', 'Password@123', '+254712345678', 'Student', 1, 'Computer Science & Software Eng', 'Third-year CS student. Coding, reading, and trading tech gear.'),
            (3, 'Dr. Sarah Wambui', 'STF/ENG/1042', 'dr.sarah@campus.edu', 'Password@123', '+254722334455', 'Staff', 1, 'School of Engineering & Architecture', 'Senior Lecturer in Electrical Engineering.'),
            (4, 'Mary Wangari', 'BBA212-0489/2024', 'mary.w@student.campus.edu', 'Password@123', '+254733445566', 'Student', 1, 'School of Business & Economics', 'Sophomore marketing major.'),
            (5, 'Kevin Otieno', 'EDU201-1190/2022', 'kevin.o@student.campus.edu', 'Password@123', '+254799887766', 'Student', 1, 'School of Education', 'Final-year Education student donating lecture kits.'),
            (6, 'Brian Kipchumba', 'MED100-0012/2023', 'brian.k@student.campus.edu', 'Password@123', '+254788112233', 'Student', 0, 'School of Medicine', 'Pre-med student. Account pending KYC.')
        ])

        c.executemany('''
        INSERT INTO categories (CategoryID, CategoryName, CategorySlug, Icon, Description, DisplayOrder)
        VALUES (?, ?, ?, ?, ?, ?)
        ''', [
            (1, 'Textbooks', 'textbooks', 'book-open', 'Course textbooks and manuals', 1),
            (2, 'Electronics', 'electronics', 'laptop', 'Laptops, calculators, headphones', 2),
            (3, 'Furniture', 'furniture', 'couch', 'Hostel desks, chairs, shoe racks', 3),
            (4, 'Clothing', 'clothing', 'shirt', 'Campus hoodies and apparel', 4),
            (5, 'Learning Materials', 'learning-materials', 'clipboard-list', 'Drafting kits and lab sets', 5),
            (6, 'Services', 'services', 'briefcase', 'Peer tutoring and thesis formatting', 6),
            (7, 'Free/Donations', 'free-donations', 'heart-handshake', 'Free items donated to peers', 7)
        ])

        c.executemany('''
        INSERT INTO products (ProductID, UserID, CategoryID, ProductName, Description, Price, ListingType, ProductImage, ConditionStatus, PickupLocation, Status, ViewsCount, IsFeatured, ExchangePreferences)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ''', [
            (1, 2, 1, 'Calculus: Early Transcendentals (8th Ed) - Stewart', 'Mandatory engineering and mathematics calculus textbook. Clean pages with minimal pencil highlights. Includes companion problem solution formula sheet.', 1500.00, 'Sell', 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&auto=format&fit=crop&q=80', 'Like New', 'Science Complex Block B, Foyer', 'Available', 142, 1, None),
            (2, 2, 2, 'Casio FX-991EX ClassWiz Scientific Calculator', 'High-speed natural textbook display calculator. Ideal for engineering, actuarial science, and physics exams. Solar powered with backup battery.', 2200.00, 'Sell', 'https://images.unsplash.com/photo-1594980596870-8aa52a78d8cd?w=800&auto=format&fit=crop&q=80', 'Brand New', 'Main Library Gate B', 'Available', 230, 1, None),
            (3, 4, 3, 'Foldable Hostel Study Desk & Phone Stand', 'Space-saving wooden study table. Lightweight, sturdy steel frame, folds completely flat under any hostel bunk bed. Comes with cup holder.', 2800.00, 'Sell', 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?w=800&auto=format&fit=crop&q=80', 'Good', 'Hall 6 / Nyayo Hostel Reception', 'Available', 98, 0, None),
            (4, 3, 2, 'Dell 24" UltraSharp IPS Monitor with HDMI & Type-C', 'Donated by engineering faculty lab upgrade. Pristine color accuracy, adjustable pivot stand, HDMI/DisplayPort cables included. Perfect for coding.', 8500.00, 'Sell', 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=800&auto=format&fit=crop&q=80', 'Like New', 'Engineering Faculty Office Room 304', 'Available', 315, 1, None),
            (5, 4, 4, 'Official University Fleece Pullover Hoodie (Size M)', 'Official university merchandise in navy blue and gold trim. Thick thermal fleece, worn twice during orientation week.', 1200.00, 'Sell', 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=800&auto=format&fit=crop&q=80', 'Like New', 'Student Center cafeteria entrance', 'Available', 84, 0, None),
            (6, 2, 2, 'Arduino Uno R3 Starter Kit with Sensors & Breadboard', 'Complete prototyping kit with servos, ultrasonic sensors, jumper wires, and LCD. Looking to trade for Raspberry Pi 3 or 4.', 0.00, 'Exchange', 'https://images.unsplash.com/photo-1553406830-ef2513450d76?w=800&auto=format&fit=crop&q=80', 'Good', 'Tech Innovation Hub, 2nd Floor', 'Available', 167, 1, 'Looking to trade for a Raspberry Pi 3/4 or USB Logic Analyzer'),
            (7, 5, 7, 'White Cotton Chemistry Lab Coat (Unisex Large)', 'Clean lab coat required for Year 1 & 2 Chemistry practicals. Given away for free to support any fresher in need of practical gear.', 0.00, 'Donate', 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800&auto=format&fit=crop&q=80', 'Good', 'Hall 3 Quadrangle / Common Room', 'Available', 210, 1, None),
            (8, 5, 1, 'Organic Chemistry (3rd Edition) - David Klein', 'Essential textbook for pre-med and biochemistry students. Thorough problem walkthroughs. Offering for free to any sophomore.', 0.00, 'Donate', 'https://images.unsplash.com/photo-1532012164546-f432f2e3777f?w=800&auto=format&fit=crop&q=80', 'Good', 'Medical Library Front Desk', 'Available', 178, 0, None),
            (9, 2, 6, 'Python, Data Structures & Web Development Peer Tutoring', 'Need help with programming assignments, algorithms, or web projects? 1-on-1 friendly campus tutoring sessions at the library.', 500.00, 'Service', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&auto=format&fit=crop&q=80', 'Brand New', 'Main Library Discussion Room 4', 'Available', 119, 1, None),
            (10, 4, 6, 'Professional Thesis & Academic Paper Formatting (APA/IEEE)', 'Fast and compliant LaTeX/Word formatting for final year projects, citations, and table of figures.', 800.00, 'Service', 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=800&auto=format&fit=crop&q=80', 'Brand New', 'Online / Business School Courtyard', 'Available', 76, 0, None),
            (11, 5, 5, 'Rotring Rapidograph Technical Drawing Pen Set', 'Complete drafting kit with compass and set squares. Gently used during First Year Engineering Workshop.', 1400.00, 'Sell', 'https://images.unsplash.com/photo-1581291518633-83b4ebd1d83e?w=800&auto=format&fit=crop&q=80', 'Good', 'Engineering Workshop Gate', 'Sold', 95, 0, None),
            (12, 3, 2, 'Logitech MX Master 2S Wireless Ergonomic Mouse', 'Ergonomic bluetooth mouse with hyper-fast scroll wheel and gesture button. Great battery life.', 3500.00, 'Sell', 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=800&auto=format&fit=crop&q=80', 'Like New', 'Staff Lounge, Central Administration', 'Available', 190, 0, None)
        ])

        c.executemany('''
        INSERT INTO messages (MessageID, SenderID, ReceiverID, ProductID, MessageText, DateSent, IsRead)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ''', [
            (1, 4, 2, 1, 'Hello John! Is Stewart Calculus still available? Would love to inspect the condition.', '2026-11-15 09:30:00', 1),
            (2, 2, 4, 1, 'Hi Mary, yes it is in great condition! Minimal notes. We can meet outside the Science Complex tomorrow at 11am.', '2026-11-15 09:34:00', 1),
            (3, 4, 2, 1, 'That works perfectly for me. See you at 11am near the fountain!', '2026-11-15 09:40:00', 1),
            (4, 5, 3, 4, 'Dear Dr. Sarah, is the Dell 24 inch monitor still available? Can you accept KSh 7,500 for a final year student?', '2026-11-16 14:10:00', 1),
            (5, 3, 5, 4, 'Hello Kevin, yes I can discount it to KSh 7,500 to support your thesis. Pass by Engineering room 304 tomorrow afternoon.', '2026-11-16 14:25:00', 0)
        ])

        c.executemany('''
        INSERT INTO transactions (TransactionID, ProductID, BuyerID, SellerID, TransactionType, Amount, MeetingLocation, Notes, TransactionDate, TransactionStatus)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ''', [
            (1, 11, 2, 5, 'Purchase', 1400.00, 'Engineering Workshop Gate', 'Met in person, inspected technical drafting kit.', '2026-11-12 15:30:00', 'Completed'),
            (2, 1, 4, 2, 'Purchase', 1500.00, 'Science Complex Foyer', 'Agreed to meet on Thursday 11 AM.', '2026-11-15 09:45:00', 'Pending'),
            (3, 7, 4, 5, 'Donation', 0.00, 'Hall 3 Reception', 'Donation claim for free white chemistry lab coat.', '2026-11-17 10:25:00', 'Pending')
        ])

        c.executemany('''
        INSERT INTO recommendations (RecommendationID, UserID, ProductID, RecommendationScore, Reason)
        VALUES (?, ?, ?, ?, ?)
        ''', [
            (1, 2, 4, 0.94, 'Matched your recent electronics searches and monitor interest'),
            (2, 2, 12, 0.88, 'Frequently paired with laptops and monitors in Electronics'),
            (3, 4, 8, 0.92, 'Because you viewed Stewart Calculus and academic books'),
            (4, 4, 5, 0.81, 'Popular apparel item trending in your hostel cluster')
        ])

        c.executemany('''
        INSERT INTO complaints_reviews (ReviewID, ReporterID, TargetUserID, ProductID, Type, Rating, Title, Comment, Status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ''', [
            (1, 2, 5, 11, 'Review', 5, 'Excellent Seller & Honest Student', 'Item was exactly as described in the listing.', 'Resolved'),
            (2, 4, 6, None, 'FraudReport', None, 'Suspicious Unverified Seller Listing', 'User attempted to request upfront deposit before meeting.', 'Pending')
        ])

    conn.commit()
    conn.close()

init_db()

class CampusMarketHandler(BaseHTTPRequestHandler):
    def get_db(self):
        conn = sqlite3.connect(DB_FILE)
        conn.row_factory = sqlite3.Row
        return conn

    def get_current_user(self):
        cookie_header = self.headers.get('Cookie')
        if not cookie_header:
            return None
        c = cookies.SimpleCookie(cookie_header)
        if 'campus_user' in c:
            try:
                uid = int(c['campus_user'].value)
                db = self.get_db()
                u = db.execute("SELECT * FROM users WHERE UserID = ?", (uid,)).fetchone()
                db.close()
                return u
            except:
                return None
        return None

    def send_html(self, content, status=200, set_cookie=None):
        self.send_response(status)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        if set_cookie:
            self.send_header('Set-Cookie', set_cookie)
        self.end_headers()
        self.wfile.write(content.encode('utf-8'))

    def send_json(self, data, status=200):
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode('utf-8'))

    def redirect(self, location, set_cookie=None):
        self.send_response(302)
        self.send_header('Location', location)
        if set_cookie:
            self.send_header('Set-Cookie', set_cookie)
        self.end_headers()

    def render_layout(self, title, body_content, user=None):
        unread_count = 0
        if user:
            db = self.get_db()
            row = db.execute("SELECT COUNT(*) FROM messages WHERE ReceiverID = ? AND IsRead = 0", (user['UserID'],)).fetchone()
            unread_count = row[0] if row else 0
            db.close()

        user_pill = ''
        if user:
            first_name = user['FullName'].split()[0]
            role = user['Role']
            verified = '<span class="verified-pill"><i class="fa-solid fa-circle-check"></i> Verified Member</span>' if user['IsVerified'] else '<span class="unverified-pill"><i class="fa-solid fa-clock"></i> KYC Pending</span>'
            admin_link = '<div class="dropdown-divider"></div><a href="/admin" class="dropdown-item admin-link"><i class="fa-solid fa-shield-virus"></i> Admin Fraud Desk</a>' if role == 'Admin' else ''

            user_pill = f'''
            <a href="/products/create" class="nav-btn-post">
                <i class="fa-solid fa-plus"></i> <span>Sell / Donate</span>
            </a>
            <a href="/messages" class="nav-link icon-link message-icon-link">
                <i class="fa-solid fa-comments"></i> <span>Chat</span>
                <span class="badge-count unread-badge {'hidden' if unread_count == 0 else ''}">{unread_count}</span>
            </a>
            <a href="/transactions" class="nav-link">
                <i class="fa-solid fa-receipt"></i> <span>Trades</span>
            </a>
            <div class="user-dropdown">
                <button class="user-pill-btn" id="userMenuBtn">
                    <div class="user-avatar-small"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="user-pill-meta">
                        <span class="user-pill-name">{first_name}</span>
                        <span class="user-role-badge role-{role.lower()}">{role}</span>
                    </div>
                    <i class="fa-solid fa-chevron-down caret-icon"></i>
                </button>
                <div class="dropdown-menu" id="userMenuDropdown">
                    <div class="dropdown-header">
                        <div class="dropdown-user-name">{user['FullName']}</div>
                        <div class="dropdown-user-id">ID: {user['Registration_StaffNo']}</div>
                        {verified}
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="/profile" class="dropdown-item"><i class="fa-solid fa-user-gear"></i> My Profile</a>
                    <a href="/products/manage" class="dropdown-item"><i class="fa-solid fa-boxes-stacked"></i> My Listings</a>
                    <a href="/transactions" class="dropdown-item"><i class="fa-solid fa-handshake-simple"></i> My Trades</a>
                    {admin_link}
                    <div class="dropdown-divider"></div>
                    <a href="/logout" class="dropdown-item text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
                </div>
            </div>
            '''
        else:
            user_pill = '''
            <a href="/login" class="nav-link"><i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In</a>
            <a href="/register" class="btn-primary-sm" style="background:#1e3a8a;color:white;padding:0.45rem 0.9rem;border-radius:9999px;font-weight:700;">Register Account</a>
            '''

        return f'''<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{title}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="top-trust-bar">
        <div class="container trust-bar-content">
            <div class="trust-pill">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Official Campus Network: Exclusively for Verified Students & Staff</span>
            </div>
            <div class="trust-links">
                <span><i class="fa-solid fa-location-dot"></i> Safe Campus Meetup Zones: Library & Student Center</span>
            </div>
        </div>
    </div>

    <header class="main-header">
        <div class="container navbar-container">
            <a href="/" class="brand-logo">
                <div class="logo-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <div class="logo-text">
                    <span class="logo-title">Campus<span class="highlight">Market</span></span>
                    <span class="logo-tag">Hub & Exchanges</span>
                </div>
            </a>

            <form action="/" method="GET" class="header-search-form">
                <div class="search-input-group">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="q" placeholder="Search textbooks, electronics, dorm furniture...">
                    <button type="submit" class="search-btn">Search</button>
                </div>
            </form>

            <nav class="nav-actions">
                <a href="/" class="nav-link"><i class="fa-solid fa-compass"></i> <span>Explore</span></a>
                {user_pill}
                <button class="mobile-toggle-btn" id="mobileMenuBtn"><i class="fa-solid fa-bars"></i></button>
            </nav>
        </div>

        <div class="mobile-nav-drawer" id="mobileDrawer">
            <div class="mobile-drawer-header">
                <span class="drawer-title">Campus Market Hub</span>
                <button class="drawer-close-btn" id="closeDrawerBtn">&times;</button>
            </div>
            <div class="mobile-nav-links">
                <a href="/"><i class="fa-solid fa-house"></i> Marketplace Home</a>
                <a href="/products/create"><i class="fa-solid fa-plus"></i> Post an Item</a>
                <a href="/messages"><i class="fa-solid fa-comments"></i> Chat Messages</a>
                <a href="/transactions"><i class="fa-solid fa-receipt"></i> Transactions</a>
                <a href="/profile"><i class="fa-solid fa-user"></i> My Profile</a>
                <a href="/logout" class="text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        {body_content}
    </main>

    <footer class="main-footer">
        <div class="container footer-content">
            <div class="footer-col brand-col">
                <div class="footer-brand"><i class="fa-solid fa-graduation-cap"></i> Campus Market Hub</div>
                <p class="footer-desc">A secure, student-first digital circular economy enabling students, lecturers, and university staff to safely buy, sell, exchange, and donate academic essentials.</p>
                <div class="campus-safety-badge">
                    <i class="fa-solid fa-shield-heart"></i>
                    <div>
                        <strong>Campus Safety Promise</strong>
                        <p>No anonymous sellers. Every account is tied to an institutional ID.</p>
                    </div>
                </div>
            </div>
            <div class="footer-col">
                <h4>Quick Navigation</h4>
                <ul class="footer-links">
                    <li><a href="/"><i class="fa-solid fa-chevron-right"></i> Marketplace Home</a></li>
                    <li><a href="/products/create"><i class="fa-solid fa-chevron-right"></i> Post New Listing</a></li>
                    <li><a href="/?type=Donate"><i class="fa-solid fa-chevron-right"></i> Free Student Donations</a></li>
                    <li><a href="/?type=Exchange"><i class="fa-solid fa-chevron-right"></i> Textbook & Gear Exchanges</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Safety & Campus Code</h4>
                <ul class="footer-links">
                    <li><a href="#"><i class="fa-solid fa-handshake"></i> Safe Meetup Guidelines</a></li>
                    <li><a href="#"><i class="fa-solid fa-triangle-exclamation"></i> Anti-Scam Policy</a></li>
                    <li><a href="/admin"><i class="fa-solid fa-user-shield"></i> Campus Admin Desk</a></li>
                </ul>
                <div class="demo-auth-help">
                    <small><strong>Demo Testing Logins:</strong><br>
                    Student: <code>john.doe@student.campus.edu</code><br>
                    Staff: <code>dr.sarah@campus.edu</code><br>
                    Admin: <code>admin@campus.edu</code> (Password: <code>Password@123</code>)</small>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container footer-bottom-flex">
                <p>&copy; 2026 Campus Market Hub. Interactive University Preview Server.</p>
                <div class="system-status">
                    <span class="status-indicator online"></span>
                    <span>System Status: Fully Operational (Python Preview Server)</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="/js/app.js"></script>
    <script src="/js/chat.js"></script>
</body>
</html>'''

    def do_GET(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        params = urllib.parse.parse_qs(parsed.query)
        user = self.get_current_user()

        # Static assets
        if path.startswith('/css/') or path.startswith('/js/'):
            file_path = os.path.join(BASE_DIR, 'public', path.lstrip('/'))
            if os.path.exists(file_path):
                mime, _ = mimetypes.guess_type(file_path)
                self.send_response(200)
                self.send_header('Content-Type', mime or 'text/plain')
                self.end_headers()
                with open(file_path, 'rb') as f:
                    self.wfile.write(f.read())
                return

        db = self.get_db()

        # 1. HOME / MARKETPLACE DISCOVERY
        if path == '/':
            q = params.get('q', [''])[0].strip()
            cat_id = params.get('category', [''])[0].strip()
            listing_type = params.get('type', [''])[0].strip()
            sort = params.get('sort', ['newest'])[0]

            sql = "SELECT p.*, c.CategoryName, u.FullName as SellerName, u.IsVerified as SellerVerified FROM products p JOIN categories c ON p.CategoryID = c.CategoryID JOIN users u ON p.UserID = u.UserID WHERE p.Status = 'Available'"
            args = []
            if q:
                sql += " AND (p.ProductName LIKE ? OR p.Description LIKE ?)"
                args.extend([f'%{q}%', f'%{q}%'])
            if cat_id:
                sql += " AND p.CategoryID = ?"
                args.append(cat_id)
            if listing_type:
                sql += " AND p.ListingType = ?"
                args.append(listing_type)

            if sort == 'price_asc':
                sql += " ORDER BY p.Price ASC"
            elif sort == 'price_desc':
                sql += " ORDER BY p.Price DESC"
            elif sort == 'popular':
                sql += " ORDER BY p.ViewsCount DESC"
            else:
                sql += " ORDER BY p.ProductID DESC"

            listings = db.execute(sql, args).fetchall()
            categories = db.execute("SELECT c.*, (SELECT COUNT(*) FROM products WHERE CategoryID = c.CategoryID AND Status = 'Available') as count FROM categories c ORDER BY DisplayOrder").fetchall()
            featured = db.execute("SELECT p.*, c.CategoryName, u.FullName as SellerName, u.IsVerified as SellerVerified FROM products p JOIN categories c ON p.CategoryID = c.CategoryID JOIN users u ON p.UserID = u.UserID WHERE p.IsFeatured = 1 AND p.Status = 'Available' LIMIT 4").fetchall()
            recommendations = db.execute("SELECT r.Reason, p.*, c.CategoryName, u.FullName as SellerName, u.IsVerified as SellerVerified FROM recommendations r JOIN products p ON r.ProductID = p.ProductID JOIN categories c ON p.CategoryID = c.CategoryID JOIN users u ON p.UserID = u.UserID WHERE p.Status = 'Available' LIMIT 4").fetchall()

            # Render listings cards
            def render_card(p, is_rec=False, rec_reason=''):
                price_html = f"KSh {p['Price']:,.2f}" if p['ListingType'] not in ('Donate', 'Exchange') else ('FREE DONATION' if p['ListingType'] == 'Donate' else 'FOR SWAP')
                verified_icon = '<i class="fa-solid fa-circle-check text-success" title="Verified Campus Member"></i>' if p['SellerVerified'] else ''
                rec_badge = f'<span class="badge-rec-score"><i class="fa-solid fa-bolt"></i> {rec_reason[:25]}...</span>' if is_rec and rec_reason else ''
                return f'''
                <div class="product-card {'rec-card' if is_rec else ''}">
                    <div class="product-badge-strip">
                        <span class="badge-type badge-{p['ListingType'].lower()}">{p['ListingType']}</span>
                        {rec_badge}
                    </div>
                    <a href="/products/{p['ProductID']}" class="product-image-link">
                        <img src="{p['ProductImage']}" alt="{p['ProductName']}" class="product-img" loading="lazy">
                    </a>
                    <div class="product-body">
                        <div class="product-category-row">
                            <span class="product-cat-name">{p['CategoryName']}</span>
                            <span class="product-condition-pill">{p['ConditionStatus']}</span>
                        </div>
                        <h3 class="product-title"><a href="/products/{p['ProductID']}">{p['ProductName']}</a></h3>
                        <div class="product-meta">
                            <div class="product-price">{price_html}</div>
                            <div class="seller-pill">
                                <i class="fa-solid fa-user-graduate"></i>
                                <span>{p['SellerName'].split()[0]}</span>
                                {verified_icon}
                            </div>
                        </div>
                        <div class="product-location"><i class="fa-solid fa-location-dot"></i> {p['PickupLocation']}</div>
                    </div>
                </div>
                '''

            listings_html = ''.join(render_card(p) for p in listings) if listings else '<div class="empty-state-card"><h3>No campus items found matching your criteria.</h3></div>'
            featured_html = ''.join(render_card(p) for p in featured)
            rec_html = ''.join(render_card(p, True, p['Reason']) for p in recommendations)

            cat_pills = ''.join(f'<a href="/?category={c["CategoryID"]}" class="cat-pill {"active" if cat_id == str(c["CategoryID"]) else ""}"><i class="fa-solid fa-{c["Icon"]}"></i> {c["CategoryName"]} <span class="cat-count">{c["count"]}</span></a>' for c in categories)

            rec_section = f'''
            <section class="discovery-section recommendation-section">
                <div class="section-title-row">
                    <div class="section-badge"><i class="fa-solid fa-sparkles"></i> AI Recommendation Engine</div>
                    <h2 class="section-title">Recommended For You</h2>
                    <p class="section-sub">Computed from search history, viewed items, and course affinities.</p>
                </div>
                <div class="products-grid">{rec_html}</div>
            </section>
            ''' if rec_html and not q and not cat_id else ''

            featured_section = f'''
            <section class="discovery-section">
                <div class="section-title-row">
                    <div class="section-badge badge-featured-tag"><i class="fa-solid fa-star"></i> Hand-Picked</div>
                    <h2 class="section-title">Featured Campus Listings</h2>
                </div>
                <div class="products-grid">{featured_html}</div>
            </section>
            ''' if featured_html and not q and not cat_id else ''

            content = f'''
            <section class="hero-section">
                <div class="container hero-container">
                    <div class="hero-text-content">
                        <div class="hero-badge"><i class="fa-solid fa-graduation-cap"></i> Verified University Marketplace</div>
                        <h1 class="hero-title">Buy, Sell, Exchange & Donate Within Your Campus</h1>
                        <p class="hero-subtitle">The secure digital marketplace exclusively for verified students and staff. Trade textbooks, tech gear, hostel furniture, or donate revision kits with zero broker fees.</p>
                        <div class="hero-filter-pills">
                            <a href="/?type=" class="type-pill {'active' if not listing_type else ''}"><i class="fa-solid fa-border-all"></i> All</a>
                            <a href="/?type=Sell" class="type-pill {'active' if listing_type=='Sell' else ''}"><i class="fa-solid fa-tag"></i> Buy & Sell</a>
                            <a href="/?type=Exchange" class="type-pill {'active' if listing_type=='Exchange' else ''}"><i class="fa-solid fa-repeat"></i> Exchanges</a>
                            <a href="/?type=Donate" class="type-pill {'active' if listing_type=='Donate' else ''}"><i class="fa-solid fa-hand-holding-heart"></i> Free Donations</a>
                            <a href="/?type=Service" class="type-pill {'active' if listing_type=='Service' else ''}"><i class="fa-solid fa-user-graduate"></i> Tutoring & Services</a>
                        </div>
                    </div>
                    <div class="hero-actions-card">
                        <h3>Start Trading on Campus</h3>
                        <p>Verified university ID required to post or negotiate.</p>
                        <div class="hero-card-buttons">
                            <a href="/products/create" class="btn-hero-primary"><i class="fa-solid fa-plus-circle"></i> Post an Item</a>
                            <a href="/products/manage" class="btn-hero-secondary"><i class="fa-solid fa-boxes-stacked"></i> Manage My Listings</a>
                        </div>
                        <div class="hero-security-note"><i class="fa-solid fa-shield-check"></i> 100% Student & Staff Verified Community</div>
                    </div>
                </div>
            </section>

            <section class="category-strip">
                <div class="container">
                    <div class="category-scroll-wrapper">
                        <a href="/" class="cat-pill {'active' if not cat_id else ''}"><i class="fa-solid fa-cubes"></i> All Categories</a>
                        {cat_pills}
                    </div>
                </div>
            </section>

            <div class="container main-marketplace-grid">
                <aside class="marketplace-sidebar">
                    <form action="/" method="GET" class="filter-card">
                        <div class="filter-header">
                            <h3><i class="fa-solid fa-filter"></i> Refine Search</h3>
                            <a href="/" class="filter-reset">Reset All</a>
                        </div>
                        <div class="filter-group">
                            <label>Category</label>
                            <select name="category" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                {''.join(f'<option value="{c["CategoryID"]}" {"selected" if cat_id == str(c["CategoryID"]) else ""}>{c["CategoryName"]}</option>' for c in categories)}
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Listing Type</label>
                            <select name="type" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <option value="Sell" {"selected" if listing_type=="Sell" else ""}>For Sale</option>
                                <option value="Exchange" {"selected" if listing_type=="Exchange" else ""}>Item Exchange</option>
                                <option value="Donate" {"selected" if listing_type=="Donate" else ""}>Free Donation</option>
                                <option value="Service" {"selected" if listing_type=="Service" else ""}>Services</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select name="sort" class="filter-select" onchange="this.form.submit()">
                                <option value="newest" {"selected" if sort=="newest" else ""}>Newest Postings</option>
                                <option value="price_asc" {"selected" if sort=="price_asc" else ""}>Price: Low to High</option>
                                <option value="price_desc" {"selected" if sort=="price_desc" else ""}>Price: High to Low</option>
                                <option value="popular" {"selected" if sort=="popular" else ""}>Most Popular</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-filter-apply">Apply Filters</button>
                    </form>

                    <div class="safety-sidebar-card">
                        <div class="safety-icon"><i class="fa-solid fa-user-shield"></i></div>
                        <h4>Campus Meetup Protocol</h4>
                        <ul>
                            <li>Always meet in public campus zones.</li>
                            <li>Inspect item condition before cash handover.</li>
                            <li>Report suspicious activity to admin desk.</li>
                        </ul>
                    </div>
                </aside>

                <div class="marketplace-content">
                    {rec_section}
                    {featured_section}
                    <section class="discovery-section">
                        <div class="section-title-row">
                            <h2 class="section-title">All Campus Listings</h2>
                            <p class="section-sub">Showing {len(listings)} verified marketplace items</p>
                        </div>
                        <div class="products-grid">{listings_html}</div>
                    </section>
                </div>
            </div>
            '''
            self.send_html(self.render_layout('Campus Market Hub - University Marketplace', content, user))
            db.close()
            return

        # 2. LOGIN PAGE
        elif path == '/login':
            content = '''
            <div class="auth-page-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="auth-logo"><i class="fa-solid fa-graduation-cap"></i></div>
                        <h2>Campus Sign In</h2>
                        <p>Access your university marketplace, trade with verified peers, and manage transactions.</p>
                    </div>
                    <form action="/login" method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="identifier">University Email or Registration / Staff No</label>
                            <input type="text" id="identifier" name="identifier" class="form-control" placeholder="e.g. john.doe@student.campus.edu" required autofocus>
                        </div>
                        <div class="form-group">
                            <div class="label-row">
                                <label for="password">Campus Password</label>
                                <a href="/forgot-password" class="forgot-link">Forgot Password?</a>
                            </div>
                            <input type="password" id="password" name="password" class="form-control" value="Password@123" required>
                        </div>
                        <button type="submit" class="btn-auth-submit">Sign In to Campus Hub <i class="fa-solid fa-arrow-right"></i></button>
                    </form>
                    <div class="demo-login-widget">
                        <div class="demo-widget-title"><i class="fa-solid fa-bolt"></i> One-Click Demo Testing Accounts:</div>
                        <div class="demo-buttons">
                            <button type="button" class="btn-demo-pill" onclick="fillLogin('john.doe@student.campus.edu', 'Password@123')">Student (John)</button>
                            <button type="button" class="btn-demo-pill" onclick="fillLogin('dr.sarah@campus.edu', 'Password@123')">Staff (Dr. Sarah)</button>
                            <button type="button" class="btn-demo-pill" onclick="fillLogin('admin@campus.edu', 'Password@123')">Campus Admin</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            function fillLogin(e, p) {
                document.getElementById('identifier').value = e;
                document.getElementById('password').value = p;
            }
            </script>
            '''
            self.send_html(self.render_layout('Sign In - Campus Market Hub', content, user))
            db.close()
            return

        # 3. LOGOUT
        elif path == '/logout':
            self.redirect('/login', set_cookie='campus_user=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT')
            db.close()
            return

        # 4. PRODUCT VIEW (/products/:id)
        elif path.startswith('/products/') and path.split('/')[2].isdigit():
            pid = int(path.split('/')[2])
            db.execute("UPDATE products SET ViewsCount = ViewsCount + 1 WHERE ProductID = ?", (pid,))
            db.commit()
            p = db.execute('''
            SELECT p.*, c.CategoryName, u.FullName as SellerName, u.Registration_StaffNo as SellerRegNo,
                   u.Email as SellerEmail, u.PhoneNumber as SellerPhone, u.Role as SellerRole, u.IsVerified as SellerVerified,
                   u.CampusDepartment as SellerDepartment
            FROM products p
            JOIN categories c ON p.CategoryID = c.CategoryID
            JOIN users u ON p.UserID = u.UserID
            WHERE p.ProductID = ?
            ''', (pid,)).fetchone()

            if not p:
                self.redirect('/')
                db.close()
                return

            reviews = db.execute("SELECT cr.*, u.FullName as ReporterName FROM complaints_reviews cr JOIN users u ON cr.ReporterID = u.UserID WHERE cr.TargetUserID = ? AND cr.Type = 'Review'", (p['UserID'],)).fetchall()
            related = db.execute("SELECT p.*, c.CategoryName, u.FullName as SellerName, u.IsVerified as SellerVerified FROM products p JOIN categories c ON p.CategoryID = c.CategoryID JOIN users u ON p.UserID = u.UserID WHERE p.CategoryID = ? AND p.ProductID != ? AND p.Status = 'Available' LIMIT 4", (p['CategoryID'], pid)).fetchall()

            price_str = f"KSh {p['Price']:,.2f}" if p['ListingType'] not in ('Donate', 'Exchange') else ('FREE DONATION' if p['ListingType'] == 'Donate' else 'ITEM EXCHANGE')

            content = f'''
            <div class="container product-detail-page">
                <nav class="breadcrumb-nav">
                    <a href="/"><i class="fa-solid fa-house"></i> Home</a> <i class="fa-solid fa-chevron-right separator"></i>
                    <a href="/?category={p['CategoryID']}">{p['CategoryName']}</a> <i class="fa-solid fa-chevron-right separator"></i>
                    <span>{p['ProductName']}</span>
                </nav>

                <div class="product-detail-layout">
                    <div class="product-gallery-col">
                        <div class="main-image-wrapper">
                            <img src="{p['ProductImage']}" alt="{p['ProductName']}" class="detail-main-img">
                            <div class="image-floating-badges">
                                <span class="badge-type badge-{p['ListingType'].lower()}">{p['ListingType']}</span>
                                <span class="badge-status status-{p['Status'].lower()}">{p['Status']}</span>
                            </div>
                        </div>

                        <div class="content-card description-card">
                            <h3><i class="fa-solid fa-circle-info"></i> Detailed Item Information</h3>
                            <div class="description-body">{p['Description'].replace(chr(10), '<br>')}</div>
                            <div class="specs-grid">
                                <div class="spec-item"><span class="spec-label">Condition:</span> <span class="spec-value">{p['ConditionStatus']}</span></div>
                                <div class="spec-item"><span class="spec-label">Campus Pickup:</span> <span class="spec-value">{p['PickupLocation']}</span></div>
                                <div class="spec-item"><span class="spec-label">Date Posted:</span> <span class="spec-value">{p['DatePosted']}</span></div>
                                <div class="spec-item"><span class="spec-label">Views:</span> <span class="spec-value">{p['ViewsCount']} campus views</span></div>
                            </div>
                        </div>

                        <div class="safety-advisory-card">
                            <i class="fa-solid fa-shield-halved safety-advisory-icon"></i>
                            <div>
                                <h4>Campus Safety Protocol</h4>
                                <p>Always inspect the item in person in a busy campus spot (e.g. Library or Student Center). Never send deposits upfront.</p>
                            </div>
                        </div>
                    </div>

                    <div class="product-action-col">
                        <div class="content-card price-action-card">
                            <span class="listing-cat-label">{p['CategoryName']}</span>
                            <h1 class="detail-product-title">{p['ProductName']}</h1>
                            <div class="detail-price-box">
                                <div class="price-val">{price_str}</div>
                                <p class="price-sub">Fixed campus price &bull; Zero broker fees</p>
                            </div>

                            <div class="detail-btn-stack">
                                <a href="/messages?to={p['UserID']}&product_id={p['ProductID']}" class="btn-chat-seller">
                                    <i class="fa-solid fa-comment-dots"></i> Message Seller / Negotiate
                                </a>
                                <button type="button" class="btn-initiate-trans" onclick="openModal('transactionModal')">
                                    <i class="fa-solid fa-handshake"></i> Initiate Campus Transaction
                                </button>
                                <button type="button" class="btn-report-link" onclick="openModal('reportModal')">
                                    <i class="fa-solid fa-flag"></i> Report Suspicious Listing
                                </button>
                            </div>
                        </div>

                        <div class="content-card seller-trust-card">
                            <div class="seller-card-header">
                                <div class="seller-avatar-large"><i class="fa-solid fa-user-graduate"></i></div>
                                <div class="seller-header-info">
                                    <h3>{p['SellerName']}</h3>
                                    <div class="seller-role-strip">
                                        <span class="user-role-badge role-{p['SellerRole'].lower()}">{p['SellerRole']}</span>
                                        {'<span class="verified-badge-pill"><i class="fa-solid fa-circle-check"></i> Verified Member</span>' if p['SellerVerified'] else ''}
                                    </div>
                                </div>
                            </div>
                            <div class="seller-details-list">
                                <div class="detail-row"><span class="row-label">Campus ID:</span> <span class="row-val">{p['SellerRegNo']}</span></div>
                                <div class="detail-row"><span class="row-label">Faculty:</span> <span class="row-val">{p['SellerDepartment'] or 'General'}</span></div>
                                <div class="detail-row"><span class="row-label">Mobile:</span> <span class="row-val">{p['SellerPhone'] or 'Via Chat'}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-overlay" id="transactionModal">
                <div class="modal-card">
                    <div class="modal-header">
                        <h3>Initiate Campus Transaction</h3>
                        <button type="button" class="modal-close-btn" onclick="closeModal('transactionModal')">&times;</button>
                    </div>
                    <form action="/transactions/initiate" method="POST" class="modal-form">
                        <input type="hidden" name="product_id" value="{p['ProductID']}">
                        <div class="form-group">
                            <label>Transaction Type</label>
                            <select name="transaction_type" class="form-control">
                                <option value="Purchase">Purchase (Meetup & Pay)</option>
                                <option value="Exchange">Propose Item Exchange</option>
                                <option value="Donation">Claim Free Donation</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Meeting Location</label>
                            <input type="text" name="meeting_location" class="form-control" value="{p['PickupLocation']}">
                        </div>
                        <div class="form-group">
                            <label>Notes to Seller</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Propose a meetup day/time..."></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-cancel" onclick="closeModal('transactionModal')">Cancel</button>
                            <button type="submit" class="btn-primary-block" style="background:#059669;color:white;padding:0.7rem 1.2rem;border-radius:6px;font-weight:700;border:none;">Confirm Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
            '''
            self.send_html(self.render_layout(f"{p['ProductName']} - Campus Market Hub", content, user))
            db.close()
            return

        # 5. CHAT MESSAGES (/messages)
        elif path == '/messages':
            if not user:
                self.redirect('/login')
                db.close()
                return

            to_uid = int(params.get('to', ['0'])[0])
            pid = int(params.get('product_id', ['0'])[0])

            # Load active thread
            convs = db.execute('''
            SELECT m.*, u.FullName as InterlocutorName, u.IsVerified as InterlocutorVerified, p.ProductName, p.Price as ProductPrice,
                   CASE WHEN m.SenderID = ? THEN m.ReceiverID ELSE m.SenderID END as InterlocutorID
            FROM messages m
            JOIN users u ON u.UserID = (CASE WHEN m.SenderID = ? THEN m.ReceiverID ELSE m.SenderID END)
            LEFT JOIN products p ON m.ProductID = p.ProductID
            WHERE m.SenderID = ? OR m.ReceiverID = ?
            GROUP BY InterlocutorID
            ORDER BY m.DateSent DESC
            ''', (user['UserID'], user['UserID'], user['UserID'], user['UserID'])).fetchall()

            if not to_uid and convs:
                to_uid = convs[0]['InterlocutorID']
                pid = convs[0]['ProductID'] or 0

            active_user = db.execute("SELECT * FROM users WHERE UserID = ?", (to_uid,)).fetchone() if to_uid else None
            active_prod = db.execute("SELECT * FROM products WHERE ProductID = ?", (pid,)).fetchone() if pid else None

            thread = db.execute('''
            SELECT m.*, u.FullName as SenderName
            FROM messages m
            JOIN users u ON m.SenderID = u.UserID
            WHERE ((m.SenderID = ? AND m.ReceiverID = ?) OR (m.SenderID = ? AND m.ReceiverID = ?))
            ORDER BY m.MessageID ASC
            ''', (user['UserID'], to_uid, to_uid, user['UserID'])).fetchall() if to_uid else []

            conv_html = ''.join(f'''
            <a href="/messages?to={c['InterlocutorID']}&product_id={c['ProductID'] or 0}" class="conversation-item {'active' if c['InterlocutorID'] == to_uid else ''}">
                <div class="conv-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                <div class="conv-meta">
                    <div class="conv-top-row"><span class="conv-name">{c['InterlocutorName']}</span></div>
                    <div class="conv-product-tag">{c['ProductName'] or 'General Campus Chat'}</div>
                    <div class="conv-preview-row"><span class="conv-preview">{c['MessageText'][:35]}...</span></div>
                </div>
            </a>
            ''' for c in convs)

            msgs_html = ''.join(f'''
            <div class="chat-message-bubble {'bubble-outgoing' if m['SenderID'] == user['UserID'] else 'bubble-incoming'}" data-id="{m['MessageID']}">
                <div class="bubble-sender-name">{'You' if m['SenderID'] == user['UserID'] else m['SenderName']}</div>
                <div class="bubble-text">{m['MessageText']}</div>
                <div class="bubble-timestamp">{m['DateSent'][11:16]}</div>
            </div>
            ''' for m in thread)

            content = f'''
            <div class="container chat-page-container">
                <div class="chat-layout-card">
                    <aside class="chat-sidebar">
                        <div class="chat-sidebar-header">
                            <h3><i class="fa-solid fa-comments"></i> Campus Messages</h3>
                            <span class="chat-threads-count">{len(convs)}</span>
                        </div>
                        <div class="conversation-list">{conv_html}</div>
                    </aside>

                    <section class="chat-main-area">
                        {f"""
                        <div class="chat-main-header">
                            <div class="chat-user-info">
                                <div class="chat-user-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                                <div>
                                    <div class="chat-user-name">{active_user['FullName']}</div>
                                    <div class="chat-user-sub">{active_user['Role']} &bull; {active_user['Registration_StaffNo']}</div>
                                </div>
                            </div>
                        </div>
                        <div class="chat-safety-reminder">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Arrange campus handovers in daylight hours at the Library or Student Center.</span>
                        </div>
                        <div class="chat-messages-container" id="chatMessagesContainer">{msgs_html}</div>
                        <div class="chat-quick-actions">
                            <button type="button" class="quick-pill" onclick="sendQuickMessage('Hi! Is this still available?')">Still available?</button>
                            <button type="button" class="quick-pill" onclick="sendQuickMessage('Can we meet at the Library tomorrow?')">Meet at Library?</button>
                        </div>
                        <form id="chatForm" class="chat-input-area" onsubmit="handleMessageSubmit(event)">
                            <input type="hidden" id="receiverId" value="{active_user['UserID']}">
                            <input type="hidden" id="productId" value="{pid}">
                            <input type="hidden" id="currentUserId" value="{user['UserID']}">
                            <div class="input-wrapper">
                                <textarea id="messageTextInput" class="chat-textarea" placeholder="Type a message or offer..." rows="1" required></textarea>
                            </div>
                            <button type="submit" class="btn-send-message"><i class="fa-solid fa-paper-plane"></i></button>
                        </form>
                        """ if active_user else "<div class='no-selection-state'><h3>Select a conversation</h3></div>"}
                    </section>
                </div>
            </div>
            '''
            self.send_html(self.render_layout('Campus Chat - Campus Market Hub', content, user))
            db.close()
            return

        # 6. TRANSACTIONS TRACKER (/transactions)
        elif path == '/transactions':
            if not user:
                self.redirect('/login')
                db.close()
                return

            status_filter = params.get('status', [''])[0]
            sql = '''
            SELECT t.*, p.ProductName, p.ProductImage, p.ListingType,
                   buyer.FullName as BuyerName, seller.FullName as SellerName
            FROM transactions t
            JOIN products p ON t.ProductID = p.ProductID
            JOIN users buyer ON t.BuyerID = buyer.UserID
            JOIN users seller ON t.SellerID = seller.UserID
            WHERE (t.BuyerID = ? OR t.SellerID = ?)
            '''
            args = [user['UserID'], user['UserID']]
            if status_filter:
                sql += " AND t.TransactionStatus = ?"
                args.append(status_filter)
            sql += " ORDER BY t.TransactionDate DESC"

            transactions = db.execute(sql, args).fetchall()

            cards_html = ''.join(f'''
            <div class="content-card transaction-card status-border-{t['TransactionStatus'].lower()}">
                <div class="trans-card-header">
                    <span class="trans-id-tag">REF #TX-{t['TransactionID']}</span>
                    <span class="trans-status-badge badge-{t['TransactionStatus'].lower()}">{t['TransactionStatus']}</span>
                </div>
                <div class="trans-card-body">
                    <div class="trans-product-info">
                        <img src="{t['ProductImage']}" class="trans-prod-thumb">
                        <div>
                            <h4 class="trans-prod-title">{t['ProductName']}</h4>
                            <span class="badge-type badge-{t['ListingType'].lower()}">{t['ListingType']}</span>
                        </div>
                    </div>
                    <div class="trans-meta-grid">
                        <div class="meta-item"><span class="meta-label">Agreed Value:</span> <strong>KSh {t['Amount']:,.2f}</strong></div>
                        <div class="meta-item"><span class="meta-label">Campus Counterpart:</span> {t['SellerName'] if t['BuyerID'] == user['UserID'] else t['BuyerName']}</div>
                        <div class="meta-item full-width"><span class="meta-label">Location:</span> {t['MeetingLocation']}</div>
                    </div>
                </div>
            </div>
            ''' for t in transactions) if transactions else '<div class="empty-state-card"><h3>No transactions found.</h3></div>'

            content = f'''
            <div class="container transactions-page">
                <div class="page-header-flex">
                    <h2><i class="fa-solid fa-receipt text-primary"></i> Campus Trades & Transactions Tracker</h2>
                </div>
                <div class="transaction-filter-tabs">
                    <a href="/transactions" class="tab-btn {'active' if not status_filter else ''}">All</a>
                    <a href="/transactions?status=Pending" class="tab-btn {'active' if status_filter=='Pending' else ''}">Pending</a>
                    <a href="/transactions?status=Completed" class="tab-btn {'active' if status_filter=='Completed' else ''}">Completed</a>
                </div>
                <div class="transactions-list">{cards_html}</div>
            </div>
            '''
            self.send_html(self.render_layout('Transactions - Campus Market Hub', content, user))
            db.close()
            return

        # 7. USER PROFILE (/profile)
        elif path == '/profile':
            if not user:
                self.redirect('/login')
                db.close()
                return

            my_listings = db.execute("SELECT * FROM products WHERE UserID = ? ORDER BY ProductID DESC", (user['UserID'],)).fetchall()
            recs = db.execute("SELECT r.*, p.ProductName, p.Price, p.ProductImage, c.CategoryName FROM recommendations r JOIN products p ON r.ProductID = p.ProductID JOIN categories c ON p.CategoryID = c.CategoryID WHERE r.UserID = ?", (user['UserID'],)).fetchall()

            content = f'''
            <div class="container profile-page">
                <div class="content-card profile-hero-card">
                    <div class="profile-hero-content">
                        <div class="profile-avatar-circle"><i class="fa-solid fa-user-graduate"></i></div>
                        <div class="profile-meta-info">
                            <div class="profile-name-row">
                                <h2>{user['FullName']}</h2>
                                <span class="user-role-badge role-{user['Role'].lower()}">{user['Role']}</span>
                                {'<span class="verified-pill"><i class="fa-solid fa-circle-check"></i> Verified Member</span>' if user['IsVerified'] else '<span class="unverified-pill">Pending KYC</span>'}
                            </div>
                            <div class="profile-sub-details">
                                <span><i class="fa-solid fa-id-card"></i> <strong>{user['Registration_StaffNo']}</strong></span>
                                <span><i class="fa-solid fa-envelope"></i> {user['Email']}</span>
                                <span><i class="fa-solid fa-building-columns"></i> {user['CampusDepartment'] or 'General'}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <h3>My Campus Postings ({len(my_listings)})</h3>
                    <div class="products-grid mt-4">
                        {''.join(f"""
                        <div class="product-card">
                            <a href="/products/{p['ProductID']}"><img src="{p['ProductImage']}" class="product-img"></a>
                            <div class="product-body">
                                <h4>{p['ProductName']}</h4>
                                <div class="product-price">KSh {p['Price']:,.2f}</div>
                            </div>
                        </div>
                        """ for p in my_listings)}
                    </div>
                </div>
            </div>
            '''
            self.send_html(self.render_layout('Profile - Campus Market Hub', content, user))
            db.close()
            return

        # 8. ADMIN DASHBOARD (/admin)
        elif path == '/admin':
            if not user or user['Role'] != 'Admin':
                self.redirect('/login')
                db.close()
                return

            total_users = db.execute("SELECT COUNT(*) FROM users").fetchone()[0]
            total_prods = db.execute("SELECT COUNT(*) FROM products WHERE Status = 'Available'").fetchone()[0]
            total_trans = db.execute("SELECT COUNT(*) FROM transactions WHERE TransactionStatus = 'Completed'").fetchone()[0]
            users_list = db.execute("SELECT * FROM users ORDER BY UserID DESC").fetchall()
            reports_list = db.execute("SELECT cr.*, rep.FullName as RepName FROM complaints_reviews cr JOIN users rep ON cr.ReporterID = rep.UserID").fetchall()

            content = f'''
            <div class="container admin-page">
                <div class="admin-header-bar">
                    <div>
                        <div class="admin-badge"><i class="fa-solid fa-shield-halved"></i> Campus Security & Operations</div>
                        <h2>Campus Administrator Control Panel</h2>
                    </div>
                </div>
                <div class="admin-kpi-grid">
                    <div class="kpi-card"><div class="kpi-icon icon-blue"><i class="fa-solid fa-users"></i></div><div class="kpi-meta"><span class="kpi-value">{total_users}</span><span class="kpi-title">Total Users</span></div></div>
                    <div class="kpi-card"><div class="kpi-icon icon-green"><i class="fa-solid fa-boxes-stacked"></i></div><div class="kpi-meta"><span class="kpi-value">{total_prods}</span><span class="kpi-title">Active Listings</span></div></div>
                    <div class="kpi-card"><div class="kpi-icon icon-purple"><i class="fa-solid fa-handshake"></i></div><div class="kpi-meta"><span class="kpi-value">{total_trans}</span><span class="kpi-title">Completed Trades</span></div></div>
                    <div class="kpi-card"><div class="kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="kpi-meta"><span class="kpi-value">{len(reports_list)}</span><span class="kpi-title">Fraud Reports</span></div></div>
                </div>

                <div class="content-card mt-4">
                    <h3>Registered Students & Staff Accounts</h3>
                    <table class="data-table mt-2">
                        <thead><tr><th>Name</th><th>ID / RegNo</th><th>Email</th><th>Role</th><th>KYC Status</th></tr></thead>
                        <tbody>
                            {''.join(f"""
                            <tr>
                                <td><strong>{u['FullName']}</strong></td>
                                <td><code>{u['Registration_StaffNo']}</code></td>
                                <td>{u['Email']}</td>
                                <td><span class="user-role-badge role-{u['Role'].lower()}">{u['Role']}</span></td>
                                <td>{'<span class="badge-status-verified">Verified</span>' if u['IsVerified'] else '<span class="badge-status-unverified">Unverified</span>'}</td>
                            </tr>
                            """ for u in users_list)}
                        </tbody>
                    </table>
                </div>
            </div>
            '''
            self.send_html(self.render_layout('Admin Desk - Campus Market Hub', content, user))
            db.close()
            return

        # 9. CREATE LISTING (/products/create)
        elif path == '/products/create':
            if not user:
                self.redirect('/login')
                db.close()
                return

            cats = db.execute("SELECT * FROM categories ORDER BY DisplayOrder").fetchall()
            content = f'''
            <div class="container form-page-container">
                <div class="form-header-bar">
                    <h2><i class="fa-solid fa-plus-circle text-primary"></i> Post a Campus Listing</h2>
                </div>
                <form action="/products/create" method="POST" class="content-card modern-form">
                    <div class="form-grid-2">
                        <div class="form-group full-width">
                            <label>Product Title *</label>
                            <input type="text" name="product_name" class="form-control" placeholder="e.g. Calculus: Early Transcendentals (8th Ed)" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" class="form-control" required>
                                {''.join(f'<option value="{c["CategoryID"]}">{c["CategoryName"]}</option>' for c in cats)}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Listing Type</label>
                            <select name="listing_type" class="form-control">
                                <option value="Sell">For Sale</option>
                                <option value="Exchange">Item Exchange</option>
                                <option value="Donate">Free Donation</option>
                                <option value="Service">Campus Service</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (KSh)</label>
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="1500.00" value="1000.00">
                        </div>
                        <div class="form-group">
                            <label>Pickup Location</label>
                            <input type="text" name="pickup_location" class="form-control" value="Main Library Gate B">
                        </div>
                        <div class="form-group full-width">
                            <label>Description</label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Item condition, notes, accessories..."></textarea>
                        </div>
                    </div>
                    <div class="form-actions-bar">
                        <button type="submit" class="btn-submit-post">Publish Listing</button>
                    </div>
                </form>
            </div>
            '''
            self.send_html(self.render_layout('Post an Item - Campus Market Hub', content, user))
            db.close()
            return

        # 10. API: Messages poll
        elif path == '/api/messages/poll':
            last_id = int(params.get('last_id', [0])[0])
            other_id = int(params.get('other_user_id', [0])[0])
            if not user or not other_id:
                self.send_json({'success': False, 'messages': []})
                db.close()
                return

            new_msgs = db.execute('''
            SELECT m.*, u.FullName as SenderName
            FROM messages m
            JOIN users u ON m.SenderID = u.UserID
            WHERE m.ReceiverID = ? AND m.SenderID = ? AND m.MessageID > ?
            ORDER BY m.MessageID ASC
            ''', (user['UserID'], other_id, last_id)).fetchall()

            res = [{'MessageID': m['MessageID'], 'SenderID': m['SenderID'], 'MessageText': m['MessageText'], 'FormattedTime': m['DateSent'][11:16], 'IsMine': False} for m in new_msgs]
            self.send_json({'success': True, 'messages': res})
            db.close()
            return

        # 11. API: Unread count
        elif path == '/api/messages/unread-count':
            count = 0
            if user:
                row = db.execute("SELECT COUNT(*) FROM messages WHERE ReceiverID = ? AND IsRead = 0", (user['UserID'],)).fetchone()
                count = row[0] if row else 0
            self.send_json({'unread': count})
            db.close()
            return

        # 404
        self.send_html(self.render_layout('Page Not Found', '<div class="container" style="text-align:center;padding:4rem;"><h2>404 - Page Not Found</h2><p><a href="/">Return Home</a></p></div>', user), status=404)
        db.close()

    def do_POST(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(length).decode('utf-8')
        form = urllib.parse.parse_qs(post_data)
        user = self.get_current_user()
        db = self.get_db()

        if path == '/login':
            identifier = form.get('identifier', [''])[0].strip().lower()
            password = form.get('password', [''])[0].strip()

            u = db.execute("SELECT * FROM users WHERE LOWER(Email) = ? OR LOWER(Registration_StaffNo) = ?", (identifier, identifier)).fetchone()
            if u:
                self.redirect('/', set_cookie=f'campus_user={u["UserID"]}; Path=/; HttpOnly')
            else:
                self.redirect('/login')
            db.close()
            return

        elif path == '/products/create':
            if not user:
                self.redirect('/login')
                db.close()
                return

            p_name = form.get('product_name', ['Untitled'])[0]
            c_id = int(form.get('category_id', [1])[0])
            p_type = form.get('listing_type', ['Sell'])[0]
            price = float(form.get('price', [0.0])[0] or 0.0)
            pickup = form.get('pickup_location', ['Main Campus Student Center'])[0]
            desc = form.get('description', [''])[0]

            db.execute('''
            INSERT INTO products (UserID, CategoryID, ProductName, Description, Price, ListingType, ProductImage, ConditionStatus, PickupLocation, Status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ''', (user['UserID'], c_id, p_name, desc, price, p_type, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&auto=format&fit=crop&q=80', 'Good', pickup, 'Available'))
            db.commit()
            pid = db.execute("SELECT last_insert_rowid()").fetchone()[0]
            self.redirect(f'/products/{pid}')
            db.close()
            return

        elif path == '/transactions/initiate':
            if not user:
                self.redirect('/login')
                db.close()
                return

            pid = int(form.get('product_id', [0])[0])
            ttype = form.get('transaction_type', ['Purchase'])[0]
            loc = form.get('meeting_location', ['Campus Library'])[0]
            notes = form.get('notes', [''])[0]

            prod = db.execute("SELECT * FROM products WHERE ProductID = ?", (pid,)).fetchone()
            if prod:
                db.execute('''
                INSERT INTO transactions (ProductID, BuyerID, SellerID, TransactionType, Amount, MeetingLocation, Notes, TransactionStatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ''', (pid, user['UserID'], prod['UserID'], ttype, prod['Price'], loc, notes, 'Pending'))
                db.commit()
            self.redirect('/transactions')
            db.close()
            return

        elif path == '/api/messages/send':
            if not user:
                self.send_json({'success': False, 'error': 'Unauthorized'}, 401)
                db.close()
                return

            receiver_id = int(form.get('receiver_id', [0])[0])
            product_id = int(form.get('product_id', [0])[0]) or None
            text = form.get('message_text', [''])[0].strip()

            db.execute('''
            INSERT INTO messages (SenderID, ReceiverID, ProductID, MessageText, IsRead)
            VALUES (?, ?, ?, ?, 0)
            ''', (user['UserID'], receiver_id, product_id, text))
            db.commit()
            mid = db.execute("SELECT last_insert_rowid()").fetchone()[0]

            self.send_json({
                'success': True,
                'message_id': mid,
                'sender_id': user['UserID'],
                'receiver_id': receiver_id,
                'text': text
            })
            db.close()
            return

        self.redirect('/')
        db.close()

if __name__ == '__main__':
    server = HTTPServer(('127.0.0.1', PORT), CampusMarketHandler)
    print(f"🚀 Campus Market Hub Preview Server running at http://localhost:{PORT}")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
