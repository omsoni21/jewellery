# 💎 JewelSync ERP - Complete Project Documentation

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [Core Modules](#core-modules)
3. [Features Breakdown](#features-breakdown)
4. [Technical Architecture](#technical-architecture)
5. [Business Workflow](#business-workflow)
6. [Database Structure](#database-structure)
7. [Installation Guide](#installation-guide)
8. [User Roles](#user-roles)
9. [API Integrations](#api-integrations)
10. [Future Enhancements](#future-enhancements)

---

## 🎯 Project Overview

**JewelSync ERP** is a comprehensive Enterprise Resource Planning system specifically designed for wholesale jewellery businesses. It provides end-to-end management of customers, billing, inventory, payments, GST compliance, and business analytics.

### Key Highlights

- 🏢 **Complete Business Solution** - All jewellery business operations in one platform
- 💰 **Financial Management** - Invoicing, payments, ledger tracking
- 📦 **Inventory Control** - Stock management with low stock alerts
- 🧾 **GST Compliant** - Automatic GST calculation and filing support
- 📊 **Business Intelligence** - Comprehensive reports and analytics
- 🔒 **Secure & Multi-User** - Role-based access control

---

## 🏗️ Core Modules

### 1. 👥 Customer Management

**Location:** `customers/`

Manage all business customers/parties with complete profiling.

#### Features

- ✅ Add, edit, and view customer details
- ✅ GST number verification (API-based)
- ✅ PAN number tracking
- ✅ Credit limit management
- ✅ Opening balance setup
- ✅ Complete transaction history
- ✅ Outstanding balance tracking
- ✅ Search and filter customers

#### Files

| File       | Purpose                             |
| ---------- | ----------------------------------- |
| `add.php`  | Add new customer                    |
| `edit.php` | Edit customer information           |
| `list.php` | View all customers with filters     |
| `view.php` | Customer profile & complete history |

---

### 2. 🧾 Billing & Invoicing

**Location:** `billing/`

Professional invoice creation and management for jewellery sales.

#### Features

- ✅ Create detailed sales invoices
- ✅ Multiple metal support (Gold, Silver)
- ✅ Purity tracking (24K, 22K, 18K, 14K, 925, 999)
- ✅ Automatic GST calculation:
  - 3% GST on jewellery (1.5% CGST + 1.5% SGST)
  - 5% GST on making charges
- ✅ Weight calculations:
  - Gross Weight
  - Net Weight
  - Wastage Weight
  - Fine/Purity Weight
- ✅ Making charges (per gram or fixed)
- ✅ Discount management
- ✅ Payment tracking (paid/balance)
- ✅ Invoice printing with company branding
- ✅ PDF generation
- ✅ Barcode integration

#### Invoice Calculation Flow

```
Gross Weight
    ↓
- Wastage Weight
    ↓
= Net Weight
    ↓
× Metal Rate (per gram)
    ↓
= Metal Amount
    ↓
+ Making Charges
    ↓
= Item Total
    ↓
+ GST (3% or 5%)
    ↓
= Final Amount
```

#### Files

| File         | Purpose                  |
| ------------ | ------------------------ |
| `create.php` | Create new invoice       |
| `view.php`   | View invoice details     |
| `print.php`  | Print-friendly invoice   |
| `pdf.php`    | Generate PDF invoice     |
| `list.php`   | Invoice listing & search |

---

### 3. 📦 Inventory Management

**Location:** `inventory/`

Complete stock and product management system.

#### Features

- ✅ Product catalog management
- ✅ Category-based organization:
  - Ring, Chain, Bangle, Bracelet
  - Earring, Necklace, Pendant
  - Mangalsutra, Nose Pin, Anklet
  - Toe Ring, Others
- ✅ Stock tracking (quantity & weight)
- ✅ **Low Stock Alerts** with category-specific minimums
- ✅ Stock inward/outward transactions
- ✅ **Barcode System:**
  - Generate barcodes for products
  - Print barcode labels
  - Scan barcodes (camera-based)
  - QR Code generation
- ✅ Product search and filtering
- ✅ Stock valuation reports

#### Minimum Stock Thresholds

| Category | Minimum Quantity |
| -------- | ---------------- |
| Ring     | 10 pieces        |
| Chain    | 5 pieces         |
| Bangle   | 8 pieces         |
| Necklace | 3 pieces         |
| Earring  | 15 pieces        |
| Others   | 5 pieces         |

#### Files

| File           | Purpose                          |
| -------------- | -------------------------------- |
| `products.php` | Product catalog management       |
| `stock.php`    | Stock view with low stock alerts |
| `inward.php`   | Stock inward entry               |
| `barcode.php`  | Barcode generation & scanning    |

---

### 4. 💰 Payment & Ledger

**Location:** `payments/`

Track all payments and maintain customer ledger.

#### Features

- ✅ Payment entry with multiple methods:
  - Cash
  - Bank Transfer
  - UPI
  - Cheque
- ✅ Customer ledger (debit/credit tracking)
- ✅ Payment reference tracking
- ✅ Invoice-wise payment allocation
- ✅ Balance calculations
- ✅ Payment history
- ✅ Date-wise payment reports

#### Files

| File         | Purpose              |
| ------------ | -------------------- |
| `entry.php`  | Record new payment   |
| `ledger.php` | View customer ledger |

---

### 5. 📈 Reports & Analytics

**Location:** `reports/`

Comprehensive business reporting and GST filing.

#### Available Reports

**1. Sales Report** (`sales.php`)

- Date-range based sales analysis
- Customer-wise sales breakdown
- Total sales, taxable amount, GST breakdown
- Payment status (paid/pending)
- Customer summary with:
  - Total purchases
  - Gold vs Silver purchases
  - Outstanding dues
  - Advance payments

**2. GST Report** (`gst.php`)

- GST collection summary
- CGST, SGST, IGST breakdown
- Period-wise GST analysis
- Export capabilities

**3. GST Filing** (`gst_filing.php`)

- GSTR-1 data generation
- B2B invoice classification
- B2C (small/large) classification
- JSON export for GST portal
- Excel report generation
- **Direct API filing** (with credentials)

**4. Stock Report** (`stock.php`)

- Current stock levels
- Stock valuation
- Low stock items
- Category-wise stock summary

**5. Outstanding Report** (`outstanding.php`)

- Customer pending payments
- Age-wise outstanding analysis
- Total receivables summary

#### Files

| File              | Purpose                           |
| ----------------- | --------------------------------- |
| `sales.php`       | Sales analysis & customer summary |
| `gst.php`         | GST collection report             |
| `gst_filing.php`  | GST return filing (GSTR-1)        |
| `stock.php`       | Stock valuation report            |
| `outstanding.php` | Outstanding payments report       |

---

### 6. ⚙️ Settings & Configuration

**Location:** `settings/`

System-wide configuration and management.

#### Features

**Company Settings** (`company.php`)

- Company name & branding
- GST number & PAN
- Complete address
- Contact information
- Bank account details
- **GST API Credentials:**
  - API Key
  - API Secret
  - Portal Username
  - Portal Password

**Metal Rates** (`rates.php`)

- Daily gold rates (24K, 22K, 18K, 14K)
- Daily silver rates (925, 999)
- Rate history tracking
- Date-wise rate management

**User Management** (`users.php`)

- Add/Edit system users
- Role assignment (Admin, Billing, Accountant)
- User activation/deactivation
- Password management

#### Files

| File          | Purpose                            |
| ------------- | ---------------------------------- |
| `company.php` | Company information & API settings |
| `rates.php`   | Metal rate management              |
| `users.php`   | User & role management             |

---

### 7. 🏠 Dashboard

**Location:** `dashboard.php`

Business overview and quick actions.

#### Dashboard Components

- 📊 Today's sales summary
- 👥 Total customers count
- 💰 Outstanding payments total
- ⚠️ Low stock alerts count
- 📋 Recent invoices list
- 🚀 Quick action buttons

---

## 🔧 Technical Architecture

### Technology Stack

| Component         | Technology                               |
| ----------------- | ---------------------------------------- |
| **Backend**       | PHP 7.4+                                 |
| **Database**      | MySQL 5.7+ / MariaDB                     |
| **Frontend**      | HTML5, CSS3, JavaScript                  |
| **CSS Framework** | Bootstrap 5.3                            |
| **JavaScript**    | jQuery 3.x                               |
| **Icons**         | Bootstrap Icons 1.10                     |
| **Fonts**         | Google Fonts (Poppins, Playfair Display) |
| **Barcode**       | JsBarcode 3.11.5                         |
| **QR Code**       | QRCode.js 1.0.0                          |
| **Scanner**       | QuaggaJS 0.12.1                          |
| **Server**        | Apache 2.4+ / Nginx                      |

### Security Features

- 🔒 **Session Management** - Secure sessions with timeout (30 min)
- 🔒 **Password Hashing** - bcrypt algorithm
- 🔒 **SQL Injection Prevention** - PDO prepared statements
- 🔒 **XSS Protection** - Input sanitization and output escaping
- 🔒 **CSRF Protection** - Token validation
- 🔒 **Role-Based Access** - 3 user roles with permissions
- 🔒 **Activity Logging** - Track user actions

### Database Architecture

- **Connection:** PDO (PHP Data Objects)
- **Engine:** InnoDB (with foreign key support)
- **Character Set:** UTF8MB4 (full Unicode support)
- **Transactions:** Supported for data integrity
- **Indexing:** Optimized queries with proper indexes

---

## 💼 Business Workflow

### Complete Business Process Flow

```
1. INITIAL SETUP
   ↓
   Configure Company Settings
   ↓
   Add Users (Admin, Billing Staff, Accountant)
   ↓
   Set Metal Rates (Daily Gold/Silver Prices)

2. CUSTOMER ONBOARDING
   ↓
   Add New Customer
   ↓
   Enter Details (Name, GST, PAN, Address)
   ↓
   Verify GST Number (API)
   ↓
   Set Opening Balance & Credit Limit

3. INVENTORY SETUP
   ↓
   Create Product Categories
   ↓
   Add Products with Details
   ↓
   Set Minimum Stock Levels
   ↓
   Add Initial Stock (Inward Entry)
   ↓
   Generate & Print Barcodes

4. DAILY OPERATIONS
   ↓
   Create Invoice
   ↓
   Select Customer
   ↓
   Add Items (Metal, Weight, Purity)
   ↓
   System Calculates:
     - Metal Amount
     - Making Charges
     - GST (3% / 5%)
     - Total Amount
   ↓
   Record Payment (Cash/Bank/UPI/Cheque)
   ↓
   Print Invoice

5. INVENTORY MANAGEMENT
   ↓
   Monitor Stock Levels
   ↓
   Receive Low Stock Alerts
   ↓
   Add New Stock (Inward)
   ↓
   Update Metal Rates Daily

6. FINANCIAL TRACKING
   ↓
   Record All Payments
   ↓
   Track Outstanding Balances
   ↓
   View Customer Ledger
   ↓
   Generate Payment Reports

7. REPORTING & COMPLIANCE
   ↓
   Generate Sales Reports
   ↓
   View GST Collection Summary
   ↓
   Generate GSTR-1 Data
   ↓
   File GST Returns (API/Manual)
   ↓
   Export Reports (Excel/PDF)

8. BUSINESS ANALYSIS
   ↓
   Review Dashboard Metrics
   ↓
   Analyze Sales Trends
   ↓
   Check Outstanding Payments
   ↓
   Monitor Stock Health
   ↓
   Make Data-Driven Decisions
```

---

## 🗄️ Database Structure

### Core Tables

#### 1. `users` - System Users

```
- id, username, password_hash, email, full_name
- role (admin/billing/accountant)
- is_active, last_login, created_at
```

#### 2. `customers` - Customer Master

```
- id, customer_code, business_name, contact_person
- phone, email, gst_number, pan_number
- address_line1, address_line2, city, state, pincode
- credit_limit, payment_terms
- opening_balance, current_balance
- is_active, created_at
```

#### 3. `invoices` - Sales Invoices

```
- id, invoice_no, customer_id, invoice_date, due_date
- subtotal, discount_amount, taxable_amount
- cgst_amount, sgst_amount, igst_amount, total_amount
- paid_amount, balance_amount
- payment_status (pending/partial/paid)
- notes, created_by, created_at
```

#### 4. `invoice_items` - Invoice Line Items

```
- id, invoice_id, product_id, item_name
- metal_type, purity, quantity
- gross_weight, net_weight, wastage_percent, wastage_weight, total_weight
- rate_per_gram, metal_amount
- making_charge_type, making_charge_rate, making_charge_amount
- item_total
```

#### 5. `products` - Product Catalog

```
- id, product_code, category_id, name
- metal_type, purity, description
- is_active, created_at
```

#### 6. `categories` - Product Categories

```
- id, name, description, is_active
```

#### 7. `stock` - Current Stock Levels

```
- id, product_id, quantity
- gross_weight, net_weight, wastage_weight, purity_weight
- last_updated
```

#### 8. `stock_transactions` - Stock Movement History

```
- id, product_id, transaction_type (opening/inward/outward/adjustment)
- quantity, gross_weight, net_weight, wastage_percent
- reference_type, reference_id, notes
- created_by, created_at
```

#### 9. `metal_rates` - Daily Metal Prices

```
- id, metal_type, purity, rate_per_gram, rate_date
- created_by, created_at
```

#### 10. `payments` - Payment Records

```
- id, customer_id, invoice_id, payment_date, amount
- payment_method (cash/bank/upi/cheque)
- reference_no, bank_name, cheque_no, cheque_date
- notes, created_by, created_at
```

#### 11. `customer_ledger` - Ledger Entries

```
- id, customer_id, transaction_date
- transaction_type (invoice/payment/opening/adjustment)
- reference_id, reference_no
- debit, credit, balance
- notes, created_at
```

#### 12. `company_settings` - System Configuration

```
- id, company_name, gst_number, pan_number
- address, phone, email
- bank_name, bank_account_no, bank_ifsc, bank_branch
- invoice_prefix, financial_year_start/end
- gst_rate, gst_api_key, gst_api_secret, gst_username, gst_password
- logo_path, created_at, updated_at
```

#### 13. `activity_logs` - User Activity Tracking

```
- id, user_id, action, details, ip_address, created_at
```

---

## 🚀 Installation Guide

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2+
- Apache 2.4+ or Nginx
- Composer (optional)
- Modern web browser

### Step-by-Step Installation

#### 1. Download & Extract

```bash
cd /var/www/html  # or your web directory
# Extract JewelSync ERP files
```

#### 2. Configure Database

```bash
# Access MySQL
mysql -u root -p

# Import database schema
mysql -u root -p < database/schema.sql
```

#### 3. Configure Application

```bash
# Edit config files
nano config/database.php
# Update DB_HOST, DB_NAME, DB_USER, DB_PASS

nano config/constants.php
# Update BASE_URL if needed
```

#### 4. Set Permissions

```bash
chmod 755 -R /var/www/html/jewellery
chmod 777 uploads/  # if upload folder exists
```

#### 5. Access Application

```
URL: http://localhost/ (or your domain)
Default Login:
- Username: admin
- Password: admin123
```

#### 6. Initial Setup

1. Login with admin credentials
2. Go to Settings → Company Settings
3. Enter your company details
4. Set current metal rates
5. Add your first customer
6. Start creating invoices!

---

## 👥 User Roles

### 1. 👑 Admin

**Full System Access**

- ✅ All features available
- ✅ User management
- ✅ Company settings
- ✅ Metal rate management
- ✅ Complete reports access
- ✅ System configuration

### 2. 💼 Billing User

**Invoice & Customer Management**

- ✅ Create/edit invoices
- ✅ Manage customers
- ✅ View products & stock
- ✅ Generate barcodes
- ✅ Print invoices
- ❌ Cannot access settings
- ❌ Limited reports access

### 3. 📊 Accountant

**Financial & Reports Access**

- ✅ View all invoices
- ✅ Payment entry
- ✅ Customer ledger
- ✅ All reports
- ✅ GST filing
- ✅ Outstanding tracking
- ❌ Cannot create invoices
- ❌ Limited customer editing

---

## 🔗 API Integrations

### 1. GST API

**Purpose:** GSTIN verification and return filing

**Features:**

- ✅ Verify customer GST numbers
- ✅ Auto-fill business details
- ✅ File GSTR-1 returns directly
- ✅ Check filing status

**Configuration:**

```
Settings → Company Settings → GST API Credentials
- API Key
- API Secret
- Portal Username
- Portal Password
```

**Modes:**

- Sandbox (Testing)
- Production (Live filing)

### 2. Barcode Scanner

**Library:** QuaggaJS 0.12.1

**Supported Formats:**

- Code 128
- EAN-13
- EAN-8
- Code 39
- UPC

**Features:**

- Camera-based scanning
- Real-time detection
- Auto-search product
- Manual barcode entry

### 3. Barcode Generator

**Library:** JsBarcode 3.11.5

**Format:** CODE128

**Features:**

- Product barcodes
- Invoice barcodes
- Custom data encoding
- Print-ready output

### 4. QR Code Generator

**Library:** QRCode.js 1.0.0

**Features:**

- Product QR codes
- Invoice QR codes
- Quick product lookup
- Mobile-friendly scanning

---

## 📊 Key Business Metrics

### Tracked KPIs

1. **Sales Performance**
   - Daily sales
   - Monthly revenue
   - Customer-wise sales
   - Product-wise sales

2. **Inventory Health**
   - Stock levels
   - Low stock alerts
   - Stock valuation
   - Category distribution

3. **Financial Status**
   - Outstanding payments
   - Payment collection rate
   - Customer balances
   - GST liability

4. **Customer Analytics**
   - Total customers
   - Active customers
   - Top buyers
   - Customer lifetime value

---

## 🔄 Data Flow

### Invoice Creation Flow

```
Customer Selection
    ↓
Product Selection
    ↓
Enter Weight & Purity
    ↓
Fetch Metal Rate (from metal_rates table)
    ↓
Calculate:
  - Metal Amount = Net Weight × Rate
  - Wastage = Weight × Wastage %
  - Making Charges (per gram or fixed)
  - Item Total = Metal + Making
    ↓
Calculate GST:
  - Taxable Amount = Sum of Item Totals
  - CGST = Taxable × 1.5%
  - SGST = Taxable × 1.5%
  - Total = Taxable + CGST + SGST
    ↓
Save Invoice (invoices table)
    ↓
Save Items (invoice_items table)
    ↓
Update Stock (stock table)
    ↓
Add Ledger Entry (customer_ledger table)
    ↓
Generate Barcode
    ↓
Print Invoice
```

---

## 🛡️ Security Best Practices

### Implemented Security

- ✅ Password hashing with bcrypt
- ✅ Prepared statements (SQL injection prevention)
- ✅ Input sanitization (XSS prevention)
- ✅ Session timeout (30 minutes)
- ✅ Role-based access control
- ✅ Activity logging
- ✅ HTTPS support
- ✅ Secure file uploads

### Recommended Practices

1. Change default admin password immediately
2. Use strong passwords
3. Enable HTTPS in production
4. Regular database backups
5. Keep PHP & MySQL updated
6. Monitor activity logs
7. Use firewall rules
8. Regular security audits

---

## 📱 Browser Compatibility

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile browsers (responsive)

---

## 🎨 Design Features

### UI/UX Highlights

- 🎨 Modern Bootstrap 5 design
- 🎨 Premium gold-themed color scheme
- 🎨 Responsive layout (mobile-friendly)
- 🎨 Clean typography (Poppins + Playfair Display)
- 🎨 Icon-rich navigation
- 🎨 Card-based dashboard
- 🎨 Print-optimized layouts
- 🎨 Smooth animations
- 🎨 Loading indicators
- 🎨 Success/error notifications

---

## 📈 Performance Optimizations

- ✅ PDO prepared statements (query caching)
- ✅ Indexed database columns
- ✅ Lazy loading for large datasets
- ✅ Pagination for lists
- ✅ Optimized CSS & JS loading
- ✅ CDN for external libraries
- ✅ Minified assets (production)
- ✅ Efficient queries (JOIN optimization)

---

## 🔮 Future Enhancements

### Planned Features

- [ ] Multi-branch support
- [ ] SMS notifications
- [ ] Email invoice delivery
- [ ] WhatsApp integration
- [ ] Mobile app (Android/iOS)
- [ ] Advanced analytics dashboard
- [ ] Purchase order management
- [ ] Supplier management
- [ ] Job work tracking
- [ ] Karigar (artisan) management
- [ ] Hallmark integration
- [ ] Multi-currency support
- [ ] Advanced user permissions
- [ ] Automated backups
- [ ] Cloud sync
- [ ] API for third-party integrations
- [ ] Customer portal
- [ ] Loyalty program
- [ ] Advanced barcode features

---

## 📞 Support & Maintenance

### Regular Maintenance Tasks

1. **Daily:**
   - Update metal rates
   - Check low stock alerts
   - Review outstanding payments

2. **Weekly:**
   - Database backup
   - Activity log review
   - User access audit

3. **Monthly:**
   - GST filing
   - Financial reports
   - Stock reconciliation
   - System performance check

4. **Quarterly:**
   - Full system backup
   - Security audit
   - Database optimization
   - User training

---

## 📄 License & Credits

**Project Name:** JewelSync ERP  
**Version:** 1.0.0  
**Technology:** PHP, MySQL, Bootstrap 5  
**Developed For:** Wholesale Jewellery Businesses

---

## 🎯 Quick Reference

### Important URLs

```
Dashboard:      /dashboard.php
Login:          /login.php
Customers:      /customers/list.php
Billing:        /billing/create.php
Inventory:      /inventory/stock.php
Payments:       /payments/entry.php
Reports:        /reports/sales.php
Settings:       /settings/company.php
```

### Default Credentials

```
Username: admin
Password: admin123
⚠️ Change immediately after first login!
```

### Key Shortcuts

- `Ctrl+P` - Print current page
- `Ctrl+F` - Search in lists
- `Esc` - Close modals
- `Tab` - Navigate forms

---

## ✨ Summary

**JewelSync ERP** is a complete, professional solution for managing wholesale jewellery businesses. It combines customer management, professional billing, inventory tracking, payment management, GST compliance, and comprehensive reporting into one unified platform.

With its modern interface, robust security, and powerful features, JewelSync ERP helps jewellery businesses:

- 📈 Increase efficiency
- 💰 Reduce errors
- 📊 Make better decisions
- 🧾 Stay GST compliant
- 📦 Never run out of stock
- 💼 Deliver professional service

**Built for jewellery businesses, by understanding jewellery businesses!** 💎✨

---

_Documentation Version: 1.0.0_  
_Last Updated: 2026_  
_For: JewelSync ERP_
