# Product Management System

A PHP 8.2 web application for managing products and categories, built with PostgreSQL and MVC architecture. The application includes CRUD operations, image uploads, pagination, search, session-based authentication, and security hardening.

## 1. Technology Stack

- **Backend:** PHP 8.2+
- **Database:** PostgreSQL
- **Web Server:** Apache (XAMPP)
- **Database Access:** PDO with PostgreSQL driver
- **Frontend:** HTML, CSS, Bootstrap
- **Architecture:** Model-View-Controller (MVC)

## 2. Requirements

- PHP 8.2 or later
- PostgreSQL
- Apache
- XAMPP
- PHP extensions:
  - PDO
  - PDO_PGSQL
  - Fileinfo

## 3. Installation

### Step 1: Set Up the Project

Clone or copy the project into the XAMPP `htdocs` directory.

Example:

```bash
C:\xampp\htdocs\ins3064\session12\product_app
```

### Step 2: Create the Database

Create the PostgreSQL database:

```sql
CREATE DATABASE product_management;
```

### Step 3: Configure Database Connection

Update `config/database.php` with your local PostgreSQL credentials.

Ensure the database name, username, password, host, and port match your PostgreSQL configuration.

### Step 4: Import the Database Schema

Connect to the `product_management` database and execute:

```text
sql/schema.sql
```

### Step 5: Configure Directories

Ensure the following directories exist and are writable by the Apache/PHP process:

- `public/uploads/` — stores uploaded product images.
- `storage/ratelimit/` — stores login rate-limiting data.

Do not make the entire project directory publicly writable.

### Step 6: Start the Services

Start Apache and PostgreSQL using XAMPP and your PostgreSQL service manager.

### Step 7: Open the Application

Visit:

```text
http://localhost/ins3064/session12/product_app/public/
```

## 4. Application Features

### Product Management

- Create new products.
- Edit existing products.
- Delete products.
- Upload product images.
- Display product information and images.
- Paginate product listings.
- Search and filter products.

### Category Management

- Create categories.
- Edit category information.
- Delete categories.
- Associate products with categories.

### User Authentication

- Register a new account.
- Log in using username or email.
- Log out securely.
- Maintain authentication using session-based authentication.
- Optional remember-me functionality.
- Protect product and category routes from unauthenticated access.

### User Account Management

- View user profile.
- Update email address.
- Change account password.

## 5. Application Routes

### Authentication and Account Routes

| Method | Route | Description |
|---|---|---|
| GET | `login` | Display login page |
| POST | `login` | Authenticate user |
| GET | `register` | Display registration page |
| POST | `register` | Create user account |
| POST | `logout` | Log out |
| GET | `profile` | Display user profile |
| POST | `profile/update-email` | Update email address |
| GET | `change-password` | Display password change form |
| POST | `change-password` | Update password |

### Product Routes

| Method | Route | Description |
|---|---|---|
| GET | `products` | Display product listing |
| GET | `products/create` | Display product creation form |
| POST | `products/store` | Create product |
| GET | `products/edit` | Display product editing form |
| POST | `products/update` | Update product |
| POST | `products/delete` | Delete product |

### Category Routes

| Method | Route | Description |
|---|---|---|
| GET | `categories` | Display category listing |
| GET | `categories/create` | Display category creation form |
| POST | `categories/store` | Create category |
| GET | `categories/edit` | Display category editing form |
| POST | `categories/update` | Update category |
| POST | `categories/delete` | Delete category |

All product and category routes require authentication.

## 6. Security Implementation

### 6.1 Cross-Site Request Forgery (CSRF) Protection

- Generate cryptographically secure CSRF tokens using `random_bytes()`.
- Include CSRF tokens in POST forms.
- Validate submitted tokens using `hash_equals()`.
- Reject requests with missing or invalid tokens.
- Return HTTP `403 Forbidden` for failed CSRF validation.
- Apply validation centrally to all POST routes.

### 6.2 Cross-Site Scripting (XSS) Protection

- Escape dynamic output before rendering it in HTML.
- Use `htmlspecialchars()` with `ENT_QUOTES`, `ENT_SUBSTITUTE`, and UTF-8 encoding.
- Use the shared `e()` helper to consistently escape output.
- Escape user-provided and database-derived values in application views.

### 6.3 SQL Injection Protection

- Use PDO prepared statements for database operations involving dynamic values.
- Bind user-provided values rather than concatenating them into SQL statements.
- Disable PDO emulated prepared statements using:

```php
PDO::ATTR_EMULATE_PREPARES => false
```

### 6.4 Secure Password Handling

- Hash passwords using `password_hash()` with `PASSWORD_DEFAULT`.
- Verify passwords using `password_verify()`.
- Never store plaintext passwords.
- Regenerate the session ID after successful authentication.

### 6.5 Session Security

- Configure session cookies as HttpOnly.
- Use the Secure cookie flag when HTTPS is enabled.
- Set SameSite to `Lax`.
- Apply session inactivity timeout.
- Regenerate session IDs after authentication.
- Destroy the session during logout.

### 6.6 Remember-Me Security

- Generate random authentication tokens.
- Store SHA-256 hashes of remember-me tokens in the database.
- Store the original token in an HttpOnly cookie.
- Validate tokens against their stored hashes.
- Rotate tokens after successful automatic authentication.
- Set an expiration period for remember-me cookies.

### 6.7 Login Rate Limiting

- Limit login attempts by client IP address.
- Allow a maximum of five failed attempts within a 15-minute window.
- Temporarily block further attempts after reaching the limit.
- Display a message indicating the remaining lockout time.
- Clear the failed-attempt record after successful authentication.
- Store rate-limiting data in `storage/ratelimit/`.

### 6.8 Secure File Uploads

- Allow only JPG, JPEG, PNG, and GIF image extensions.
- Validate actual MIME types using PHP Fileinfo.
- Verify uploaded image data using `getimagesize()`.
- Limit image uploads to 2 MB.
- Generate randomized filenames to avoid filename collisions.
- Reject invalid or unsupported uploads.
- Restrict script execution in the upload directory using `.htaccess`.

### 6.9 HTTP Security Headers

The application sends security-related HTTP response headers:

| Header | Purpose |
|---|---|
| `X-Content-Type-Options: nosniff` | Prevent MIME-type sniffing |
| `X-Frame-Options: DENY` | Prevent clickjacking through framing |
| `X-XSS-Protection: 1; mode=block` | Enable legacy browser XSS filtering |
| `Referrer-Policy: strict-origin-when-cross-origin` | Restrict referrer information |
| `Content-Security-Policy` | Restrict permitted content sources |

## 7. Security Configuration Notes

- Use HTTPS in production.
- Keep database credentials outside publicly accessible directories.
- Ensure `storage/ratelimit/` cannot be accessed directly from the web.
- Keep uploaded files restricted to supported image formats.
- Do not expose PHP errors or database credentials to end users.
- Review application logs when investigating unexpected errors.
- Use appropriate filesystem permissions for application storage.

## 8. Security Verification

Refer to [SECURITY_CHECKLIST.md](SECURITY_CHECKLIST.md) for the security audit checklist.

The checklist covers:

- CSRF protection
- XSS prevention
- SQL injection prevention
- Authentication and session security
- Login rate limiting
- Secure image uploads
- HTTP security headers
- Application configuration

## 9. Project Structure

```text
product_app/
├── app/
│   ├── controllers/
│   ├── core/
│   │   ├── Auth.php
│   │   ├── Csrf.php
│   │   ├── Database.php
│   │   ├── RateLimiter.php
│   │   ├── Router.php
│   │   ├── Security.php
│   │   └── helpers.php
│   ├── models/
│   └── views/
├── config/
│   └── database.php
├── public/
│   ├── assets/
│   ├── uploads/
│   │   └── .htaccess
│   ├── .htaccess
│   └── index.php
├── sql/
│   └── schema.sql
├── storage/
│   └── ratelimit/
├── SECURITY_CHECKLIST.md
└── README.md
```

## 10. License

This project was developed for educational purposes as part of the INS3064 coursework.