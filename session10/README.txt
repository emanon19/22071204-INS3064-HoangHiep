CONTACT MANAGER — SETUP

Requirements
- PHP 8.0+ (PHP 8.2 recommended)
- MySQL or MariaDB
- PDO MySQL extension enabled
- mbstring extension enabled

Run locally with XAMPP
1. Copy the contact_manager folder into htdocs.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open phpMyAdmin and import setup.sql, or run it in your MySQL client.
4. If your MySQL credentials differ from the defaults, update DB_HOST, DB_NAME,
   DB_USER, and DB_PASS in config.php.
5. Open http://localhost/contact_manager/index.php.

Files
- setup.sql                Creates the database/table and inserts five sample contacts.
- config.php               PDO connection, session, validation, CSRF, flash, layout helpers.
- index.php                Contact listing, count, and name/email search.
- add.php                  Create contact form and insert handling.
- edit.php                 Edit contact form and update handling.
- delete.php               GET confirmation; deletion happens only on POST.
- contact-form-fields.php  Shared form fields used by add.php and edit.php.
- style.css                Responsive styling.

Notes
- Email is optional because the SQL schema allows NULL, but if entered it must be valid
  and unique. This also permits multiple contacts without an email address.
- All SQL queries use prepared statements.
- Output values are escaped with htmlspecialchars().
- POST forms include a session-based CSRF token.
- The SQL UNIQUE constraint remains the final protection against duplicate emails.
