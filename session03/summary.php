<?php
session_start();

if (
    !isset($_SESSION['registration']) ||
    !isset($_SESSION['survey'])
) {
    header('Location: register.php');
    exit;
}

$registration = $_SESSION['registration'];
$survey = $_SESSION['survey'];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_over'])) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: register.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Summary</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Registration Summary</h1>

    <div class="thank-you">
        <h2>
            Thank you, <?= e($registration['full_name']) ?>!
        </h2>

        <p>Your registration and survey have been completed successfully.</p>
    </div>

    <section class="summary-section">
        <h2>Registration Information</h2>

        <div class="summary-row">
            <strong>Full Name:</strong>
            <span><?= e($registration['full_name']) ?></span>
        </div>

        <div class="summary-row">
            <strong>Email:</strong>
            <span><?= e($registration['email']) ?></span>
        </div>

        <div class="summary-row">
            <strong>Age:</strong>
            <span><?= e((string)$registration['age']) ?></span>
        </div>

        <div class="summary-row">
            <strong>Gender:</strong>
            <span><?= e($registration['gender']) ?></span>
        </div>
    </section>

    <section class="summary-section">
        <h2>Survey Information</h2>

        <div class="summary-row">
            <strong>Favorite Programming Language:</strong>
            <span><?= e($survey['language']) ?></span>
        </div>

        <div class="summary-row">
            <strong>Experience Level:</strong>
            <span><?= e($survey['experience']) ?></span>
        </div>

        <div class="summary-row">
            <strong>Interests:</strong>

            <ul>
                <?php foreach ($survey['interests'] as $interest): ?>
                    <li><?= e($interest) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <form method="POST" action="summary.php">
        <button
            type="submit"
            name="start_over"
            value="1"
            class="secondary-button"
        >
            Start Over
        </button>
    </form>
</div>

</body>
</html>