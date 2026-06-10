-- Phelyz Diamond Store - Complete Database Schema
-- Drop tables if exist
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS addresses;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS email_verifications;
DROP TABLE IF EXISTS password_resets;
-- ================================
-- TABLE 1: USERS (Customers & Admin)
-- ================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Nigeria',
    role ENUM('customer', 'admin') DEFAULT 'customer',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================
-- TABLE 2: CATEGORIES
-- ================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT DEFAULT NULL,
    image VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================
-- TABLE 3: PRODUCTS
-- ================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    category_id INT NOT NULL,
    material VARCHAR(100) COMMENT 'Gold, Platinum, Silver, Rose Gold, White Gold, Titanium',
    metal_purity VARCHAR(50) COMMENT '10K, 14K, 18K, 22K, 24K, 950, 925',
    stone_type VARCHAR(100) COMMENT 'Diamond, Ruby, Emerald, Sapphire, Pearl, None',
    stone_weight DECIMAL(10,2) COMMENT 'Carat weight',
    brand VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2) COMMENT 'Original price for discount display',
    stock_quantity INT DEFAULT 0,
    stock_status ENUM('available','express','out_of_stock') NOT NULL DEFAULT 'available',
    sku VARCHAR(100) UNIQUE,
    image VARCHAR(255),
    images TEXT COMMENT 'JSON array of additional images',
    weight DECIMAL(10,2) COMMENT 'Product weight in grams',
    dimensions VARCHAR(100) COMMENT 'Length x Width x Height',
    gender ENUM('Men', 'Women', 'Unisex') DEFAULT 'Unisex',
    style VARCHAR(100) COMMENT 'Classic, Modern, Vintage, Art Deco, Minimalist',
    occasion VARCHAR(100) COMMENT 'Engagement, Wedding, Anniversary, Birthday, Daily Wear',
    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_price (price),
    INDEX idx_material (material),
    INDEX idx_stone_type (stone_type),
    INDEX idx_brand (brand),
    INDEX idx_featured (is_featured),
    INDEX idx_active (is_active),
    FULLTEXT idx_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================
-- TABLE 4: ORDERS
-- ================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0.00,
    shipping DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'cod',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    shipping_first_name VARCHAR(100),
    shipping_last_name VARCHAR(100),
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    shipping_state VARCHAR(100),
    shipping_zip VARCHAR(20),
    shipping_country VARCHAR(100),
    shipping_phone VARCHAR(20),
    billing_first_name VARCHAR(100),
    billing_last_name VARCHAR(100),
    billing_address TEXT,
    billing_city VARCHAR(100),
    billing_state VARCHAR(100),
    billing_zip VARCHAR(20),
    billing_country VARCHAR(100),
    billing_phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_number (order_number),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================
-- TABLE 5: ORDER_ITEMS
-- ================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255),
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================
-- TABLE 6: CART
-- ================================
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================
-- TABLE 7: CART_ITEMS
-- ================================
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_cart (cart_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================
-- TABLE 8: WISHLIST
-- ================================
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================
-- TABLE 9: ADDRESSES
-- ================================
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('shipping', 'billing') NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100),
    phone VARCHAR(20),
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================
-- INSERT DEFAULT DATA
-- ================================

-- Insert Admin User
INSERT INTO users (email, password, first_name, last_name, phone, role) VALUES
('admin@phelyz.com', '$2y$12$LQv3c1yycEn7NZEhWONTseXlLOc5uUGo2P8Gy.Vy9cY8FHu3C3ZVa', 'Admin', 'Phelyz', '+234 800 000 0000', 'admin');

-- Insert Sample Customer
INSERT INTO users (email, password, first_name, last_name, phone, address, city, state, zip_code, role) VALUES
('customer@example.com', '$2y$12$LQv3c1yycEn7NZEhWONTseXlLOc5uUGo2P8Gy.Vy9cY8FHu3C3ZVa', 'John', 'Customer', '+234 800 111 2222', '123 Victoria Island', 'Lagos', 'Lagos', '101001', 'customer');

-- Insert Categories
INSERT INTO categories (name, slug, description, display_order) VALUES
('Rings', 'rings', 'Elegant rings for every occasion', 1),
('Necklaces', 'necklaces', 'Beautiful necklaces and pendants', 2),
('Bracelets', 'bracelets', 'Stunning bracelets and bangles', 3),
('Earrings', 'earrings', 'Exquisite earrings collection', 4),
('Pendants', 'pendants', 'Charming pendants', 5),
('Watches', 'watches', 'Luxury timepieces', 6),
('Bridal Sets', 'bridal-sets', 'Complete wedding sets', 7),
('Mens Jewelry', 'mens-jewelry', 'Jewelry for men', 8);

-- Insert 15 Sample Products

-- RINGS (3 products)
INSERT INTO products (name, slug, description, category_id, material, metal_purity, stone_type, stone_weight, brand, price, compare_price, stock_quantity, sku, image, gender, style, occasion, is_featured, rating, review_count) VALUES
('Eternal Brilliance Diamond Ring', 'eternal-brilliance-diamond-ring', 'A breathtaking 2.5 carat diamond ring set in premium platinum. This timeless piece features exceptional clarity and brilliance, perfect for marking life''s most precious moments.', 1, 'Platinum', '950', 'Diamond', 2.50, 'Phelyz Collection', 5999.00, 6999.00, 8, 'RING-001', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=800', 'Women', 'Classic', 'Engagement', 1, 4.9, 127),

('Vintage Rose Gold Engagement Ring', 'vintage-rose-gold-engagement-ring', 'Romantic 1.8 carat diamond engagement ring crafted in warm rose gold. The vintage-inspired design combines old-world charm with modern elegance.', 1, 'Rose Gold', '14K', 'Diamond', 1.80, 'Phelyz Heritage', 4299.00, 4999.00, 12, 'RING-002', 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800', 'Women', 'Vintage', 'Engagement', 1, 4.8, 94),

('Classic Solitaire Wedding Band', 'classic-solitaire-wedding-band', 'Elegant 1.2 carat solitaire diamond in lustrous white gold. A symbol of eternal love with its pure, minimalist design that never goes out of style.', 1, 'White Gold', '18K', 'Diamond', 1.20, 'Phelyz Eternal', 3499.00, 3999.00, 15, 'RING-003', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=800', 'Women', 'Minimalist', 'Wedding', 1, 5.0, 156);

-- NECKLACES (3 products)
INSERT INTO products (name, slug, description, category_id, material, metal_purity, stone_type, stone_weight, brand, price, compare_price, stock_quantity, sku, image, gender, style, occasion, is_featured, rating, review_count) VALUES
('Royal Crown Diamond Necklace', 'royal-crown-diamond-necklace', 'Magnificent statement necklace featuring 5 carats of brilliant diamonds set in 18K gold. This showstopping piece is designed for royalty and red carpet moments.', 2, 'Gold', '18K', 'Diamond', 5.00, 'Phelyz Royale', 12999.00, 14999.00, 3, 'NECK-001', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=800', 'Women', 'Modern', 'Anniversary', 1, 4.9, 43),

('Pearl Elegance Pendant', 'pearl-elegance-pendant', 'Rare Tahitian pearl suspended from a delicate platinum chain. The deep lustrous pearl creates an aura of sophistication and timeless beauty.', 2, 'Platinum', '950', 'Pearl', 0.00, 'Phelyz Oceanic', 2799.00, 3299.00, 10, 'NECK-002', 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=800', 'Women', 'Classic', 'Daily Wear', 0, 4.7, 68),

('Diamond Halo Necklace', 'diamond-halo-necklace', 'Stunning 3 carat diamond halo necklace in white gold. Multiple diamonds create a brilliant halo effect that captures light from every angle.', 2, 'White Gold', '14K', 'Diamond', 3.00, 'Phelyz Radiance', 6799.00, 7499.00, 7, 'NECK-003', 'https://images.unsplash.com/photo-1506630448388-4e683c67ddb0?w=800', 'Women', 'Modern', 'Anniversary', 1, 4.8, 82);

-- BRACELETS (3 products)
INSERT INTO products (name, slug, description, category_id, material, metal_purity, stone_type, stone_weight, brand, price, compare_price, stock_quantity, sku, image, gender, style, occasion, is_featured, rating, review_count) VALUES
('Infinity Tennis Bracelet', 'infinity-tennis-bracelet', 'Luxurious tennis bracelet with 8 carats of perfectly matched diamonds in platinum. The continuous line of brilliance symbolizes infinite love and elegance.', 3, 'Platinum', '950', 'Diamond', 8.00, 'Phelyz Infinity', 15999.00, 17999.00, 4, 'BRAC-001', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=800', 'Women', 'Classic', 'Anniversary', 1, 5.0, 91),

('Gold Chain Bracelet', 'gold-chain-bracelet', 'Sophisticated 22K gold chain bracelet. The warm golden glow and substantial weight make this piece a treasured heirloom for generations.', 3, 'Gold', '22K', 'None', 0.00, 'Phelyz Heritage', 1899.00, 2199.00, 20, 'BRAC-002', 'https://images.unsplash.com/photo-1573408301185-9146fe634ad0?w=800', 'Unisex', 'Classic', 'Daily Wear', 0, 4.6, 134),

('Ruby & Diamond Bangle', 'ruby-diamond-bangle', 'Exquisite bangle combining 2 carats of vivid rubies with 1 carat of diamonds in 18K gold. The vibrant red rubies create a stunning contrast with brilliant diamonds.', 3, 'Gold', '18K', 'Ruby', 2.00, 'Phelyz Gemstone', 5499.00, 6299.00, 6, 'BRAC-003', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=800', 'Women', 'Art Deco', 'Birthday', 0, 4.9, 57);

-- EARRINGS (3 products)
INSERT INTO products (name, slug, description, category_id, material, metal_purity, stone_type, stone_weight, brand, price, compare_price, stock_quantity, sku, image, gender, style, occasion, is_featured, rating, review_count) VALUES
('Celestial Diamond Studs', 'celestial-diamond-studs', 'Brilliant 2 carat diamond studs (1ct each) set in platinum. These classic studs offer maximum sparkle and versatility for any occasion.', 4, 'Platinum', '950', 'Diamond', 2.00, 'Phelyz Celestial', 8999.00, 9999.00, 9, 'EAR-001', 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=800', 'Women', 'Classic', 'Daily Wear', 1, 5.0, 203),

('Emerald Drop Earrings', 'emerald-drop-earrings', 'Magnificent 3 carat emerald drop earrings in 18K gold. The rich green emeralds dangle elegantly, catching light with every movement.', 4, 'Gold', '18K', 'Emerald', 3.00, 'Phelyz Gemstone', 4599.00, 5299.00, 11, 'EAR-002', 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=800', 'Women', 'Modern', 'Anniversary', 0, 4.8, 76),

('Pearl Hoop Earrings', 'pearl-hoop-earrings', 'Delicate freshwater pearl hoop earrings in 925 sterling silver. Perfect everyday elegance with a touch of natural beauty.', 4, 'Silver', '925', 'Pearl', 0.00, 'Phelyz Oceanic', 899.00, 1099.00, 25, 'EAR-003', 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=800', 'Women', 'Minimalist', 'Daily Wear', 0, 4.7, 189);

-- WATCHES (2 products)
INSERT INTO products (name, slug, description, category_id, material, metal_purity, stone_type, stone_weight, brand, price, compare_price, stock_quantity, sku, image, gender, style, occasion, is_featured, rating, review_count) VALUES
('Diamond Luxury Watch', 'diamond-luxury-watch', 'Exclusive timepiece featuring 1.5 carats of diamonds on stainless steel. Swiss movement meets diamond brilliance in this ultimate luxury statement.', 6, 'Stainless Steel', 'N/A', 'Diamond', 1.50, 'Phelyz Timepieces', 18999.00, 21999.00, 2, 'WATCH-001', 'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?w=800', 'Unisex', 'Modern', 'Daily Wear', 1, 4.9, 34),

('Classic Gold Watch', 'classic-gold-watch', 'Timeless 18K gold watch with precision Swiss movement. A perfect blend of functionality and elegance for the discerning collector.', 6, 'Gold', '18K', 'None', 0.00, 'Phelyz Timepieces', 9499.00, 10999.00, 5, 'WATCH-002', 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?w=800', 'Men', 'Classic', 'Daily Wear', 0, 4.8, 62);

-- PENDANTS (1 product)
INSERT INTO products (name, slug, description, category_id, material, metal_purity, stone_type, stone_weight, brand, price, compare_price, stock_quantity, sku, image, gender, style, occasion, is_featured, rating, review_count) VALUES
('Heart Pendant Necklace', 'heart-pendant-necklace', 'Romantic 0.8 carat diamond heart pendant in rose gold. The perfect symbol of love, beautifully crafted with exceptional attention to detail.', 5, 'Rose Gold', '14K', 'Diamond', 0.80, 'Phelyz Romance', 2199.00, 2599.00, 14, 'PEND-001', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=800', 'Women', 'Minimalist', 'Anniversary', 1, 4.9, 118);

-- Sample Order (for testing)
INSERT INTO orders (user_id, order_number, status, subtotal, tax, shipping, total, payment_method, shipping_first_name, shipping_last_name, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_country, shipping_phone) VALUES
(2, 'ORD-2024-001', 'delivered', 8999.00, 449.95, 0.00, 9448.95, 'cod', 'John', 'Customer', '123 Victoria Island', 'Lagos', 'Lagos', '101001', 'Nigeria', '+234 800 111 2222');

INSERT INTO order_items (order_id, product_id, product_name, quantity, price_at_purchase, subtotal) VALUES
(1, 10, 'Celestial Diamond Studs', 1, 8999.00, 8999.00);

-- Success Message
SELECT 'Database installation completed successfully!' as Message;

-- ==============================================
-- Table 11: EMAIL VERIFICATION FOR REGISTRATION
-- ==============================================
CREATE TABLE email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
-- ===========================
-- Table 12: PASSWORD RESET
-- ===========================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ================================
-- TABLE 13: SHIPPING_RATES
-- ================================
DROP TABLE IF EXISTS shipping_rates;
CREATE TABLE shipping_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state VARCHAR(100) NOT NULL UNIQUE,
    rate DECIMAL(10,2) NOT NULL DEFAULT 4000.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_state (state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO shipping_rates (state, rate) VALUES
('Abia', 4000.00),('Adamawa', 4500.00),('Akwa Ibom', 4000.00),('Anambra', 3500.00),
('Bauchi', 4500.00),('Bayelsa', 4000.00),('Benue', 4000.00),('Borno', 5000.00),
('Cross River', 4000.00),('Delta', 3500.00),('Ebonyi', 4000.00),('Edo', 3500.00),
('Ekiti', 3500.00),('Enugu', 3500.00),('FCT (Abuja)', 3000.00),('Gombe', 4500.00),
('Imo', 3500.00),('Jigawa', 4500.00),('Kaduna', 4000.00),('Kano', 3500.00),
('Katsina', 4500.00),('Kebbi', 5000.00),('Kogi', 4000.00),('Kwara', 3500.00),
('Lagos', 2500.00),('Nasarawa', 4000.00),('Niger', 4000.00),('Ogun', 3000.00),
('Ondo', 3500.00),('Osun', 3500.00),('Oyo', 3500.00),('Plateau', 4000.00),
('Rivers', 3500.00),('Sokoto', 5000.00),('Taraba', 4500.00),('Yobe', 5000.00),
('Zamfara', 5000.00);

-- Run this SQL in phpMyAdmin to create the reviews table

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT NOT NULL,
    verified_purchase TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_rating (rating),
    UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;