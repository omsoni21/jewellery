# Jewellery Billing & Inventory (PHP)

A small PHP-based billing, inventory and customer management application for a jewellery shop.

This repository contains the web application source, helper scripts for generating sample data, and database schema under `database/`.

## Requirements

- PHP 7.4+ (compatible with PHP 8)
- MySQL / MariaDB
- A web server (Apache via XAMPP/AMP stack) or the built-in PHP server for local testing

## Quick start

1. Copy the project into your web server document root (for XAMPP: `/Applications/XAMPP/htdocs/jewellery`) or use the PHP built-in server:

```bash
# from project root
php -S localhost:8000
```

2. Create a MySQL database and import the schema:

```bash
# adjust database name and user as needed
mysql -u root -p < database/schema.sql
```

3. Update configuration: open `config/database.php` (DB credentials) and `config/constants.php` (company constants, GST rates, etc.).

4. (Optional) Load sample data or helper scripts in the `database/` folder. Example scripts include `add_300_products.php`, `generate_250_customers.php`, and other data-generation utilities.

5. Open the app in your browser:

```
http://localhost:8000/index.php
```

If using XAMPP/Apache, navigate to the project path served by the webserver.

## Important files and folders

- `index.php` — application entry page
- `config/` — database and constant configuration (`database.php`, `constants.php`)
- `database/schema.sql` — SQL schema for creating required tables
- `ajax/` — AJAX endpoints used by the UI
- `assets/` — front-end CSS and JS
- `billing/`, `customers/`, `inventory/`, `payments/`, `reports/`, `settings/` — main app modules
- `database/` — data generation and utility scripts (use carefully)
- `includes/` — shared PHP includes (`header.php`, `footer.php`, `functions.php`, `gst_api.php`)

## Running common tasks

- Create a database backup or import sample data using the scripts in `database/`.
- To reset or inspect tables, use the existing `show_tables.php`, `check_db.php`, or `database/schema.sql`.

## Security & deployment notes

- Do not commit real credentials. Keep `config/database.php` out of public repositories in production.
- Review `includes/functions.php` and other input-handling code for SQL injection and XSS before deploying.
- If deploying publicly, serve over HTTPS and secure database access.

## Troubleshooting

- Blank page or PHP errors: enable error display in `php.ini` or check server/PHP error logs.
- Database connection errors: verify credentials in `config/database.php` and ensure MySQL is running.

## Next steps / suggestions

- Add an automated installer script that sets up the database and writes `config/database.php`.
- Add unit/integration tests and a small Dockerfile or Compose for reproducible local development.

## License & author

Repository: `omsoni21/jewellery` (owner: omsoni21). No license file is included — add one if you want to make the project open source.
