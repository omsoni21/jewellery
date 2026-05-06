# JewelSync ERP — Jewellery Billing & Inventory System

Lightweight PHP-based billing, inventory and invoicing system tailored for jewellery stores.

## Quick summary

- Purpose: manage customers, invoices, metal rates, stock, payments and reports.
- Language: PHP (PDO), MySQL (MariaDB/MySQL)
- Recommended for: small retail jewellery shops, local deployments (XAMPP, MAMP, LAMP).

## Checklist (your request)

- [x] Provide a README describing the project and how to run it locally.
- [x] Start the project locally (PHP built-in server) — server started in background. Database import still required for full functionality.

## Requirements

- PHP 7.4+ with PDO and common extensions (pdo_mysql, mbstring, json, gd as needed)
- MySQL 5.7+ or MariaDB
- Web server (optional): Apache / Nginx / XAMPP / MAMP
- Command line access or phpMyAdmin for importing the DB schema

## Quick start (recommended for local development)

1. Create the database and import the schema:

```bash
# create database (if using mysql CLI)
mysql -u root -p < database/schema.sql
```

Or import `database/schema.sql` via phpMyAdmin or your preferred DB tool. Default DB name in the schema: `jewellery_billing`.

2. Update DB credentials if needed in `config/database.php` (defaults: host 127.0.0.1, user root, empty password).

3. Run the PHP built-in server from the project root (for development):

```bash
cd /path/to/jewellery
php -S 127.0.0.1:8000
```

Then open http://127.0.0.1:8000 in your browser. The root `index.php` redirects to `login.php`.

Alternative: use XAMPP or MAMP — place the `jewellery` folder inside your webserver's document root (for XAMPP on macOS: `/Applications/XAMPP/htdocs/jewellery`) and access via http://localhost/jewellery.

## Default credentials

Per the project setup notes, the initial default credentials are:

- Username: `admin`
- Password: `admin123`

Change the password immediately after first login.

## Project configuration

- Database configuration: `config/database.php` — update DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME as needed.
- Application constants: `config/constants.php` (BASE_URL, session settings, GST rates, paths).

Notes:

- If you run inside a subdirectory (for example with XAMPP at `/jewellery`), set `BASE_URL` in `config/constants.php` to `'/jewellery'`.

## Folder overview

Key folders and responsibilities:

- `ajax/` — AJAX endpoints used by the frontend
- `api/` — lightweight API endpoints
- `assets/` — CSS and JS
- `billing/` — invoice generation and printing
- `config/` — DB and application configuration
- `customers/`, `inventory/`, `payments/`, `reports/`, `settings/` — feature modules
- `database/schema.sql` — full database schema and create statements

## Running with XAMPP (macOS)

1. Copy the `jewellery` folder to `/Applications/XAMPP/htdocs/`.
2. Start Apache & MySQL from the XAMPP control panel.
3. Import `database/schema.sql` using phpMyAdmin (http://localhost/phpmyadmin) or the mysql CLI.
4. Visit http://localhost/jewellery

## Troubleshooting

- Blank pages or PHP errors: enable display_errors in `php.ini` (development only) and check the webserver/PHP error log.
- Database connection failures: confirm credentials in `config/database.php` and that MySQL is running. The default DB in config is `jewellery_billing`.
- Missing uploads/invoices folders: ensure `uploads/` and `invoices/` exist and are writable by your web server user.

## Security & production notes

- Never expose error details in production.
- Use strong passwords and restrict DB access.
- Use HTTPS in production.
- Regularly backup `database/schema.sql` and your DB data.

## Developer notes & useful commands

- Run a quick PHP version check:

```bash
php -v
```

- Import DB from the project root (MySQL CLI):

```bash
mysql -u root -p jewellery_billing < database/schema.sql
```

## Where to look next

- Login page: `login.php`
- App entry: `index.php` (redirects to `login.php`)
- DB setup script: `database/schema.sql`

## License

This repository does not include an explicit license file. Treat it as proprietary unless you add a LICENSE.

---

If you want, I can also:

- import the schema automatically (if you provide DB root password) and create a test admin user, or
- adjust `config/constants.php` -> `BASE_URL` to match a XAMPP install path and verify a login page loads.
