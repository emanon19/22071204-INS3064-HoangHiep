# Contact Manager

A PHP Contact Manager application refactored using
Object-Oriented Programming (OOP) and MVC architecture.

## Technologies

- PHP 8.2+
- PostgreSQL
- PDO
- HTML5
- CSS3

## Project Structure

contact_manager/
├── public/
│   ├── index.php
│   └── css/
│       └── style.css
├── app/
│   ├── core/
│   │   ├── Database.php
│   │   └── Router.php
│   ├── controllers/
│   │   └── ContactController.php
│   ├── models/
│   │   ├── Model.php
│   │   └── ContactModel.php
│   └── views/
│       ├── layout.php
│       └── contacts/
│           ├── index.php
│           ├── create.php
│           └── edit.php
├── config/
│   └── database.php
└── README.md

## Database Setup

1. Create a PostgreSQL database named `contact_manager`.
2. Open PostgreSQL Query Tool.
3. Execute the supplied database.sql script.
4. Configure database credentials in config/database.php.

## Running the Application

1. Place the project inside your XAMPP htdocs directory.
2. Enable Apache and PostgreSQL.
3. Make sure the PHP PDO PostgreSQL extension (pdo_pgsql) is enabled.
4. Open:

   http://localhost/contact_manager/public/

## Application Routes

| Method | Route | Description |
|--------|-------|-------------|
| GET | / | Display contacts |
| GET | /create | Display create form |
| POST | /store | Save new contact |
| GET | /edit?id=1 | Display edit form |
| POST | /update | Update contact |
| POST | /delete?id=1 | Delete contact |

Routes are dispatched through public/index.php
using the Router class.

## Architecture

### Database

Implements the Singleton design pattern to
maintain one PDO connection per request.

### Model

Abstract base class providing the shared
database connection to child models.

### ContactModel

Handles contact database operations:
- getAll()
- findById()
- emailExists()
- create()
- update()
- delete()
- count()

All database operations use PDO prepared statements.

### ContactController

Handles HTTP requests, input validation,
model interactions, and view rendering.

### Router

Maps HTTP methods and routes to controller methods.

### Views

Responsible for rendering contact lists,
forms, validation messages, and shared layouts.

### Autoloading

Uses spl_autoload_register() to automatically
load application classes based on namespaces.

## Security

- PDO prepared statements
- Server-side input validation
- HTML output escaping
- CSRF token verification
- POST requests for data modification
- Friendly database error messages