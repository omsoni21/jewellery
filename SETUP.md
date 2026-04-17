# JewelSync ERP

## Jewellery Billing & Inventory Management System

## Setup Instructions

### Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PDO PHP Extension

### Installation Steps

1. **Database Setup**
   - Create a MySQL database named `jewellery_billing`
   - Import the database schema from `database/schema.sql`
   - Update database credentials in `config/database.php` if needed

2. **Web Server Configuration**
   - Point your web server document root to the project folder
   - Ensure `.htaccess` is working (for Apache)
   - Enable `mod_rewrite` for Apache

3. **Default Login Credentials**
   - Username: `admin`
   - Password: `admin123`

4. **First Time Setup**
   - Login with default credentials
   - Go to Settings > Company and update your company details
   - Go to Settings > Metal Rates and set today's rates
   - Create additional users as needed

### Folder Structure

```
/config/         - Configuration files
/includes/       - Common includes (header, footer, functions)
/assets/         - CSS, JS, images
/database/       - Database schema
/ajax/           - AJAX endpoints
/customers/      - Customer management
/billing/        - Invoice management
/payments/       - Payments and ledger
/inventory/      - Stock management
/reports/        - Reports
/settings/       - System settings
```

### User Roles

- **Admin**: Full access to all features
- **Billing**: Can create invoices and manage customers
- **Accountant**: Can manage inventory, payments, and view reports

### Security Notes

- Change default admin password immediately after first login
- Keep database credentials secure
- Regularly backup your database
- Keep PHP and MySQL updated

### Support

For issues or questions, please contact the developer.
