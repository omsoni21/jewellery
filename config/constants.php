<?php

/**
 * Application Constants
 * JewelSync ERP - Jewellery Billing & Inventory Management System
 */

// Base URL - Set this to your project folder path
// For XAMPP with project in htdocs/jewellery/, use: '/jewellery'
// For root installation, use: ''
define('BASE_URL', '');

// Session Configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('SESSION_NAME', 'jewellery_session');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_BILLING', 'billing');
define('ROLE_ACCOUNTANT', 'accountant');

// Metal Types
define('METAL_GOLD', 'gold');
define('METAL_SILVER', 'silver');

// Purity Types
define('PURITY_24K', '24K');
define('PURITY_22K', '22K');
define('PURITY_18K', '18K');
define('PURITY_14K', '14K');
define('PURITY_SILVER_925', '925');
define('PURITY_SILVER_999', '999');

// Payment Methods
define('PAYMENT_CASH', 'cash');
define('PAYMENT_BANK', 'bank');
define('PAYMENT_UPI', 'upi');
define('PAYMENT_CHEQUE', 'cheque');

// GST Rates (as per Indian GST laws for jewellery - 2024)
// Gold Jewellery: 3% GST (1.5% CGST + 1.5% SGST)
// Silver Jewellery: 3% GST (1.5% CGST + 1.5% SGST)  
// Making Charges: 5% GST (2.5% CGST + 2.5% SGST) - considered as job work/service
// Diamonds/Precious Stones: 3% GST
define('GST_RATE', 3); // Default 3% for jewellery
define('GST_RATE_GOLD', 3); // Gold Jewellery: 3%
define('GST_RATE_SILVER', 3); // Silver Jewellery: 3%
define('GST_RATE_MAKING_CHARGES', 5); // Making charges (job work): 5%
define('GST_RATE_DIAMOND', 3); // Diamonds/Stones: 3%

// Application Settings
define('APP_NAME', 'JewelSync ERP');
define('APP_VERSION', '1.0.0');
define('CURRENCY_SYMBOL', '₹');
define('WEIGHT_UNIT', 'gram');

// File Upload Paths
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('INVOICE_PATH', __DIR__ . '/../invoices/');

// Date Formats
define('DATE_FORMAT', 'd-m-Y');
define('DATETIME_FORMAT', 'd-m-Y H:i:s');
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');
