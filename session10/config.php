<?php
/**
 * Contact Manager configuration and shared helpers.
 */

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '5432';
const DB_NAME = 'contact_manager';
const DB_USER = 'postgres';
const DB_PASS = 'Astro2910';

const CONTACT_GROUPS = ['Family', 'Friends', 'Work', 'Other', 'General'];

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

try {
    $pdo = new PDO(
        'pgsql:host=' . DB_HOST
        . ';port=' . DB_PORT
        . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log('Contact Manager database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Unable to connect to the database. Check the database settings in config.php.');
}

/** Escape a value before rendering it in HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Store a one-time flash message. */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Retrieve and clear a one-time flash message. */
function get_flash(): ?array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $allowedTypes = ['success', 'error', 'warning'];
    if (!in_array($flash['type'] ?? '', $allowedTypes, true)) {
        $flash['type'] = 'warning';
    }

    return $flash;
}

/** Redirect and stop execution. */
function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

/** Create or retrieve a session CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** Validate a submitted CSRF token using a timing-safe comparison. */
function verify_csrf_token(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';

    return is_string($submitted)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submitted);
}

/** Normalize and validate contact form input. */
function validate_contact_input(array $input, PDO $pdo, ?int $currentId = null): array
{
    $values = [
        'name' => trim((string)($input['name'] ?? '')),
        'email' => trim((string)($input['email'] ?? '')),
        'phone' => trim((string)($input['phone'] ?? '')),
        'group_name' => trim((string)($input['group_name'] ?? 'General')),
        'notes' => trim((string)($input['notes'] ?? '')),
    ];
    $errors = [];

    if ($values['name'] === '') {
        $errors[] = 'Name is required.';
    } elseif (mb_strlen($values['name']) > 100) {
        $errors[] = 'Name must be 100 characters or fewer.';
    }

    if ($values['email'] !== '') {
        if (mb_strlen($values['email']) > 150 || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter a valid email address (maximum 150 characters).';
        } else {
            $sql = 'SELECT id FROM contacts WHERE email = :email';
            $params = [':email' => $values['email']];
            if ($currentId !== null) {
                $sql .= ' AND id <> :id';
                $params[':id'] = $currentId;
            }

            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ($statement->fetch()) {
                $errors[] = 'That email address is already used by another contact.';
            }
        }
    }

    if (mb_strlen($values['phone']) > 20) {
        $errors[] = 'Phone must be 20 characters or fewer.';
    }

    if (!in_array($values['group_name'], CONTACT_GROUPS, true)) {
        $errors[] = 'Choose a valid contact group.';
    }

    return [$values, $errors];
}

/** Render the shared page header and one-time flash message. */
function render_header(string $pageTitle): void
{
    $flash = get_flash();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($pageTitle) ?> | Contact Manager</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="index.php"><span class="brand-mark">C</span> Contact Manager</a>
            <nav aria-label="Main navigation">
                <a href="index.php">All contacts</a>
                <a class="nav-add" href="add.php">+ Add contact</a>
            </nav>
        </div>
    </header>
    <main class="container">
    <?php if ($flash !== null): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php
}

/** Render the shared page footer. */
function render_footer(): void
{
    ?>
    </main>
    <footer class="site-footer">Contact Manager <span>·</span> Keep your connections organized.</footer>
    </body>
    </html>
    <?php
}
