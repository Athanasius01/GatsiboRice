GATSIBO RICE CO. LTD - MANAGEMENT PORTAL (STARTER RELEASE)

REQUIREMENTS
- Windows + XAMPP
- PHP 8.2+ with PDO MySQL enabled
- MySQL/MariaDB

INSTALLATION
1. Extract the GatsiboRice folder into C:\xampp2\htdocs\
2. Open XAMPP Control Panel and start Apache and MySQL.
3. Visit http://localhost/phpmyadmin/
4. Import database/schema.sql (it creates a separate database named gatsibo_rice).
5. Visit http://localhost/GatsiboRice/create_admin.php
6. Create your administrator account.
7. Delete create_admin.php after the administrator has been created.
8. Sign in at http://localhost/GatsiboRice/login.php

DATABASE
Database: gatsibo_rice
Host: 127.0.0.1
User: root
Password: (blank, default XAMPP)
Timezone: Africa/Kigali

ROLES
admin: full access
hr: employees and attendance
store: products and stock movements
production: production records
sales: customers and sales
accountant: expenses and reports

IMPORTANT
This is a first runnable foundation, not yet a complete ERP or public online shop.
Before live business use, add HTTPS, automated backups, stronger approval workflows,
financial double-entry ledger, supplier/procurement module, payroll calculations,
tax/VAT configuration, delivery management, and independent security review.
Production records do not automatically change inventory; finished-goods receipts
must currently be entered through Stock movements.
