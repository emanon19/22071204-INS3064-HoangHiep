# Product Management System

A PHP and PostgreSQL web application for managing products and categories.

## 1. Technologies

- PHP 8.2
- PostgreSQL
- PDO
- HTML5
- CSS3

## 2. Features

### Category Management
- Create categories.
- View all categories.
- Edit categories.
- Delete categories.
- Display the number of products in each category.
- Preserve products when deleting a category by setting their category reference to NULL.

### Product Management
- Create products.
- View products.
- Edit products.
- Delete products.
- Upload JPEG, PNG, and GIF images.
- Maximum image size: 2 MB.
- Replace product images when editing.
- Search products by partial name.
- Filter products by category.
- Combine search and category filters.
- Paginate product results with 10 products per page.

### Application Features
- MVC architecture.
- PDO prepared statements.
- Input validation.
- HTML output escaping.
- CSRF protection.
- Flash messages.
- POST-Redirect-GET.
- Responsive interface.

## 3. Requirements

Install or enable:

- PHP 8.2 or newer
- PostgreSQL
- PHP PDO extension
- PHP pdo_pgsql extension
- PHP fileinfo extension
- PHP mbstring extension
- PHP file upload support

## 4. Installation

### Step 1: Place the project

Place the `product_app` directory inside your web server directory.

For XAMPP:

```text
C:\xampp\htdocs\product_app\