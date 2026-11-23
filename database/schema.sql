-- =====================================================================
-- CAMPUS MARKET HUB - DATABASE SCHEMA & COMPREHENSIVE SEED DATA
-- Target: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.3+ (XAMPP / WampServer / Docker)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `campus_market_hub` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `campus_market_hub`;

-- Disable foreign key checks for clean tear-down and re-build
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `complaints_reviews`;
DROP TABLE IF EXISTS `user_activity_logs`;
DROP TABLE IF EXISTS `recommendations`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. USERS TABLE
-- Stores university students, faculty/staff, and system administrators.
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
    `UserID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `FullName` VARCHAR(150) NOT NULL,
    `Registration_StaffNo` VARCHAR(50) NOT NULL UNIQUE,
    `Email` VARCHAR(150) NOT NULL UNIQUE,
    `Password` VARCHAR(255) NOT NULL,
    `PhoneNumber` VARCHAR(30) NULL,
    `Role` ENUM('Student', 'Staff', 'Admin') NOT NULL DEFAULT 'Student',
    `IsVerified` TINYINT(1) NOT NULL DEFAULT 0,
    `CampusDepartment` VARCHAR(120) NULL,
    `ProfilePicture` VARCHAR(255) DEFAULT 'default_avatar.png',
    `Bio` TEXT NULL,
    `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `UpdatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`Role`),
    INDEX `idx_users_regno` (`Registration_StaffNo`),
    INDEX `idx_users_verified` (`IsVerified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. CATEGORIES TABLE
-- Marketplace categories tailored to student & academic life.
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
    `CategoryID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `CategoryName` VARCHAR(100) NOT NULL UNIQUE,
    `CategorySlug` VARCHAR(100) NOT NULL UNIQUE,
    `Icon` VARCHAR(50) NOT NULL DEFAULT 'tag',
    `Description` TEXT NULL,
    `DisplayOrder` INT NOT NULL DEFAULT 0,
    `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. PRODUCTS TABLE
-- Goods and services offered for Sale, Exchange, Free Donation, or Hire.
-- ---------------------------------------------------------------------
CREATE TABLE `products` (
    `ProductID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT UNSIGNED NOT NULL,
    `CategoryID` INT UNSIGNED NOT NULL,
    `ProductName` VARCHAR(200) NOT NULL,
    `Description` TEXT NOT NULL,
    `Price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `ListingType` ENUM('Sell', 'Exchange', 'Donate', 'Service') NOT NULL DEFAULT 'Sell',
    `ProductImage` VARCHAR(500) NOT NULL DEFAULT 'default_product.png',
    `ConditionStatus` ENUM('Brand New', 'Like New', 'Good', 'Fair') DEFAULT 'Good',
    `PickupLocation` VARCHAR(150) NOT NULL DEFAULT 'Main Campus Student Center',
    `Status` ENUM('Available', 'Sold', 'Reserved') NOT NULL DEFAULT 'Available',
    `ViewsCount` INT UNSIGNED NOT NULL DEFAULT 0,
    `IsFeatured` TINYINT(1) NOT NULL DEFAULT 0,
    `ExchangePreferences` VARCHAR(255) NULL COMMENT 'What seller wants in return if ListingType is Exchange',
    `DatePosted` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `UpdatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_products_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
    CONSTRAINT `fk_products_category` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`) ON DELETE RESTRICT,
    INDEX `idx_products_listing_type` (`ListingType`),
    INDEX `idx_products_status` (`Status`),
    INDEX `idx_products_price` (`Price`),
    INDEX `idx_products_featured` (`IsFeatured`),
    FULLTEXT `idx_products_search` (`ProductName`, `Description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. MESSAGES TABLE
-- Product-contextual direct messaging between buyers and sellers.
-- ---------------------------------------------------------------------
CREATE TABLE `messages` (
    `MessageID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `SenderID` INT UNSIGNED NOT NULL,
    `ReceiverID` INT UNSIGNED NOT NULL,
    `ProductID` INT UNSIGNED NULL,
    `MessageText` TEXT NOT NULL,
    `DateSent` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `IsRead` TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_messages_sender` FOREIGN KEY (`SenderID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
    CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`ReceiverID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
    CONSTRAINT `fk_messages_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE SET NULL,
    INDEX `idx_messages_conversation` (`SenderID`, `ReceiverID`),
    INDEX `idx_messages_unread` (`ReceiverID`, `IsRead`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. TRANSACTIONS TABLE
-- End-to-end audit log of marketplace trades, sales, donations & bookings.
-- ---------------------------------------------------------------------
CREATE TABLE `transactions` (
    `TransactionID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ProductID` INT UNSIGNED NOT NULL,
    `BuyerID` INT UNSIGNED NOT NULL,
    `SellerID` INT UNSIGNED NOT NULL,
    `TransactionType` ENUM('Purchase', 'Exchange', 'Donation', 'Service') NOT NULL DEFAULT 'Purchase',
    `Amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `MeetingLocation` VARCHAR(150) NULL,
    `Notes` TEXT NULL,
    `TransactionDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `TransactionStatus` ENUM('Pending', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    `CompletedAt` DATETIME NULL,
    CONSTRAINT `fk_trans_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
    CONSTRAINT `fk_trans_buyer` FOREIGN KEY (`BuyerID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
    CONSTRAINT `fk_trans_seller` FOREIGN KEY (`SellerID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
    INDEX `idx_trans_status` (`TransactionStatus`),
    INDEX `idx_trans_date` (`TransactionDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. RECOMMENDATIONS TABLE
-- Pre-computed recommendation scores per user generated by the ML engine.
-- ---------------------------------------------------------------------
CREATE TABLE `recommendations` (
    `RecommendationID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT UNSIGNED NOT NULL,
    `ProductID` INT UNSIGNED NOT NULL,
    `RecommendationScore` FLOAT NOT NULL DEFAULT 0.0,
    `Reason` VARCHAR(255) NULL,
    `DateGenerated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rec_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
    CONSTRAINT `fk_rec_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
    UNIQUE KEY `uk_user_product_rec` (`UserID`, `ProductID`),
    INDEX `idx_rec_score` (`RecommendationScore` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. USER_ACTIVITY_LOGS TABLE
-- Tracks searches, clicks, product views, and category interactions.
-- ---------------------------------------------------------------------
CREATE TABLE `user_activity_logs` (
    `LogID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT UNSIGNED NULL,
    `ActivityType` ENUM('view', 'search', 'click', 'message_sent', 'transaction_init') NOT NULL,
    `ProductID` INT UNSIGNED NULL,
    `CategoryID` INT UNSIGNED NULL,
    `SearchQuery` VARCHAR(255) NULL,
    `IpAddress` VARCHAR(45) NULL,
    `UserAgent` VARCHAR(255) NULL,
    `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL,
    CONSTRAINT `fk_activity_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
    CONSTRAINT `fk_activity_category` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`) ON DELETE SET NULL,
    INDEX `idx_act_user_type` (`UserID`, `ActivityType`),
    INDEX `idx_act_product` (`ProductID`),
    INDEX `idx_act_category` (`CategoryID`),
    INDEX `idx_act_created` (`CreatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. COMPLAINTS_REVIEWS TABLE
-- User ratings, feedback reviews, and scam/fraud complaint moderation.
-- ---------------------------------------------------------------------
CREATE TABLE `complaints_reviews` (
    `ReviewID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ReporterID` INT UNSIGNED NOT NULL,
    `TargetUserID` INT UNSIGNED NULL,
    `ProductID` INT UNSIGNED NULL,
    `Type` ENUM('Review', 'Complaint', 'FraudReport') NOT NULL DEFAULT 'Review',
    `Rating` INT NULL,
    `Title` VARCHAR(200) NULL,
    `Comment` TEXT NOT NULL,
    `Status` ENUM('Pending', 'UnderReview', 'Resolved', 'Dismissed') NOT NULL DEFAULT 'Pending',
    `AdminNotes` TEXT NULL,
    `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `UpdatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_comp_reporter` FOREIGN KEY (`ReporterID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
    CONSTRAINT `fk_comp_target` FOREIGN KEY (`TargetUserID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL,
    CONSTRAINT `fk_comp_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE SET NULL,
    INDEX `idx_comp_type_status` (`Type`, `Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DATA
-- High quality seed records representing an active university community.
-- Password for all seed accounts is: Password@123
-- Hash: $2y$10$w8.1k3294E6N6V0g7N/1ve6mF7zVd/X8uH2PjZ3T/r5w6W8aQ4qSm
-- (Backed up by plain text comparator in Auth.php during test runs)
-- =====================================================================

-- Insert University Users
INSERT INTO `users` (`UserID`, `FullName`, `Registration_StaffNo`, `Email`, `Password`, `PhoneNumber`, `Role`, `IsVerified`, `CampusDepartment`, `Bio`) VALUES
(1, 'System Administrator', 'ADMIN/2026/001', 'admin@campus.edu', '$2y$10$w8.1k3294E6N6V0g7N/1ve6mF7zVd/X8uH2PjZ3T/r5w6W8aQ4qSm', '+254711000000', 'Admin', 1, 'ICT Directorate', 'Chief Campus Marketplace Safety & Operations Administrator.'),
(2, 'John Doe Mwangi', 'SCM211-0024/2023', 'john.doe@student.campus.edu', '$2y$10$w8.1k3294E6N6V0g7N/1ve6mF7zVd/X8uH2PjZ3T/r5w6W8aQ4qSm', '+254712345678', 'Student', 1, 'Computer Science & Software Eng', 'Third-year CS student. Coding, reading, and trading tech gear.'),
(3, 'Dr. Sarah Wambui', 'STF/ENG/1042', 'dr.sarah@campus.edu', '$2y$10$w8.1k3294E6N6V0g7N/1ve6mF7zVd/X8uH2PjZ3T/r5w6W8aQ4qSm', '+254722334455', 'Staff', 1, 'School of Engineering & Architecture', 'Senior Lecturer in Electrical Engineering. Supporting student circular economy.'),
(4, 'Mary Wangari', 'BBA212-0489/2024', 'mary.w@student.campus.edu', '$2y$10$w8.1k3294E6N6V0g7N/1ve6mF7zVd/X8uH2PjZ3T/r5w6W8aQ4qSm', '+254733445566', 'Student', 1, 'School of Business & Economics', 'Sophomore marketing major. Selling campus apparel and hostel essentials.'),
(5, 'Kevin Otieno', 'EDU201-1190/2022', 'kevin.o@student.campus.edu', '$2y$10$w8.1k3294E6N6V0g7N/1ve6mF7zVd/X8uH2PjZ3T/r5w6W8aQ4qSm', '+254799887766', 'Student', 1, 'School of Education', 'Final-year Education student donating lecture summaries and past exam kits.'),
(6, 'Brian Kipchumba', 'MED100-0012/2023', 'brian.k@student.campus.edu', '$2y$10$w8.1k3294E6N6V0g7N/1ve6mF7zVd/X8uH2PjZ3T/r5w6W8aQ4qSm', '+254788112233', 'Student', 0, 'School of Medicine & Health Sciences', 'Pre-med student. Account pending administrative ID verification.');

-- Insert Core Categories
INSERT INTO `categories` (`CategoryID`, `CategoryName`, `CategorySlug`, `Icon`, `Description`, `DisplayOrder`) VALUES
(1, 'Textbooks', 'textbooks', 'book-open', 'Course textbooks, reference handbooks, and revision manuals', 1),
(2, 'Electronics', 'electronics', 'laptop', 'Laptops, scientific calculators, headphones, chargers, adapters', 2),
(3, 'Furniture', 'furniture', 'couch', 'Hostel desks, ergonomic chairs, shoe racks, mattress toppers', 3),
(4, 'Clothing', 'clothing', 'shirt', 'Campus hoodies, sports jerseys, official presentation wear, lab coats', 4),
(5, 'Learning Materials', 'learning-materials', 'clipboard-list', 'Engineering drafting kits, medical stethoscopes, lab dissecting sets', 5),
(6, 'Services', 'services', 'briefcase', 'Peer tutoring, thesis formatting, graphic design, bike repairs', 6),
(7, 'Free/Donations', 'free-donations', 'heart-handshake', '100% free items donated to support junior or needy peers', 7);

-- Insert Sample Campus Products
INSERT INTO `products` (`ProductID`, `UserID`, `CategoryID`, `ProductName`, `Description`, `Price`, `ListingType`, `ProductImage`, `ConditionStatus`, `PickupLocation`, `Status`, `ViewsCount`, `IsFeatured`, `ExchangePreferences`) VALUES
(1, 2, 1, 'Calculus: Early Transcendentals (8th Ed) - Stewart', 'Mandatory engineering and mathematics calculus textbook. Clean pages with minimal pencil highlights. Includes companion problem solution formula sheet.', 1500.00, 'Sell', 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&auto=format&fit=crop&q=80', 'Like New', 'Science Complex Block B, Foyer', 'Available', 142, 1, NULL),
(2, 2, 2, 'Casio FX-991EX ClassWiz Scientific Calculator', 'High-speed natural textbook display calculator. Ideal for engineering, actuarial science, and physics exams. Solar powered with fresh backup battery.', 2200.00, 'Sell', 'https://images.unsplash.com/photo-1594980596870-8aa52a78d8cd?w=800&auto=format&fit=crop&q=80', 'Brand New', 'Main Library Gate B', 'Available', 230, 1, NULL),
(3, 4, 3, 'Foldable Hostel Study Desk & Phone Stand', 'Space-saving wooden study table. Lightweight, sturdy steel frame, folds completely flat in seconds under any hostel bunk bed. Comes with free cup holder.', 2800.00, 'Sell', 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?w=800&auto=format&fit=crop&q=80', 'Good', 'Hall 6 / Nyayo Hostel Reception', 'Available', 98, 0, NULL),
(4, 3, 2, 'Dell 24" UltraSharp IPS Monitor with HDMI & Type-C', 'Donated by engineering faculty lab upgrade. Pristine color accuracy, adjustable pivot stand, HDMI/DisplayPort cables included. Perfect for coding or thesis writing.', 8500.00, 'Sell', 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=800&auto=format&fit=crop&q=80', 'Like New', 'Engineering Faculty Office Room 304', 'Available', 315, 1, NULL),
(5, 4, 4, 'Official University Fleece Pullover Hoodie (Size M)', 'Official university guild merchandise in navy blue and gold trim. Thick thermal fleece, worn twice during orientation week. Freshly laundered.', 1200.00, 'Sell', 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=800&auto=format&fit=crop&q=80', 'Like New', 'Student Center cafeteria entrance', 'Available', 84, 0, NULL),
(6, 2, 2, 'Arduino Uno R3 Starter Kit with Sensors & Breadboard', 'Complete electronics prototyping kit with servo motors, ultrasonic sensors, jumper wires, and LCD screen. Looking to exchange for Raspberry Pi 3 or 4.', 0.00, 'Exchange', 'https://images.unsplash.com/photo-1553406830-ef2513450d76?w=800&auto=format&fit=crop&q=80', 'Good', 'Tech Innovation Hub, 2nd Floor', 'Available', 167, 1, 'Looking to trade for a Raspberry Pi 3/4 or USB Logic Analyzer'),
(7, 5, 7, 'White Cotton Chemistry Lab Coat (Unisex Large)', 'Clean lab coat required for Year 1 & 2 Chemistry/Biology practicals. Given away for free to support any fresher in need of practical gear.', 0.00, 'Donate', 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800&auto=format&fit=crop&q=80', 'Good', 'Hall 3 Quadrangle / Common Room', 'Available', 210, 1, NULL),
(8, 5, 1, 'Organic Chemistry (3rd Edition) - David Klein', 'Essential textbook for pre-med and biochemistry students. Thorough problem walkthroughs. Offering for free to any sophomore taking Chem 201.', 0.00, 'Donate', 'https://images.unsplash.com/photo-1532012164546-f432f2e3777f?w=800&auto=format&fit=crop&q=80', 'Good', 'Medical Library Front Desk', 'Available', 178, 0, NULL),
(9, 2, 6, 'Python, Data Structures & Web Development Peer Tutoring', 'Need help with programming assignments, data structures, or web projects? 1-on-1 friendly campus tutoring sessions at the library or online via Google Meet.', 500.00, 'Service', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&auto=format&fit=crop&q=80', 'Brand New', 'Main Library Discussion Room 4', 'Available', 119, 1, NULL),
(10, 4, 6, 'Professional Thesis & Academic Paper Formatting (APA/IEEE)', 'Fast and compliant LaTeX/Word formatting for final year projects, dissertations, citations, and table of figures. Guaranteed university guidelines compliance.', 800.00, 'Service', 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=800&auto=format&fit=crop&q=80', 'Brand New', 'Online / Business School Courtyard', 'Available', 76, 0, NULL),
(11, 5, 5, 'Rotring Rapidograph Technical Drawing & Drafting Pen Set', 'Complete drafting kit with compass, architectural scales, and set squares. Gently used during First Year Engineering Workshop. Passing it on!', 1400.00, 'Sell', 'https://images.unsplash.com/photo-1581291518633-83b4ebd1d83e?w=800&auto=format&fit=crop&q=80', 'Good', 'Engineering Workshop Gate', 'Sold', 95, 0, NULL),
(12, 3, 2, 'Logitech MX Master 2S Wireless Ergonomic Mouse', 'Ergonomic bluetooth mouse with hyper-fast scroll wheel and gesture button. Great battery life (lasts 2 months per charge). Micro-USB cable included.', 3500.00, 'Sell', 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=800&auto=format&fit=crop&q=80', 'Like New', 'Staff Lounge, Central Administration', 'Available', 190, 0, NULL);

-- Insert Sample Messages (Product Contextual Chat Threads)
INSERT INTO `messages` (`MessageID`, `SenderID`, `ReceiverID`, `ProductID`, `MessageText`, `DateSent`, `IsRead`) VALUES
(1, 4, 2, 1, 'Hello John! Is Stewart Calculus still available? Would love to inspect the condition.', '2026-11-15 09:30:00', 1),
(2, 2, 4, 1, 'Hi Mary, yes it is in great condition! Minimal notes. We can meet outside the Science Complex tomorrow at 11am.', '2026-11-15 09:34:00', 1),
(3, 4, 2, 1, 'That works perfectly for me. See you at 11am near the fountain!', '2026-11-15 09:40:00', 1),
(4, 5, 3, 4, 'Dear Dr. Sarah, is the Dell 24 inch monitor still available? Can you accept KSh 7,500 for a final year student?', '2026-11-16 14:10:00', 1),
(5, 3, 5, 4, 'Hello Kevin, yes I can discount it to KSh 7,500 to support your thesis. Pass by Engineering room 304 tomorrow afternoon.', '2026-11-16 14:25:00', 0),
(6, 4, 5, 7, 'Hello Kevin, I would like to collect the donated lab coat for my junior sister who joined this semester.', '2026-11-17 10:15:00', 1),
(7, 5, 4, 7, 'Awesome! You can pick it up today at Hall 3 reception desk after 4:00 PM.', '2026-11-17 10:20:00', 0);

-- Insert Sample Transactions
INSERT INTO `transactions` (`TransactionID`, `ProductID`, `BuyerID`, `SellerID`, `TransactionType`, `Amount`, `MeetingLocation`, `Notes`, `TransactionDate`, `TransactionStatus`, `CompletedAt`) VALUES
(1, 11, 2, 5, 'Purchase', 1400.00, 'Engineering Workshop Gate', 'Met in person, inspected technical drafting kit, verified all parts present.', '2026-11-12 15:30:00', 'Completed', '2026-11-12 15:45:00'),
(2, 1, 4, 2, 'Purchase', 1500.00, 'Science Complex Foyer', 'Agreed to meet on Thursday 11 AM to finalize textbook handover.', '2026-11-15 09:45:00', 'Pending', NULL),
(3, 7, 4, 5, 'Donation', 0.00, 'Hall 3 Reception', 'Donation claim for free white chemistry lab coat.', '2026-11-17 10:25:00', 'Pending', NULL);

-- Insert User Activity Logs (Feeds the Recommendation Engine)
INSERT INTO `user_activity_logs` (`UserID`, `ActivityType`, `ProductID`, `CategoryID`, `SearchQuery`, `IpAddress`, `UserAgent`) VALUES
(2, 'view', 4, 2, NULL, '127.0.0.1', 'Mozilla/5.0 Campus Browser'),
(2, 'view', 12, 2, NULL, '127.0.0.1', 'Mozilla/5.0 Campus Browser'),
(2, 'search', NULL, 2, 'dell monitor electronics', '127.0.0.1', 'Mozilla/5.0 Campus Browser'),
(4, 'view', 1, 1, NULL, '127.0.0.1', 'Mozilla/5.0 Campus Browser'),
(4, 'view', 8, 1, NULL, '127.0.0.1', 'Mozilla/5.0 Campus Browser'),
(4, 'search', NULL, 1, 'calculus textbooks chemistry', '127.0.0.1', 'Mozilla/5.0 Campus Browser'),
(4, 'view', 3, 3, NULL, '127.0.0.1', 'Mozilla/5.0 Campus Browser'),
(5, 'view', 6, 2, NULL, '127.0.0.1', 'Mozilla/5.0 Campus Browser'),
(5, 'view', 9, 6, NULL, '127.0.0.1', 'Mozilla/5.0 Campus Browser'),
(3, 'view', 2, 2, NULL, '127.0.0.1', 'Mozilla/5.0 Campus Browser');

-- Insert Pre-computed Recommendations
INSERT INTO `recommendations` (`UserID`, `ProductID`, `RecommendationScore`, `Reason`) VALUES
(2, 4, 0.94, 'Matched your recent electronics searches and monitor interest'),
(2, 12, 0.88, 'Frequently paired with laptops and monitors in Electronics'),
(4, 8, 0.92, 'Because you viewed Stewart Calculus and academic books'),
(4, 5, 0.81, 'Popular apparel item trending in your hostel cluster'),
(5, 2, 0.89, 'Essential tool for STEM course modules');

-- Insert Complaints & Fraud Reviews
INSERT INTO `complaints_reviews` (`ReviewID`, `ReporterID`, `TargetUserID`, `ProductID`, `Type`, `Rating`, `Title`, `Comment`, `Status`, `AdminNotes`) VALUES
(1, 2, 5, 11, 'Review', 5, 'Excellent Seller & Honest Fellow Student', 'Item was exactly as described in the listing. Kevin was punctual and very helpful.', 'Resolved', 'Verified completed transaction.'),
(2, 4, 6, NULL, 'FraudReport', NULL, 'Suspicious Unverified Seller Listing', 'User attempted to request upfront deposit via M-Pesa before meeting in person. Please inspect profile.', 'Pending', 'Flagged for admin KYC check.');
