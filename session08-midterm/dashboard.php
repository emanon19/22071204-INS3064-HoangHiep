<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';

$pdo = null;
$dbError = null;

try {
    $pdo = Database::getInstance()->getConnection();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $dbError = 'Unable to connect to the database. Please check your configuration.';
}

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function redirectToDashboard(): never
{
    header('Location: dashboard.php');
    exit;
}

function getResourceData(): array
{
    return [
        'resource_type_id' => trim((string) ($_POST['resource_type_id'] ?? '')),
        'location_id'      => trim((string) ($_POST['location_id'] ?? '')),
        'name'             => trim((string) ($_POST['name'] ?? '')),
        'code'             => trim((string) ($_POST['code'] ?? '')),
        'description'      => trim((string) ($_POST['description'] ?? '')),
        'capacity'         => trim((string) ($_POST['capacity'] ?? '')),
        'status'           => trim((string) ($_POST['status'] ?? 'available')),
        'is_active'        => isset($_POST['is_active']) ? '1' : '0',
    ];
}

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare(
        'SELECT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = current_schema()
              AND table_name = :table_name
        )'
    );

    $stmt->execute([
        'table_name' => $tableName,
    ]);

    return (bool) $stmt->fetchColumn();
}

/*
|--------------------------------------------------------------------------
| Initialize page state
|--------------------------------------------------------------------------
*/

$errors = [];
$successMessage = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

$formData = [
    'resource_type_id' => '',
    'location_id'      => '',
    'name'             => '',
    'code'             => '',
    'description'      => '',
    'capacity'         => '',
    'status'           => 'available',
    'is_active'        => '1',
];

$editingId = null;
$formVisible = false;
$action = 'create';
$resources = [];

/*
|--------------------------------------------------------------------------
| Handle POST requests
|--------------------------------------------------------------------------
*/

if ($pdo !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedAction = (string) ($_POST['action'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | CREATE / UPDATE
    |--------------------------------------------------------------------------
    */

    if ($postedAction === 'create' || $postedAction === 'update') {
        $action = $postedAction;
        $formVisible = true;

        $formData = getResourceData();

        $idInput = trim((string) ($_POST['id'] ?? ''));

        if ($postedAction === 'update') {
            if ($idInput === '' || !ctype_digit($idInput) || (int) $idInput < 1) {
                $errors[] = 'Invalid resource ID.';
            } else {
                $editingId = (int) $idInput;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate required fields
        |--------------------------------------------------------------------------
        */

        if ($formData['resource_type_id'] === '') {
            $errors[] = 'Resource type ID is required.';
        } elseif (
            !ctype_digit($formData['resource_type_id']) ||
            (int) $formData['resource_type_id'] < 1
        ) {
            $errors[] = 'Resource type ID must be a positive integer.';
        }

        if ($formData['location_id'] === '') {
            $errors[] = 'Location ID is required.';
        } elseif (
            !ctype_digit($formData['location_id']) ||
            (int) $formData['location_id'] < 1
        ) {
            $errors[] = 'Location ID must be a positive integer.';
        }

        if ($formData['name'] === '') {
            $errors[] = 'Resource name is required.';
        } elseif (strlen($formData['name']) > 150) {
            $errors[] = 'Resource name must not exceed 150 characters.';
        }

        if ($formData['code'] === '') {
            $errors[] = 'Resource code is required.';
        } elseif (strlen($formData['code']) > 50) {
            $errors[] = 'Resource code must not exceed 50 characters.';
        }

        /*
        |--------------------------------------------------------------------------
        | Validate capacity
        |--------------------------------------------------------------------------
        */

        if ($formData['capacity'] !== '') {
            if (
                !ctype_digit($formData['capacity']) ||
                (int) $formData['capacity'] < 1
            ) {
                $errors[] = 'Capacity must be a positive integer.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate status
        |--------------------------------------------------------------------------
        */

        $allowedStatuses = [
            'available',
            'maintenance',
            'inactive',
        ];

        if (!in_array($formData['status'], $allowedStatuses, true)) {
            $errors[] = 'Please select a valid resource status.';
        }

        /*
        |--------------------------------------------------------------------------
        | Validate active checkbox
        |--------------------------------------------------------------------------
        */

        $formData['is_active'] =
            $formData['is_active'] === '1' ? '1' : '0';

        /*
        |--------------------------------------------------------------------------
        | Validate foreign-key references when lookup tables are available
        |--------------------------------------------------------------------------
        */

        if (
            $formData['resource_type_id'] !== '' &&
            ctype_digit($formData['resource_type_id']) &&
            (int) $formData['resource_type_id'] > 0
        ) {
            try {
                if (tableExists($pdo, 'resource_types')) {
                    $stmt = $pdo->prepare(
                        'SELECT id
                        FROM resource_types
                        WHERE id = :id'
                    );

                    $stmt->execute([
                        'id' => (int) $formData['resource_type_id'],
                    ]);

                    if (!$stmt->fetchColumn()) {
                        $errors[] = 'The selected resource type ID does not exist.';
                    }
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Unable to validate the resource type.';
            }
        }

        if (
            $formData['location_id'] !== '' &&
            ctype_digit($formData['location_id']) &&
            (int) $formData['location_id'] > 0
        ) {
            try {
                if (tableExists($pdo, 'locations')) {
                    $stmt = $pdo->prepare(
                        'SELECT id
                        FROM locations
                        WHERE id = :id'
                    );

                    $stmt->execute([
                        'id' => (int) $formData['location_id'],
                    ]);

                    if (!$stmt->fetchColumn()) {
                        $errors[] = 'The selected location ID does not exist.';
                    }
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Unable to validate the location.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate duplicate business code
        |--------------------------------------------------------------------------
        */

        if ($formData['code'] !== '' && strlen($formData['code']) <= 50) {
            try {
                if ($postedAction === 'create') {
                    $stmt = $pdo->prepare(
                        'SELECT id FROM resources WHERE code = :code'
                    );

                    $stmt->execute([
                        'code' => $formData['code'],
                    ]);
                } elseif ($editingId !== null) {
                    $stmt = $pdo->prepare(
                        'SELECT id
                         FROM resources
                         WHERE code = :code
                           AND id <> :id'
                    );

                    $stmt->execute([
                        'code' => $formData['code'],
                        'id' => $editingId,
                    ]);
                } else {
                    $stmt = false;
                }

                if ($stmt && $stmt->fetch()) {
                    $errors[] = 'This resource code already exists. Please use another code.';
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Unable to validate the resource code.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Save data if validation passes
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {
            try {
                if ($postedAction === 'create') {
                    $sql = '
                        INSERT INTO resources (
                            resource_type_id,
                            location_id,
                            name,
                            code,
                            description,
                            capacity,
                            status,
                            is_active,
                            updated_at
                        )
                        VALUES (
                            :resource_type_id,
                            :location_id,
                            :name,
                            :code,
                            :description,
                            :capacity,
                            :status,
                            :is_active,
                            CURRENT_TIMESTAMP
                        )
                    ';

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        'resource_type_id' => (int) $formData['resource_type_id'],
                        'location_id'      => (int) $formData['location_id'],
                        'name'             => $formData['name'],
                        'code'             => $formData['code'],
                        'description'      => $formData['description'] !== ''
                            ? $formData['description']
                            : null,
                        'capacity'         => $formData['capacity'] !== ''
                            ? (int) $formData['capacity']
                            : null,
                        'status'            => $formData['status'],
                        'is_active' => ($formData['is_active'] === '1') ? 'true' : 'false',
                    ]);

                    $_SESSION['success'] = 'Resource created successfully.';
                } else {
                    if ($editingId === null) {
                        throw new RuntimeException('Invalid resource ID.');
                    }

                    /*
                     * Confirm the resource exists before updating.
                     */

                    $checkStmt = $pdo->prepare(
                        'SELECT id FROM resources WHERE id = :id'
                    );

                    $checkStmt->execute([
                        'id' => $editingId,
                    ]);

                    if (!$checkStmt->fetch()) {
                        throw new RuntimeException(
                            'The resource you are trying to edit no longer exists.'
                        );
                    }

                    $sql = '
                        UPDATE resources
                        SET
                            resource_type_id = :resource_type_id,
                            location_id = :location_id,
                            name = :name,
                            code = :code,
                            description = :description,
                            capacity = :capacity,
                            status = :status,
                            is_active = :is_active,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ';

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        'resource_type_id' => (int) $formData['resource_type_id'],
                        'location_id'      => (int) $formData['location_id'],
                        'name'             => $formData['name'],
                        'code'             => $formData['code'],
                        'description'      => $formData['description'] !== ''
                            ? $formData['description']
                            : null,
                        'capacity'          => $formData['capacity'] !== ''
                            ? (int) $formData['capacity']
                            : null,
                        'status'            => $formData['status'],
                        'is_active' => ($formData['is_active'] === '1') ? 'true' : 'false',
                        'id'                => $editingId,
                    ]);

                    $_SESSION['success'] = 'Resource updated successfully.';
                }

                redirectToDashboard();
            } catch (PDOException $e) {
                error_log($e->getMessage());

                /*
                 * Handle duplicate code errors and other database errors.
                 */

                if ($e->getCode() === '23505') {
                    $errors[] = 'The resource code or location/name combination already exists.';
                } elseif ($e->getCode() === '23503') {
                    $errors[] = 'The selected resource type or location does not exist.';
                } else {
                    $errors[] = 'Unable to save the resource. Please check your input and try again.';
                }
            } catch (Throwable $e) {
                error_log($e->getMessage());
                $errors[] = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'An unexpected error occurred. Please try again.';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    elseif ($postedAction === 'delete') {
        $deleteId = trim((string) ($_POST['id'] ?? ''));

        if ($deleteId === '' || !ctype_digit($deleteId) || (int) $deleteId < 1) {
            $_SESSION['success'] = '';
            $_SESSION['error'] = 'Invalid resource ID.';
            redirectToDashboard();
        }

        try {
            $stmt = $pdo->prepare(
                'DELETE FROM resources WHERE id = :id'
            );

            $stmt->execute([
                'id' => (int) $deleteId,
            ]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = 'Resource deleted successfully.';
            } else {
                $_SESSION['error'] = 'Resource not found.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $sqlState = $e->errorInfo[0] ?? $e->getCode();

            if ($sqlState === '23001') {
                $_SESSION['error'] =
                    'This resource cannot be deleted because it is referenced by existing bookings.';
            } else {
                $_SESSION['error'] =
                    'Unable to delete the resource. Please try again.';
            }
        }

        redirectToDashboard();
    }
}

/*
|--------------------------------------------------------------------------
| Display one-time error messages
|--------------------------------------------------------------------------
*/

$errorMessage = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

/*
|--------------------------------------------------------------------------
| Handle GET requests for editing
|--------------------------------------------------------------------------
*/

if (
    $pdo !== null &&
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['edit'])
) {
    $editId = trim((string) $_GET['edit']);

    if ($editId === '' || !ctype_digit($editId) || (int) $editId < 1) {
        $errorMessage = 'Invalid resource ID.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT *
                 FROM resources
                 WHERE id = :id'
            );

            $stmt->execute([
                'id' => (int) $editId,
            ]);

            $resource = $stmt->fetch();

            if ($resource) {
                $editingId = (int) $resource['id'];
                $action = 'update';
                $formVisible = true;

                $formData = [
                    'resource_type_id' => (string) $resource['resource_type_id'],
                    'location_id'      => (string) $resource['location_id'],
                    'name'             => (string) $resource['name'],
                    'code'             => (string) $resource['code'],
                    'description'      => (string) ($resource['description'] ?? ''),
                    'capacity'         => $resource['capacity'] !== null
                        ? (string) $resource['capacity']
                        : '',
                    'status'            => (string) $resource['status'],
                    'is_active'         => $resource['is_active'] ? '1' : '0',
                ];
            } else {
                $errorMessage = 'Resource not found.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errorMessage = 'Unable to load the resource for editing.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Dashboard statistics
|--------------------------------------------------------------------------
*/

$totalResources = 0;
$totalBookings = 0;
$availableResources = 0;

if ($pdo !== null) {
    try {
        $stmt = $pdo->query("
            SELECT
                COUNT(*) AS total_resources,
                COUNT(*) FILTER (
                    WHERE status = 'available'
                      AND is_active = TRUE
                ) AS available_resources
            FROM resources
        ");

        $stats = $stmt->fetch();

        $totalResources = (int) $stats['total_resources'];
        $availableResources = (int) $stats['available_resources'];

        $stmt = $pdo->query("
            SELECT COUNT(*) AS total_bookings
            FROM bookings
        ");

        $bookingStats = $stmt->fetch();

        $totalBookings = (int) $bookingStats['total_bookings'];

    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| Read all resources
|--------------------------------------------------------------------------
*/

if ($pdo !== null) {
    try {
        $stmt = $pdo->query(
            'SELECT *
             FROM resources
             ORDER BY id DESC'
        );

        $resources = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $errorMessage = 'Unable to load resources. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Campus Booking System - Resources</title>

    <style>
        :root {
            --primary: #2457a7;
            --primary-dark: #193f7b;
            --background: #f4f6fa;
            --surface: #ffffff;
            --text: #263247;
            --muted: #667085;
            --border: #dfe4ec;
            --success-bg: #e7f7ed;
            --success-text: #176b3a;
            --error-bg: #fff0f0;
            --error-text: #a61b1b;
            --warning-bg: #fff6df;
            --warning-text: #855b00;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.5;
        }

        header {
            background: var(--primary);
            color: white;
            padding: 24px 20px;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        header h1 {
            margin: 0 0 6px;
            font-size: 26px;
        }

        header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        main {
            max-width: 1400px;
            margin: 28px auto;
            padding: 0 20px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 22px;
        }

        .page-heading h2 {
            margin: 0;
            font-size: 24px;
        }

        .page-heading p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        .card h3 {
            margin: 0 0 20px;
            font-size: 19px;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border: 0;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: #e9edf4;
            color: var(--text);
        }

        .btn-secondary:hover {
            background: #dce3ee;
        }

        .btn-edit {
            background: #e5efff;
            color: #1c4f9c;
            padding: 7px 11px;
        }

        .btn-edit:hover {
            background: #d2e2ff;
        }

        .btn-delete {
            background: #ffebeb;
            color: #b42318;
            padding: 7px 11px;
        }

        .btn-delete:hover {
            background: #ffd8d8;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: 600;
        }

        .required {
            color: #c62828;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd3df;
            border-radius: 6px;
            font: inherit;
            font-size: 14px;
            color: var(--text);
            background: white;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: 2px solid #b7d0ff;
            border-color: var(--primary);
        }

        textarea {
            min-height: 95px;
            resize: vertical;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }

        .checkbox-row input {
            width: 17px;
            height: 17px;
        }

        .checkbox-row label {
            font-weight: normal;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 22px;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .alert-warning {
            background: var(--warning-bg);
            color: var(--warning-text);
        }

        .alert ul {
            margin: 8px 0 0;
            padding-left: 22px;
        }

        .table-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 15px;
            color: var(--muted);
            font-size: 14px;
        }

        .table-wrapper {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 1050px;
        }

        th,
        td {
            padding: 13px 12px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f8f9fc;
            color: #475467;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #fafbfe;
        }

        .resource-name {
            font-weight: 600;
        }

        .resource-code {
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-available {
            background: #e7f7ed;
            color: #176b3a;
        }

        .status-maintenance {
            background: #fff2d5;
            color: #855b00;
        }

        .status-inactive {
            background: #f0f0f0;
            color: #555;
        }

        .badge-active {
            background: #e7f7ed;
            color: #176b3a;
        }

        .badge-inactive {
            background: #f0f0f0;
            color: #555;
        }

        .actions {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        .empty-state {
            text-align: center;
            color: var(--muted);
            padding: 35px 15px;
        }

        footer {
            max-width: 1400px;
            margin: 35px auto 0;
            padding: 20px;
            color: var(--muted);
            text-align: center;
            font-size: 13px;
        }

        /* Dashboard statistics */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #dfe4ec;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        .stat-card h3 {
            margin: 0 0 10px;
            color: #667085;
            font-size: 14px;
            font-weight: 600;
        }

        .stat-number {
            margin: 0;
            color: #2457a7;
            font-size: 30px;
            font-weight: 700;
        }

        .stat-description {
            margin: 8px 0 0;
            color: #667085;
            font-size: 13px;
        }

        @media (max-width: 700px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                padding: 18px;
            }
        }

        @media (max-width: 700px) {
            header {
                padding: 20px 16px;
            }

            header h1 {
                font-size: 22px;
            }

            main {
                padding: 0 12px;
                margin-top: 18px;
            }

            .page-heading {
                align-items: stretch;
                flex-direction: column;
            }

            .page-heading .btn {
                width: 100%;
            }

            .card {
                padding: 16px;
                border-radius: 8px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-group.full-width {
                grid-column: auto;
            }

            .form-actions .btn {
                flex: 1;
            }

            .table-summary {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="header-content">
        <h1>Campus Booking System</h1>
        <p>Resource Management Dashboard</p>
    </div>
</header>

<main>

    <div class="page-heading">
        <div>
            <h2>Resources</h2>
            <p>Manage campus rooms, laboratories, study spaces, and facilities.</p>
        </div>

        <?php if (!$formVisible && $pdo !== null): ?>
            <a
                class="btn btn-primary"
                href="dashboard.php?add=1"
            >
                + Add Resource
            </a>
        <?php endif; ?>
    </div>

    <!-- Dashboard Statistics -->

    <section class="stats-grid">

        <div class="stat-card">
            <h3>Total Resources</h3>

            <p class="stat-number">
                <?= e($totalResources) ?>
            </p>

            <p class="stat-description">
                All registered campus resources
            </p>
        </div>

        <div class="stat-card">
            <h3>Available Resources</h3>

            <p class="stat-number">
                <?= e($availableResources) ?>
            </p>

            <p class="stat-description">
                Active resources available for booking
            </p>
        </div>

        <div class="stat-card">
            <h3>Total Bookings</h3>

            <p class="stat-number">
                <?= e($totalBookings) ?>
            </p>

            <p class="stat-description">
                All booking records
            </p>
        </div>

    </section>

    <?php if ($dbError !== null): ?>
        <div class="alert alert-error">
            <?= e($dbError) ?>
        </div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
        <div class="alert alert-success">
            <?= e($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-error">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Please correct the following errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php
    /*
     * Display the create form when ?add=1 is present.
     */
    if (
        isset($_GET['add']) &&
        $_SERVER['REQUEST_METHOD'] === 'GET'
    ) {
        $formVisible = true;
        $action = 'create';
    }
    ?>

    <?php if ($formVisible && $pdo !== null): ?>

        <section class="card" id="resource-form">

            <h3>
                <?= $action === 'update' ? 'Edit Resource' : 'Add New Resource' ?>
            </h3>

            <form
                method="POST"
                action="dashboard.php"
                autocomplete="off"
            >

                <input
                    type="hidden"
                    name="action"
                    value="<?= e($action) ?>"
                >

                <?php if ($action === 'update' && $editingId !== null): ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?= e($editingId) ?>"
                    >
                <?php endif; ?>

                <div class="form-grid">

                    <div class="form-group">
                        <label for="resource_type_id">
                            Resource Type ID
                            <span class="required">*</span>
                        </label>

                        <input
                            type="number"
                            id="resource_type_id"
                            name="resource_type_id"
                            min="1"
                            step="1"
                            required
                            value="<?= e($formData['resource_type_id']) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="location_id">
                            Location ID
                            <span class="required">*</span>
                        </label>

                        <input
                            type="number"
                            id="location_id"
                            name="location_id"
                            min="1"
                            step="1"
                            required
                            value="<?= e($formData['location_id']) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="name">
                            Resource Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="150"
                            required
                            value="<?= e($formData['name']) ?>"
                            placeholder="e.g. Computer Lab A"
                        >
                    </div>

                    <div class="form-group">
                        <label for="code">
                            Resource Code
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="code"
                            name="code"
                            maxlength="50"
                            required
                            value="<?= e($formData['code']) ?>"
                            placeholder="e.g. LAB-001"
                        >
                    </div>

                    <div class="form-group">
                        <label for="capacity">
                            Capacity
                        </label>

                        <input
                            type="number"
                            id="capacity"
                            name="capacity"
                            min="1"
                            step="1"
                            value="<?= e($formData['capacity']) ?>"
                            placeholder="e.g. 40"
                        >
                    </div>

                    <div class="form-group">
                        <label for="status">
                            Resource Status
                            <span class="required">*</span>
                        </label>

                        <select
                            id="status"
                            name="status"
                            required
                        >
                            <option
                                value="available"
                                <?= $formData['status'] === 'available' ? 'selected' : '' ?>
                            >
                                Available
                            </option>

                            <option
                                value="maintenance"
                                <?= $formData['status'] === 'maintenance' ? 'selected' : '' ?>
                            >
                                Maintenance
                            </option>

                            <option
                                value="inactive"
                                <?= $formData['status'] === 'inactive' ? 'selected' : '' ?>
                            >
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            placeholder="Describe the resource and its facilities."
                        ><?= e($formData['description']) ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <div class="checkbox-row">
                            <input
                                type="checkbox"
                                id="is_active"
                                name="is_active"
                                value="1"
                                <?= $formData['is_active'] === '1' ? 'checked' : '' ?>
                            >

                            <label for="is_active">
                                Resource is active
                            </label>
                        </div>
                    </div>

                </div>

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <?= $action === 'update' ? 'Save Changes' : 'Create Resource' ?>
                    </button>

                    <a
                        href="dashboard.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </section>

    <?php endif; ?>

    <section class="card">

        <h3>All Resources</h3>

        <div class="table-summary">
            <span>
                Total resources:
                <strong><?= count($resources) ?></strong>
            </span>

            <span>
                Use the action buttons to edit or delete a resource.
            </span>
        </div>

        <?php if (empty($resources)): ?>

            <div class="empty-state">
                <p>No resources found.</p>

                <?php if ($pdo !== null): ?>
                    <a
                        href="dashboard.php?add=1"
                        class="btn btn-primary"
                    >
                        Add Your First Resource
                    </a>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Resource</th>
                            <th>Type ID</th>
                            <th>Location ID</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Active</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($resources as $resource): ?>

                            <tr>

                                <td><?= e($resource['id']) ?></td>

                                <td>
                                    <div class="resource-name">
                                        <?= e($resource['name']) ?>
                                    </div>

                                    <div class="resource-code">
                                        <?= e($resource['code']) ?>
                                    </div>
                                </td>

                                <td><?= e($resource['resource_type_id']) ?></td>

                                <td><?= e($resource['location_id']) ?></td>

                                <td>
                                    <?= $resource['capacity'] !== null
                                        ? e($resource['capacity'])
                                        : '—' ?>
                                </td>

                                <td>
                                    <?php
                                    $statusClass = match ($resource['status']) {
                                        'available' => 'status-available',
                                        'maintenance' => 'status-maintenance',
                                        default => 'status-inactive',
                                    };
                                    ?>

                                    <span class="badge <?= e($statusClass) ?>">
                                        <?= e(ucfirst($resource['status'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge <?= $resource['is_active']
                                        ? 'badge-active'
                                        : 'badge-inactive' ?>">

                                        <?= $resource['is_active']
                                            ? 'Yes'
                                            : 'No' ?>

                                    </span>
                                </td>

                                <td>
                                    <?= e($resource['created_at']) ?>
                                </td>

                                <td>
                                    <div class="actions">

                                        <a
                                            href="dashboard.php?edit=<?= e($resource['id']) ?>"
                                            class="btn btn-edit"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="dashboard.php"
                                            onsubmit="return confirm('Are you sure you want to delete this resource?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= e($resource['id']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-delete"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

<footer>
    Campus Booking System &mdash; Resource Management
</footer>

</body>
</html>