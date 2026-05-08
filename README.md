
<div align="center">

# 💎 JewelSync ERP
### 🚀 Enterprise Resource Planning & Jewellery Billing System

<img src="https://readme-typing-svg.herokuapp.com?font=Poppins&size=28&duration=3000&color=F7C600&center=true&vCenter=true&width=1000&lines=Modern+ERP+for+Jewellery+Businesses;GST+Billing+%7C+Inventory+%7C+Reports;Customer+%26+Ledger+Management;Built+with+PHP+%2B+MySQL;Professional+Business+Management+System" />

<br>

<img src="https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
<img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
<img src="https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" />
<img src="https://img.shields.io/badge/ERP-System-success?style=for-the-badge" />
<img src="https://img.shields.io/badge/Status-Active-brightgreen?style=for-the-badge" />
=======
<<<<<<< HEAD
# 💎 JewelSync ERP — Jewellery Billing & Inventory System

A modern PHP-based Jewellery Billing, Inventory, and GST Management System designed for jewellery shops and wholesalers.

## ✨ Features

- 🧾 GST Billing & Invoice Generation
- 📦 Inventory & Stock Management
- 👥 Customer & Ledger Management
- 💰 Gold/Silver Rate Management
- 📊 Sales & GST Reports
- 🔐 Secure Login System
- 💳 Multiple Payment Methods
- 📄 PDF Invoice Download
- 📈 Outstanding Balance Tracking
  
## 🛠 Tech Stack

| Technology | Version |
|------------|---------|
| PHP | 8.x |
| MySQL | 5.7+ |
| Bootstrap | 5 |
| JavaScript | ES6 |
| HTML/CSS | HTML5/CSS3 |
>>>>>>> main

<br><br>

<img src="https://capsule-render.vercel.app/api?type=waving&color=F7C600&height=120&section=header"/>

</div>

---

# 💎 About JewelSync ERP

JewelSync ERP is a modern **Enterprise Resource Planning (ERP) System** specially designed for jewellery shops, wholesalers, and retail businesses.

The platform integrates:

- 🧾 Jewellery Billing
- 📦 Inventory Management
- 👥 Customer Management
- 💳 Payment Tracking
- 📊 GST & Sales Reporting
- 💰 Metal Rate Management
- 📈 Business Analytics

into a single centralized and easy-to-use business management solution.

---

# ✨ Features

<div align="center">

| Core Features | Description |
|---|---|
| 🧾 GST Billing | Professional GST Invoice Generation |
| 📦 Inventory Management | Real-Time Stock Tracking |
| 👥 Customer Management | Ledger & Outstanding Tracking |
| 💰 Metal Rate Management | Gold/Silver Rate Handling |
| 💳 Payment Management | Cash, UPI, Bank & Cheque |
| 📊 Reports & Analytics | Sales, GST & Business Reports |
| 🔐 Secure Authentication | Login & Role-Based Access |
| 📄 PDF Invoice | Download Printable Invoices |
| 📈 ERP Dashboard | Business Insights & Analytics |

</div>

---

# 📊 Modules Included

---

## 🔐 User Authentication Module

- Secure Login System
- Password Encryption
- Session Management
- Role-Based Access Control

---

## 🧾 Jewellery Billing Module

- GST Invoice Generation
- Multi-Item Billing
- Making Charges Calculation
- Wastage Calculation
- PDF Invoice Download

---

## 📦 Inventory Management Module

- Stock Inward & Outward
- Automatic Stock Deduction
- Low Stock Alerts
- Purity-wise Stock Tracking

---

## 💰 Metal Rate Management Module

- Daily Gold/Silver Rate Entry
- Historical Rate Management
- Automatic Billing Rate Usage

---

## 👥 Customer & Ledger Module

- Customer Profile Management
- Outstanding Balance Tracking
- Credit Limit Handling
- Customer Ledger Maintenance

---

## 💳 Payment Management Module

- Cash / UPI / Bank / Cheque Payments
- Partial Payment Support
- Payment History Tracking

---

## 📊 Reporting & Analytics Module

- Sales Reports
- GST Summary Reports
- Customer-wise Reports
- Inventory Reports
- Outstanding Payment Reports

---

## 🏢 ERP Administration Module

- Company Configuration
- Financial Year Setup
- Invoice Number Management
- Business Dashboard & Analytics

---

# 🚀 Technology Stack

<div align="center">

| Technology | Usage |
|---|---|
| 🐘 PHP | Backend Development |
| 🛢️ MySQL | Database Management |
| 🎨 Bootstrap 5 | Frontend UI Design |
| ⚡ JavaScript | Client-side Functionality |
| 🌐 HTML/CSS | Interface Design |

</div>

---

# ⚙️ System Requirements

| Requirement | Version |
|---|---|
| PHP | 7.4+ |
| MySQL | 5.7+ |
| MariaDB | Supported |
| Bootstrap | 5 |
| Web Server | Apache / Nginx / XAMPP / MAMP |

---

# 🚀 Quick Start

## 1️⃣ Import Database

```bash
mysql -u root -p < database/schema.sql
```

Or import:

```bash
database/schema.sql
```

using phpMyAdmin.

---

## 2️⃣ Configure Database

Update credentials inside:

```bash
config/database.php
```

---

## 3️⃣ Run Development Server

```bash
cd /path/to/jewellery
php -S 127.0.0.1:8000
```

---

## 4️⃣ Open In Browser

```bash
http://127.0.0.1:8000
```

The root `index.php` redirects to `login.php`.

---

# 🔐 Default Credentials

<div align="center">

| Username | Password |
|---|---|
| `admin` | `admin123` |

</div>

⚠️ Change password immediately after first login.

---

# 🛠️ Project Configuration

---

## 📂 Database Configuration

### File:

```bash
config/database.php
```

### Update:

```env
DB_HOST
DB_USERNAME
DB_PASSWORD
DB_NAME
```

### Default Values

| Setting | Default |
|---|---|
| Host | 127.0.0.1 |
| Username | root |
| Password | *(empty)* |
| Database | jewellery_billing |

---

## ⚙️ Application Constants

### File:

```bash
config/constants.php
```

### Contains:

- `BASE_URL`
- Session Settings
- GST Rates
- File Paths
- Application Constants

---

## 📝 Notes

If running inside a subdirectory:

```php
BASE_URL = '/jewellery';
```

Example:

```bash
http://localhost/jewellery
```

---

# 📂 Project Structure

```bash
📦 jewellery
 ┣ 📂 ajax
 ┣ 📂 api
 ┣ 📂 assets
 ┣ 📂 config
 ┣ 📂 database
 ┣ 📂 includes
 ┣ 📂 uploads
 ┣ 📂 reports
 ┣ 📜 index.php
 ┣ 📜 login.php
 ┗ 📜 dashboard.php
```

---

# 📁 Important Files & Folders

<div align="center">

| File / Folder | Purpose |
|---|---|
| `index.php` | Application entry point |
| `login.php` | Login page |
| `dashboard.php` | Main dashboard |
| `config/` | Application config |
| `database/schema.sql` | Database schema |
| `includes/` | Shared reusable components |

</div>

---

# 🌟 Core ERP Capabilities

✅ Centralized Business Management  
✅ Real-Time Inventory Tracking  
✅ Automated GST Calculations  
✅ Financial Record Management  
✅ Digital Invoice Generation  
✅ Business Analytics Dashboard  
✅ Reporting & Data Management  
✅ Customer & Vendor Handling  

---

# 📌 Ideal For

- Jewellery Shops
- Jewellery Wholesalers
- Gold & Silver Retailers
- Multi-Branch Jewellery Businesses
- Small & Medium Enterprises (SMEs)

---

# 🔥 Key Highlights

✔ Modern ERP Architecture  
✔ GST-Compliant Billing  
✔ Responsive User Interface  
✔ Secure Authentication System  
✔ Automated Business Workflow  
✔ Scalable Inventory System  
✔ Professional Reporting System  

---

# 🛠️ Future Improvements

- Barcode Integration
- Multi-Branch Support
- Advanced Business Analytics
- Cloud Deployment
- Mobile Application
- AI-Based Sales Prediction

---

# 📄 License & Author

Repository:

```bash
omsoni21/jewelsync_ERP
```

Owner:

```bash
omsoni21
```

---

<div align="center">

# ⭐ Support The Project

If you like this project, consider giving it a ⭐ on GitHub.

<br><br>

<img src="https://capsule-render.vercel.app/api?type=waving&color=F7C600&height=120&section=footer"/>

</div>
