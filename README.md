<<<<<<< HEAD

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
<!-- Merged README: combines both summaries and usage instructions -->

# JewelSync ERP — Jewellery Billing & Inventory System

Lightweight PHP-based billing, inventory and invoicing system tailored for jewellery stores.

## One-line summary

- Purpose: manage customers, invoices, metal rates, stock, payments and reports.
- Language: PHP (PDO), MySQL (MariaDB/MySQL)

## Requirements

- PHP 7.4+ with PDO and common extensions (pdo_mysql, mbstring, json)
- MySQL 5.7+ / MariaDB
- Web server (Apache/Nginx) or PHP built-in server for development

## Quick start (local)

1. Import database schema:

```bash
mysql -u root -p < database/schema.sql
```

2. Update DB credentials in `config/database.php` and `BASE_URL` in `config/constants.php` when needed.

3. Start dev server:

```bash
cd /path/to/jewellery
php -S 127.0.0.1:8000
```

4. Open http://127.0.0.1:8000 (or http://localhost/jewellery for XAMPP).

## Default credentials (if present)

- Username: `admin`
- Password: `admin123`

## Important files & folders

- `index.php` — app entry (redirects to `login.php`)
- `config/` — DB and app config
- `database/schema.sql` — DB schema
- `includes/` — shared PHP includes (`functions.php`, `header.php`, `footer.php`)

## Troubleshooting notes

- Database connection issues: ensure MySQL is running and `config/database.php` matches credentials & host.
- Port conflicts: XAMPP and Homebrew MySQL both bind to 3306 — stop one before starting the other.

## Next steps

- Optionally import sample data from `database/` scripts.
- Adjust `BASE_URL` when deploying under a subdirectory.

---

If you want, I can import the schema and create a test admin user for you (provide DB credentials), or push the changes to GitHub for you.

## Next steps / suggestions

- Add an automated installer script that sets up the database and writes `config/database.php`.
- Add unit/integration tests and a small Dockerfile or Compose for reproducible local development.

## License & author

Repository: `omsoni21/jewellery` (owner: omsoni21). No license file is included — add one if you want to make the project open source.

> > > > > > > origin/main
