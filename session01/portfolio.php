<?php

// Personal information
$fullName = "Hoang Hiep";
$studentId = "22071204";
$bio = "I am a Computer Science student interested in software development and data-driven applications. I enjoy building practical projects and continuously improving my programming skills.";

$profilePhoto = "profile.jpg";

// Education history
$education = [
    [
        "school" => "VNU International School",
        "degree" => "Bachelor of Science in Computer Science",
        "period" => "2022 - Present"
    ],
    [
        "school" => "High School",
        "degree" => "High School Diploma",
        "period" => "2019 - 2022"
    ]
];

// Technical skills
$skills = [
    ["name" => "Python", "level" => "Advanced"],
    ["name" => "C/C++", "level" => "Advanced"],
    ["name" => "PHP", "level" => "Intermediate"],
    ["name" => "SQL", "level" => "Intermediate"],
    ["name" => "Git", "level" => "Intermediate"]
];

// Hobbies and interests
$hobbies = [
    "Programming and software development",
    "Artificial Intelligence and Machine Learning",
    "Reading and learning new technologies"
];

// Example of PHP string concatenation
$welcomeMessage = "Welcome to " . $fullName . "'s personal portfolio.";

// Example of PHP variable interpolation
$profileTitle = "About " . $fullName;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $fullName ?> - Personal Portfolio</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f7fb;
            color: #333;
            line-height: 1.6;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
        }

        header {
            background-color: #2563eb;
            color: white;
            text-align: center;
            padding: 40px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        header h1 {
            margin-bottom: 10px;
            font-size: 2.2rem;
        }

        header p {
            font-size: 1.05rem;
        }

        .profile {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .photo {
            width: 150px;
            height: 150px;
            background-color: #dbeafe;
            border: 3px solid #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #2563eb;
            font-weight: bold;
            flex-shrink: 0;
        }

        .card {
            background-color: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        h2 {
            color: #2563eb;
            margin-bottom: 15px;
            border-bottom: 2px solid #dbeafe;
            padding-bottom: 8px;
        }

        h3 {
            color: #1e40af;
            margin-bottom: 5px;
        }

        .education-item {
            margin-bottom: 18px;
        }

        .education-item:last-child {
            margin-bottom: 0;
        }

        .period {
            color: #666;
            font-size: 0.9rem;
        }

        .skills {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .skill {
            background-color: #eff6ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }

        .skill-level {
            color: #555;
            font-size: 0.9rem;
        }

        ul {
            padding-left: 20px;
        }

        li {
            margin-bottom: 8px;
        }

        footer {
            text-align: center;
            color: #666;
            margin-top: 30px;
            padding: 20px;
            font-size: 0.9rem;
        }

        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        @media (max-width: 600px) {
            .profile {
                flex-direction: column;
                text-align: center;
            }

            header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <header>
        <h1><?= $fullName ?></h1>
        <p><?= $welcomeMessage ?></p>
    </header>

    <!-- Personal Information -->
    <section class="card">
        <h2><?= $profileTitle ?></h2>

        <div class="profile">
            <div class="photo">
                <img src="<?= $profilePhoto ?>" alt="Profile photo">
            </div>

            <div>
                <p><strong>Full Name:</strong> <?= $fullName ?></p>
                <p><strong>Student ID:</strong> <?= $studentId ?></p>
                <p><strong>Bio:</strong> <?= $bio ?></p>
            </div>
        </div>
    </section>

    <!-- Education -->
    <section class="card">
        <h2>Education</h2>

        <?php foreach ($education as $item): ?>
            <div class="education-item">
                <h3><?= $item["school"] ?></h3>
                <p><?= $item["degree"] ?></p>
                <p class="period"><?= $item["period"] ?></p>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Technical Skills -->
    <section class="card">
        <h2>Technical Skills</h2>

        <div class="skills">
            <?php foreach ($skills as $skill): ?>
                <div class="skill">
                    <strong><?= $skill["name"] ?></strong>
                    <div class="skill-level">
                        Proficiency: <?= $skill["level"] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Hobbies -->
    <section class="card">
        <h2>Hobbies & Interests</h2>

        <ul>
            <?php foreach ($hobbies as $hobby): ?>
                <li><?= $hobby ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <footer>
        Last updated: <?= date("d M Y, H:i") ?>
    </footer>

</div>

</body>
</html>