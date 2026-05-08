<<<<<<< HEAD

<div align="center">

# 💎 JewelSync ERP
### ✨ Premium Jewellery Billing & Inventory Management System ✨

<img src="https://readme-typing-svg.herokuapp.com?font=Poppins&size=28&duration=3000&color=F7C600&center=true&vCenter=true&width=900&lines=Jewellery+Billing+%26+Inventory+System;Built+with+PHP+%2B+MySQL;GST+Billing+%7C+Inventory+%7C+Reports;Professional+ERP+for+Jewellery+Stores" />

<br>

<img src="https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
<img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
<img src="https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" />
<img src="https://img.shields.io/badge/Status-Active-success?style=for-the-badge" />
<img src="https://img.shields.io/badge/Open%20Source-Yes-brightgreen?style=for-the-badge" />

<br><br>

<img src="https://capsule-render.vercel.app/api?type=waving&color=F7C600&height=120&section=header"/>

</div>

---

# 📌 About The Project

JewelSync ERP is a modern Jewellery Billing & Inventory System developed for jewellery stores and retail businesses.  
The system helps manage GST billing, jewellery inventory, invoices, customer ledgers, stock management, payments, reports, and daily metal rates through a centralized web platform.

---

# ✨ Quick Summary

| Feature | Details |
|---|---|
| 🎯 Purpose | manage customers, invoices, metal rates, stock, payments and reports |
| 💻 Language | PHP (PDO), MySQL (MariaDB/MySQL) |
| 🏪 Recommended For | small retail jewellery shops |
| ⚙️ Deployment | XAMPP, MAMP, LAMP |

---

# ✅ Checklist (your request)

- [x] Provide a README describing the project and how to run it locally.
- [x] Start the project locally (PHP built-in server) — server started in background. Database import still required for full functionality.

---

# ⚙️ Requirements

<div align="center">

| Technology | Version |
|---|---|
| 🐘 PHP | 7.4+ |
| 🛢️ MySQL | 5.7+ |
| 🧩 MariaDB | Supported |
| 🎨 Bootstrap | 5 |
| 🌐 Web Server | Apache / Nginx / XAMPP / MAMP |

</div>

### Required PHP Extensions

```bash
pdo_mysql
mbstring
json
gd
```

---

# 🚀 Quick Start (Recommended for Local Development)

## 1️⃣ Create Database & Import Schema

```bash
# create database (if using mysql CLI)
mysql -u root -p < database/schema.sql
```

Or import:

```bash
database/schema.sql
```

using phpMyAdmin or any preferred DB tool.

Default DB Name:

```bash
jewellery_billing
```

---

## 2️⃣ Configure Database

Update credentials if needed in:

```bash
config/database.php
```

Default values:

| Setting | Default |
|---|---|
| Host | 127.0.0.1 |
| Username | root |
| Password | *(empty)* |

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

# 💻 Alternative Setup (XAMPP / MAMP)

Place the `jewellery` folder inside your server document root.

### Example (XAMPP macOS)

```bash
/Applications/XAMPP/htdocs/jewellery
```

Access project:

```bash
http://localhost/jewellery
```

---

# 🔐 Default Credentials

Per the project setup notes, the initial default credentials are:

<div align="center">

| Username | Password |
|---|---|
| `admin` | `admin123` |

</div>

⚠️ Change the password immediately after first login.

---

# 🛠️ Project Configuration

## 📂 Database Configuration

File:

```bash
config/database.php
```

Update:
- `DB_HOST`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_NAME`

---

## ⚙️ Application Constants

File:

```bash
config/constants.php
```

Contains:
- `BASE_URL`
- session settings
- GST rates
- paths

### Notes

If running inside a subdirectory:

```php
BASE_URL = '/jewellery';
```

---

# 📂 Folder Structure

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

| File / Folder | Purpose |
|---|---|
| `index.php` | Application entry point |
| `login.php` | Login page |
| `dashboard.php` | Main dashboard |
| `config/` | App configuration |
| `database/schema.sql` | Database schema |
| `includes/` | Shared reusable components |

---

# 🌟 Core Features

<div align="center">

| Module | Features |
|---|---|
| 👥 Customer Management | Add/Edit/Delete customers, ledgers, balances |
| 💎 Jewellery Billing | GST invoices, multi-item billing, PDF invoices |
| 📦 Inventory | Stock inward/outward, purity-wise stock |
| 💳 Payments | Cash, Bank, UPI, Cheque |
| 📈 Reports | Sales, GST, stock, outstanding reports |

</div>

---

# 🧾 Jewellery Billing Features

- Jewellery categories (Ring, Chain, Bangle, etc.)
- Purity options (22K, 18K, Silver 925)
- Gross and net weight calculation
- Wastage handling
- Making charges (per gram / fixed)
- Automatic GST calculation (default 3%)
- Multi-item invoice with PDF download

---

# 📦 Inventory & Stock Management

- Opening stock entry
- Stock inward and outward
- Automatic stock deduction after billing
- Metal-wise and purity-wise stock view
- Low stock alerts

---

# 💳 Payments & Ledger

- Payment entry (Cash, Bank, UPI, Cheque)
- Partial payment support
- Outstanding balance auto update

---

# 📊 Reports

- Date-wise sales report
- Customer-wise sales report
- Stock report
- Outstanding payment report
- GST summary report

---

# 🛠️ Troubleshooting Notes

## ❌ Database Connection Issues

Ensure:
- MySQL is running
- Credentials are correct
- Database exists

---

## ⚠️ Port Conflicts

XAMPP and Homebrew MySQL may both use:

```bash
3306
```

Stop one before starting the other.

---

# 📌 Next Steps

- Optionally import sample data from `database/` scripts.
- Adjust `BASE_URL` when deploying under a subdirectory.

---

# 🚀 Future Improvements

- Automated installer setup
- Docker support
- Unit & integration testing
- Barcode integration
- Multi-branch support
- Advanced analytics dashboard

---

# 📄 License & Author

Repository:

```bash
omsoni21/jewellery
```

Owner:

```bash
omsoni21
```

No license file is included — add one if you want to make the project open source.

---

<div align="center">

## ⭐ Support The Project

If you like this project, consider giving it a ⭐ on GitHub.

<br><br>

<img src="https://capsule-render.vercel.app/api?type=waving&color=F7C600&height=120&section=footer"/>

</div>

> > > > > > > origin/main
