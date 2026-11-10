# Product Management System

A PHP 8.2 Product Management System with PostgreSQL, MVC architecture, CRUD operations, image uploads, pagination, search, and session-based authentication.

## Requirements

- PHP 8.2+
- PostgreSQL
- Apache (XAMPP)
- PHP extensions: PDO, PDO_PGSQL, Fileinfo

## Installation

1. Clone or copy the project into the XAMPP `htdocs` directory.

2. Create the PostgreSQL database:

   ```sql
   CREATE DATABASE product_management;
   ```

3. Update `config/database.php` with your PostgreSQL credentials.

4. Connect to the `product_management` database.

5. Execute `sql/schema.sql`.

6. Ensure the `public/uploads/` directory exists and is writable.

7. Start Apache and PostgreSQL.

8. Open:

   http://localhost/ins3064/session12/product_app/public/

## Authentication

The application supports:

- User registration
- Username/email login
- Secure password hashing
- Session-based authentication
- Remember-me cookies
- Automatic login using remembered tokens
- Logout
- Protected product and category routes
- User profile management
- Email updates
- Password changes
- CSRF protection
- Flash messages

## Authentication Routes

| Method | Route | Description |
|---|---|---|
| GET | login | Login page |
| POST | login | Authenticate user |
| GET | register | Registration page |
| POST | register | Create account |
| POST | logout | Logout |
| GET | profile | View profile |
| POST | profile/update-email | Update email |
| GET | change-password | Change password page |
| POST | change-password | Update password |

## Application Routes

| Method | Route | Description |
|---|---|---|
| GET | products | Product listing |
| GET | products/create | Create product form |
| POST | products/store | Save product |
| GET | products/edit | Edit product form |
| POST | products/update | Update product |
| POST | products/delete | Delete product |
| GET | categories | Category listing |
| GET | categories/create | Create category form |
| POST | categories/store | Save category |
| GET | categories/edit | Edit category form |
| POST | categories/update | Update category |
| POST | categories/delete | Delete category |

All product and category routes require authentication.

## Security

- PDO prepared statements
- `password_hash()` with `PASSWORD_DEFAULT`
- `password_verify()`
- `session_regenerate_id(true)`
- SHA-256 hashed remember-me tokens
- HttpOnly cookies
- Secure cookies over HTTPS
- SameSite=Lax
- CSRF tokens
- Output escaping with `htmlspecialchars()`