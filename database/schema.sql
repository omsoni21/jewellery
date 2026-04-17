-- JewelSync ERP
-- Database Schema

CREATE DATABASE IF NOT EXISTS jewellery_billing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jewellery_billing;

-- ============================================
-- 1. USER & SECURITY MANAGEMENT
-- ============================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'billing', 'accountant') NOT NULL DEFAULT 'billing',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- 2. COMPANY & GST CONFIGURATION
-- ============================================

CREATE TABLE company_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(200) NOT NULL,
    gst_number VARCHAR(20),
    pan_number VARCHAR(20),
    address_line1 VARCHAR(200),
    address_line2 VARCHAR(200),
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(100),
    bank_name VARCHAR(100),
    bank_account_no VARCHAR(50),
    bank_ifsc VARCHAR(20),
    bank_branch VARCHAR(100),
    invoice_prefix VARCHAR(10) DEFAULT 'INV',
    financial_year_start DATE,
    financial_year_end DATE,
    gst_rate DECIMAL(5,2) DEFAULT 3.00,
    gst_api_key VARCHAR(255),
    gst_api_secret VARCHAR(255),
    gst_username VARCHAR(100),
    gst_password VARCHAR(255),
    logo_path VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 3. CUSTOMER (PARTY) MANAGEMENT
-- ============================================

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) UNIQUE,
    business_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    gst_number VARCHAR(20),
    pan_number VARCHAR(20),
    address_line1 VARCHAR(200),
    address_line2 VARCHAR(200),
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    payment_terms INT DEFAULT 30, -- days
    opening_balance DECIMAL(15,2) DEFAULT 0.00,
    current_balance DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 4. METAL RATE MANAGEMENT
-- ============================================

CREATE TABLE metal_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metal_type ENUM('gold', 'silver') NOT NULL,
    purity VARCHAR(10) NOT NULL,
    rate_per_gram DECIMAL(12,2) NOT NULL,
    rate_date DATE NOT NULL,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_rate (metal_type, purity, rate_date)
);

-- ============================================
-- 5. JEWELLERY PRODUCT & INVENTORY
-- ============================================

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(20) UNIQUE,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    metal_type ENUM('gold', 'silver') NOT NULL,
    purity VARCHAR(10) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT DEFAULT 0,
    gross_weight DECIMAL(10,3) DEFAULT 0.000,
    net_weight DECIMAL(10,3) DEFAULT 0.000,
    wastage_weight DECIMAL(10,3) DEFAULT 0.000,
    purity_weight DECIMAL(10,3) DEFAULT 0.000,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product (product_id)
);

CREATE TABLE stock_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    transaction_type ENUM('opening', 'inward', 'outward', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    gross_weight DECIMAL(10,3) NOT NULL,
    net_weight DECIMAL(10,3) NOT NULL,
    wastage_percent DECIMAL(5,2) DEFAULT 0.00,
    reference_type ENUM('invoice', 'purchase', 'adjustment', 'opening') DEFAULT NULL,
    reference_id INT,
    notes TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- 6. BILLING & INVOICES
-- ============================================

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    taxable_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    cgst_amount DECIMAL(15,2) DEFAULT 0.00,
    sgst_amount DECIMAL(15,2) DEFAULT 0.00,
    igst_amount DECIMAL(15,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(15,2) DEFAULT 0.00,
    balance_amount DECIMAL(15,2) DEFAULT 0.00,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    notes TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_id INT,
    item_name VARCHAR(200) NOT NULL,
    metal_type ENUM('gold', 'silver') NOT NULL,
    purity VARCHAR(10) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    gross_weight DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    net_weight DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    wastage_percent DECIMAL(5,2) DEFAULT 0.00,
    wastage_weight DECIMAL(10,3) DEFAULT 0.000,
    total_weight DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    rate_per_gram DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    metal_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    making_charge_type ENUM('per_gram', 'fixed') DEFAULT 'per_gram',
    making_charge_rate DECIMAL(12,2) DEFAULT 0.00,
    making_charge_amount DECIMAL(15,2) DEFAULT 0.00,
    item_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- ============================================
-- 7. PAYMENTS & LEDGER
-- ============================================

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    invoice_id INT,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'bank', 'upi', 'cheque') NOT NULL,
    reference_no VARCHAR(50),
    bank_name VARCHAR(100),
    cheque_no VARCHAR(50),
    cheque_date DATE,
    notes TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE customer_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    transaction_type ENUM('invoice', 'payment', 'opening', 'adjustment') NOT NULL,
    reference_id INT,
    reference_no VARCHAR(50),
    debit DECIMAL(15,2) DEFAULT 0.00,
    credit DECIMAL(15,2) DEFAULT 0.00,
    balance DECIMAL(15,2) NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Default Admin User (password: admin123)
INSERT INTO users (username, password_hash, email, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@jewellery.com', 'System Administrator', 'admin');

-- Default Categories
INSERT INTO categories (name, description) VALUES 
('Ring', 'Gold and Silver Rings'),
('Chain', 'Gold and Silver Chains'),
('Bangle', 'Gold and Silver Bangles'),
('Bracelet', 'Gold and Silver Bracelets'),
('Earring', 'Gold and Silver Earrings'),
('Necklace', 'Gold and Silver Necklaces'),
('Pendant', 'Gold and Silver Pendants'),
('Mangalsutra', 'Gold Mangalsutras'),
('Nose Pin', 'Gold and Silver Nose Pins'),
('Anklet', 'Silver Anklets'),
('Toe Ring', 'Silver Toe Rings'),
('Others', 'Other Jewellery Items');

-- Default Company Settings
INSERT INTO company_settings (company_name, gst_number, address_line1, city, state) VALUES 
('Your Jewellery Business', '00AAAAA0000A1Z5', 'Shop No. 1, Main Market', 'Mumbai', 'Maharashtra');
