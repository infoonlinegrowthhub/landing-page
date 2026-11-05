-- Dynamic E-Commerce Promotional Landing Page & Admin Schema

-- 1. Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL DEFAULT 'Administrator',
    `role` VARCHAR(50) NOT NULL DEFAULT 'Super Admin',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Site Settings Table (Key-Value store for easy extensibility)
CREATE TABLE IF NOT EXISTS `site_settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `icon` VARCHAR(80) DEFAULT 'fas fa-tag',
    `description` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Products Table (External Buy Link / Payment Link Powered)
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `name` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(200) NOT NULL UNIQUE,
    `subtitle` VARCHAR(255) DEFAULT NULL,
    `short_description` TEXT DEFAULT NULL,
    `full_description` LONGTEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `compare_at_price` DECIMAL(10,2) DEFAULT NULL,
    `discount_badge` VARCHAR(60) DEFAULT NULL,
    `main_image` VARCHAR(500) NOT NULL,
    `gallery_images` TEXT DEFAULT NULL COMMENT 'JSON array of image URLs',
    `features_json` TEXT DEFAULT NULL COMMENT 'JSON array of key bullet points/specs',
    `external_buy_url` VARCHAR(1000) NOT NULL COMMENT 'External checkout, affiliate, or payment link',
    `buy_button_text` VARCHAR(80) NOT NULL DEFAULT 'Buy Now',
    `platform_label` VARCHAR(80) DEFAULT 'Official Store' COMMENT 'e.g. Stripe Link, Razorpay, Amazon, Official Checkout',
    `open_in_new_tab` TINYINT(1) NOT NULL DEFAULT 1,
    `rating` DECIMAL(2,1) NOT NULL DEFAULT 4.9,
    `reviews_count` INT NOT NULL DEFAULT 120,
    `stock_status` ENUM('In Stock', 'Limited Stock', 'Pre-Order', 'Sold Out') DEFAULT 'In Stock',
    `is_featured` TINYINT(1) NOT NULL DEFAULT 1,
    `is_spotlight` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flagship spotlight product on landing page',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `clicks_count` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Hero Banners Table
CREATE TABLE IF NOT EXISTS `hero_banners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `badge_text` VARCHAR(100) DEFAULT 'LIMITED TIME OFFER',
    `title` VARCHAR(200) NOT NULL,
    `highlight_text` VARCHAR(150) DEFAULT NULL,
    `subtitle` TEXT DEFAULT NULL,
    `price_tag` VARCHAR(50) DEFAULT NULL,
    `compare_price_tag` VARCHAR(50) DEFAULT NULL,
    `cta_text` VARCHAR(80) NOT NULL DEFAULT 'Buy Now — Instant Access',
    `cta_external_url` VARCHAR(1000) NOT NULL,
    `secondary_cta_text` VARCHAR(80) DEFAULT 'Explore Products',
    `secondary_cta_url` VARCHAR(500) DEFAULT '#products',
    `hero_image` VARCHAR(500) NOT NULL,
    `trust_text` VARCHAR(255) DEFAULT '10,000+ Happy Customers • 30-Day Money-Back Guarantee',
    `product_id` INT DEFAULT NULL COMMENT 'Optional linked product for click tracking',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Features / Benefits Table
CREATE TABLE IF NOT EXISTS `features` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `icon` VARCHAR(80) NOT NULL DEFAULT 'fas fa-shield-alt',
    `title` VARCHAR(120) NOT NULL,
    `description` TEXT NOT NULL,
    `badge` VARCHAR(50) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Testimonials Table
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_role` VARCHAR(100) DEFAULT 'Verified Buyer',
    `customer_avatar` VARCHAR(500) DEFAULT NULL,
    `rating` TINYINT NOT NULL DEFAULT 5,
    `review_title` VARCHAR(150) DEFAULT NULL,
    `review_text` TEXT NOT NULL,
    `product_name` VARCHAR(150) DEFAULT NULL,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. FAQs Table
CREATE TABLE IF NOT EXISTS `faqs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `question` VARCHAR(300) NOT NULL,
    `answer` TEXT NOT NULL,
    `category` VARCHAR(80) DEFAULT 'General',
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Click Analytics Logs Table
CREATE TABLE IF NOT EXISTS `click_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT DEFAULT NULL,
    `target_url` VARCHAR(1000) NOT NULL,
    `placement` VARCHAR(50) NOT NULL DEFAULT 'card' COMMENT 'hero, spotlight, card, modal, sticky_bar',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `referrer` VARCHAR(500) DEFAULT NULL,
    `clicked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_clicked_at` (`clicked_at`),
    INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Customer Inquiries / Pre-Sale Leads Table
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(40) DEFAULT NULL,
    `product_interest` VARCHAR(180) DEFAULT NULL,
    `subject` VARCHAR(200) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('Unread', 'Read', 'Replied') DEFAULT 'Unread',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
