<?php
session_start();

if (!isset($_SESSION['registration'])) {
    header('Location: register.php');
    exit;
}

$errors = [];

$language = '';
$experience = '';
$interests = [];

$validLanguages = [
    'PHP',
    'JavaScript',
    'Python',
    'Java',
    'C++',
    'Other'
];

$validExperiences = [
    'Beginner',
    'Intermediate',
    'Advanced'
];

$validInterests = [
    'Web Development',
    'Mobile Apps',
    'Data Science',
    'Cybersecurity',
    'AI/ML',
    'Game Development'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $language = $_POST['language'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $interests = $_POST['interests'] ?? [];

    // Language
    if (!in_array($language, $validLanguages, true)) {
        $errors['language'] = 'Please select a valid programming language.';
    }

    // Experience
    if (!in_array($experience, $validExperiences, true)) {
        $errors['experience'] = 'Please select your experience level.';
    }

    // Interests
    if (!is_array($interests)) {
        $interests = [];
    }

    $interests = array_values(
        array_intersect($interests, $validInterests)
    );

    if (count($interests) === 0) {
        $errors['interests'] = 'Please select at least one interest.';
    }

    if (empty($errors)) {
        $_SESSION['survey'] = [
            'language' => $language,
            'experience' => $experience,
            'interests' => $interests
        ];

        header('Location: summary.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programming Survey</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Programming Survey</h1>

    <form method="POST" action="survey.php">

        <div class="form-group">
            <label for="language">
                Favorite Programming Language
            </label>

            <select id="language" name="language" required>
                <option value="">-- Select a language --</option>

                <?php foreach ($validLanguages as $item): ?>
                    <option
                        value="<?= htmlspecialchars($item) ?>"
                        <?= $language === $item ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($item) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (isset($errors['language'])): ?>
                <span class="error">
                    <?= htmlspecialchars($errors['language']) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Experience Level</label>

            <div class="options">
                <?php foreach ($validExperiences as $item): ?>
                    <label>
                        <input
                            type="radio"
                            name="experience"
                            value="<?= htmlspecialchars($item) ?>"
                            <?= $experience === $item ? 'checked' : '' ?>
                        >
                        <?= htmlspecialchars($item) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php if (isset($errors['experience'])): ?>
                <span class="error">
                    <?= htmlspecialchars($errors['experience']) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Interests</label>

            <div class="options">
                <?php foreach ($validInterests as $item): ?>
                    <label>
                        <input
                            type="checkbox"
                            name="interests[]"
                            value="<?= htmlspecialchars($item) ?>"
                            <?= in_array($item, $interests, true)
                                ? 'checked'
                                : '' ?>
                        >
                        <?= htmlspecialchars($item) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php if (isset($errors['interests'])): ?>
                <span class="error">
                    <?= htmlspecialchars($errors['interests']) ?>
                </span>
            <?php endif; ?>
        </div>

        <button type="submit">View Summary</button>

    </form>
</div>

</body>
</html>