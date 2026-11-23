-- =====================================================================
-- CAMPUS MARKET HUB - STANDALONE SEED DATA SCRIPT
-- Imports sample users, categories, products, messages, transactions & logs
-- =====================================================================

USE `campus_market_hub`;

-- Password for all seed users is: Password@123
INSERT IGNORE INTO `categories` (`CategoryID`, `CategoryName`, `CategorySlug`, `Icon`, `Description`, `DisplayOrder`) VALUES
(1, 'Textbooks', 'textbooks', 'book-open', 'Course textbooks, reference handbooks, and revision manuals', 1),
(2, 'Electronics', 'electronics', 'laptop', 'Laptops, scientific calculators, headphones, chargers, adapters', 2),
(3, 'Furniture', 'furniture', 'couch', 'Hostel desks, ergonomic chairs, shoe racks, mattress toppers', 3),
(4, 'Clothing', 'clothing', 'shirt', 'Campus hoodies, sports jerseys, official presentation wear, lab coats', 4),
(5, 'Learning Materials', 'learning-materials', 'clipboard-list', 'Engineering drafting kits, medical stethoscopes, lab dissecting sets', 5),
(6, 'Services', 'services', 'briefcase', 'Peer tutoring, thesis formatting, graphic design, bike repairs', 6),
(7, 'Free/Donations', 'free-donations', 'heart-handshake', '100% free items donated to support junior or needy peers', 7);
