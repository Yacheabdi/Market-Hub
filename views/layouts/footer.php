<?php
/**
 * Campus Market Hub - Footer Layout
 */
?>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container footer-content">
            <div class="footer-col brand-col">
                <div class="footer-brand">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>Campus Market Hub</span>
                </div>
                <p class="footer-desc">
                    A secure, student-first digital circular economy enabling students, lecturers, and university staff to safely buy, sell, exchange, and donate academic essentials.
                </p>
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
                    <li><a href="/?type=Service"><i class="fa-solid fa-chevron-right"></i> Peer Tutoring & Services</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Top Categories</h4>
                <ul class="footer-links">
                    <li><a href="/?category=1"><i class="fa-solid fa-book"></i> Course Textbooks</a></li>
                    <li><a href="/?category=2"><i class="fa-solid fa-laptop"></i> Laptops & Electronics</a></li>
                    <li><a href="/?category=3"><i class="fa-solid fa-couch"></i> Hostel Furniture</a></li>
                    <li><a href="/?category=4"><i class="fa-solid fa-shirt"></i> Campus Apparel</a></li>
                    <li><a href="/?category=5"><i class="fa-solid fa-flask"></i> Lab & Engineering Kits</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Safety & Campus Code</h4>
                <ul class="footer-links">
                    <li><a href="#safety-modal" onclick="alert('Campus Safety Tip: Always meet during daylight hours at designated campus safe spots like the University Library or Student Center.'); return false;"><i class="fa-solid fa-handshake"></i> Safe Meetup Guidelines</a></li>
                    <li><a href="#scam-modal" onclick="alert('Fraud Warning: Never send deposits via mobile money before seeing the item in person.'); return false;"><i class="fa-solid fa-triangle-exclamation"></i> Anti-Scam Policy</a></li>
                    <li><a href="/admin"><i class="fa-solid fa-user-shield"></i> Campus Admin Desk</a></li>
                </ul>
                <div class="demo-auth-help">
                    <small><strong>Demo Login Credentials:</strong><br>
                    Student: <code>lisa.doe@student.campus.edu</code> (Password@123)<br>
                    Staff: <code>dr.sarah@campus.edu</code> (Password@123)<br>
                    Admin: <code>admin@campus.edu</code> (Password@123)</small>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container footer-bottom-flex">
                <p>&copy; <?= date('Y') ?> Campus Market Hub. Built for university academic and student communities.</p>
                <div class="system-status">
                    <span class="status-indicator online"></span>
                    <span>ML Recommendation Engine: Online (PHP Native)</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- App JavaScript -->
    <script src="/js/app.js"></script>
    <script src="/js/chat.js"></script>
</body>
</html>
