<?php

session_start();

$errors = [];

$fullName = '';
$email = '';
$age = '';
$gender = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get submitted values
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $age = trim($_POST['age'] ?? '');
    $gender = $_POST['gender'] ?? '';

    // Validate Full Name
    if ($fullName === '') {
        $errors['full_name'] = 'Full name is required.';
    }

    // Validate Email
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // Validate Password
    if (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }

    // Validate Confirm Password
    if ($confirmPassword === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($confirmPassword !== $password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // Validate Age
    if ($age === '') {
        $errors['age'] = 'Age is required.';
    } elseif (filter_var($age, FILTER_VALIDATE_INT) === false) {
        $errors['age'] = 'Age must be a valid number.';
    } elseif ((int) $age < 10 || (int) $age > 100) {
        $errors['age'] = 'Age must be between 10 and 100.';
    }

    // Validate Gender
    $validGenders = ['Male', 'Female', 'Other'];

    if (!in_array($gender, $validGenders, true)) {
        $errors['gender'] = 'Please select a gender.';
    }

    // If there are no errors, store data in session
    if (empty($errors)) {

        $_SESSION['registration'] = [
            'full_name' => $fullName,
            'email' => $email,
            'password' => $password,
            'age' => (int) $age,
            'gender' => $gender
        ];

        header('Location: survey.php');
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Registration Form</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h1>Registration Form</h1>

    <form method="POST" action="register.php">

        <!-- Full Name -->
        <div class="form-group">

            <label for="full_name">
                Full Name
            </label>

            <input
                type="text"
                id="full_name"
                name="full_name"
                value="<?= htmlspecialchars($fullName) ?>"
                required
            >

            <?php if (isset($errors['full_name'])): ?>
                <span class="error">
                    <?= htmlspecialchars($errors['full_name']) ?>
                </span>
            <?php endif; ?>

        </div>


        <!-- Email -->
        <div class="form-group">

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($email) ?>"
                required
            >

            <?php if (isset($errors['email'])): ?>
                <span class="error">
                    <?= htmlspecialchars($errors['email']) ?>
                </span>
            <?php endif; ?>

        </div>


        <!-- Password -->
        <div class="form-group">

            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="6"
                required
            >

            <?php if (isset($errors['password'])): ?>
                <span class="error">
                    <?= htmlspecialchars($errors['password']) ?>
                </span>
            <?php endif; ?>

        </div>


        <!-- Confirm Password -->
        <div class="form-group">

            <label for="confirm_password">
                Confirm Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required
            >

            <?php if (isset($errors['confirm_password'])): ?>
                <span class="error">
                    <?= htmlspecialchars($errors['confirm_password']) ?>
                </span>
            <?php endif; ?>

        </div>


        <!-- Age -->
        <div class="form-group">

            <label for="age">
                Age
            </label>

            <input
                type="number"
                id="age"
                name="age"
                min="10"
                max="100"
                value="<?= htmlspecialchars($age) ?>"
                required
            >

            <?php if (isset($errors['age'])): ?>
                <span class="error">
                    <?= htmlspecialchars($errors['age']) ?>
                </span>
            <?php endif; ?>

        </div>


        <!-- Gender -->
        <div class="form-group">

            <label>
                Gender
            </label>

            <div class="options">

                <label>
                    <input
                        type="radio"
                        name="gender"
                        value="Male"
                        <?= $gender === 'Male' ? 'checked' : '' ?>
                    >
                    Male
                </label>

                <label>
                    <input
                        type="radio"
                        name="gender"
                        value="Female"
                        <?= $gender === 'Female' ? 'checked' : '' ?>
                    >
                    Female
                </label>

                <label>
                    <input
                        type="radio"
                        name="gender"
                        value="Other"
                        <?= $gender === 'Other' ? 'checked' : '' ?>
                    >
                    Other
                </label>

            </div>

            <?php if (isset($errors['gender'])): ?>
                <span class="error">
                    <?= htmlspecialchars($errors['gender']) ?>
                </span>
            <?php endif; ?>

        </div>


        <button type="submit">
            Continue to Survey
        </button>

    </form>

</div>

</body>
</html>