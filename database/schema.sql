-- =========================================================
-- JEWELLERY SHOP MANAGEMENT PLATFORM - DATABASE SCHEMA
-- MySQL 8.x (Laragon)
-- =========================================================

CREATE DATABASE IF NOT EXISTS jewellery_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jewellery_shop;

-- ---------------------------------------------------------
-- USERS (customers)
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    status ENUM('active','disabled') DEFAULT 'active',
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- ADMINS (separate auth, RBAC)
-- ---------------------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','store_manager','inventory_manager','appointment_manager','content_manager') DEFAULT 'super_admin',
    status ENUM('active','disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- BRANCHES
-- ---------------------------------------------------------
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(150),
    map_url TEXT,
    business_hours VARCHAR(150),
    image VARCHAR(255),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- CATEGORIES & COLLECTIONS
-- ---------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- PRODUCTS  (1 product = 1 unique physical piece, qty always 1)
-- ---------------------------------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id INT,
    collection_id INT,
    branch_id INT NOT NULL,
    occasion VARCHAR(100),
    gender ENUM('men','women','unisex') DEFAULT 'unisex',
    description TEXT,
    weight DECIMAL(10,2) DEFAULT 0,
    base_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    making_charges DECIMAL(12,2) NOT NULL DEFAULT 0,
    gst_percent DECIMAL(5,2) NOT NULL DEFAULT 3.00,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    final_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    thumbnail VARCHAR(255),
    model_3d VARCHAR(255) DEFAULT NULL,       -- .glb file path
    tryon_type ENUM('ring','necklace','earring','bracelet','none') DEFAULT 'none',
    tryon_asset VARCHAR(255) DEFAULT NULL,    -- transparent PNG overlay
    status ENUM('available','reserved','sold','hidden') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE product_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    material_name VARCHAR(50) NOT NULL,   -- Gold, Platinum, Silver...
    purity VARCHAR(20),                   -- 22K, 18K, 925...
    weight DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE product_gemstones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    gemstone_name VARCHAR(50) NOT NULL,   -- Diamond, Ruby...
    carat DECIMAL(6,2) DEFAULT 0,
    quantity INT DEFAULT 1,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- CERTIFICATES
-- ---------------------------------------------------------
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    certificate_no VARCHAR(50) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    file_path VARCHAR(255),
    status ENUM('valid','revoked') DEFAULT 'valid',
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- APPOINTMENTS
-- ---------------------------------------------------------
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    branch_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    purpose VARCHAR(100) DEFAULT 'View & Purchase',
    status ENUM('pending','approved','rejected','rescheduled','cancelled','completed') DEFAULT 'pending',
    admin_note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- WISHLIST
-- ---------------------------------------------------------
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wish (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- REVIEWS
-- ---------------------------------------------------------
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT,
    admin_reply TEXT DEFAULT NULL,
    status ENUM('visible','hidden') DEFAULT 'visible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- PURCHASES (recorded manually by admin after in-store sale)
-- ---------------------------------------------------------
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    branch_id INT NOT NULL,
    final_price DECIMAL(12,2) NOT NULL,
    purchase_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- CONTACT MESSAGES
-- ---------------------------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(150),
    phone VARCHAR(20),
    subject VARCHAR(150),
    message TEXT,
    status ENUM('new','read','replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- SITE SETTINGS (key-value, used for homepage content/admin config)
-- ---------------------------------------------------------
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- ACTIVITY LOGS (admin actions)
-- ---------------------------------------------------------
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);

-- =========================================================
-- SEED DATA
-- =========================================================

INSERT INTO admins (name, email, password, role) VALUES
('Super Admin', 'admin@jewellery.com', '$2y$10$1qUZLK6XyQhq7q0m2H8nMuP1vXYVZ4Yy8O5wq1z9y4Y1n2b3c4d5e', 'super_admin');
-- Default admin password is: admin1234  (hash generated with PHP password_hash, bcrypt)
-- IMPORTANT: run database/generate_admin_hash.php once if the hash above doesn't match your PHP build.

INSERT INTO branches (name, address, phone, email, business_hours, status) VALUES
('Rajkot Branch', 'Yagnik Road, Rajkot, Gujarat', '9998887770', 'rajkot@jewellery.com', '10:00 AM - 8:00 PM', 'active'),
('Ahmedabad Branch', 'CG Road, Ahmedabad, Gujarat', '9998887771', 'ahmedabad@jewellery.com', '10:00 AM - 8:00 PM', 'active'),
('Surat Branch', 'Ring Road, Surat, Gujarat', '9998887772', 'surat@jewellery.com', '10:00 AM - 8:00 PM', 'active');

INSERT INTO categories (name, description, status) VALUES
('Rings', 'Engagement, wedding and fashion rings', 'active'),
('Necklaces', 'Gold and diamond necklaces', 'active'),
('Earrings', 'Studs, hoops and drops', 'active'),
('Bracelets', 'Gold and diamond bracelets', 'active');

INSERT INTO collections (name, description, status) VALUES
('Bridal Collection', 'Wedding and engagement specials', 'active'),
('Everyday Elegance', 'Lightweight daily wear', 'active'),
('Festive Collection', 'Statement festive pieces', 'active');

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Mahavir Ornaments'),
('site_email', 'info@jewellery.com'),
('site_phone', '9998887770'),
('hero_title', 'Timeless Jewellery, Crafted For You'),
('hero_subtitle', 'Explore, try on virtually, and book your visit');
