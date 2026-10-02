<?php

declare(strict_types=1);

/**
 * Student Grade Calculator
 */

/**
 * Calculate the average score.
 */
function calculateAverage(array $scores): float
{
    return round(array_sum($scores) / count($scores), 1);
}

/**
 * Assign a letter grade based on the average score.
 */
function assignGrade(float $average): string
{
    if ($average >= 90) {
        return 'A';
    } elseif ($average >= 80) {
        return 'B';
    } elseif ($average >= 70) {
        return 'C';
    } elseif ($average >= 60) {
        return 'D';
    } else {
        return 'F';
    }
}

/**
 * Return a CSS class for a letter grade.
 */
function getGradeClass(string $grade): string
{
    switch ($grade) {
        case 'A':
            return 'grade-a';
        case 'B':
            return 'grade-b';
        case 'C':
            return 'grade-c';
        case 'D':
            return 'grade-d';
        default:
            return 'grade-f';
    }
}


/*
 * Student data
 *
 * Each student is an associative array.
 * The scores value is an indexed array.
 */
$students = [
    [
        'name' => 'Alice Johnson',
        'scores' => [95, 92, 98, 96],
    ],
    [
        'name' => 'Bob Smith',
        'scores' => [85, 88, 82, 90],
    ],
    [
        'name' => 'Charlie Brown',
        'scores' => [75, 75, 75, 75],
    ],
    [
        'name' => 'Diana Wilson',
        'scores' => [0, 0, 0, 0],
    ],
    [
        'name' => 'Ethan Davis',
        'scores' => [100, 100, 100, 100],
    ],
];


/*
 * Calculate results for each student.
 */
$results = [];

foreach ($students as $student) {
    $scores = $student['scores'];

    $average = calculateAverage($scores);

    $results[] = [
        'name' => $student['name'],
        'scores' => $scores,
        'average' => $average,
        'highest' => max($scores),
        'lowest' => min($scores),
        'grade' => assignGrade($average),
    ];
}


/*
 * Sort students by average score in descending order.
 */
usort($results, function (array $a, array $b): int {
    return $b['average'] <=> $a['average'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Grade Calculator</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px 20px;
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
            text-align: center;
            color: #222;
        }

        .description {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #343a40;
            color: #ffffff;
        }

        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tbody tr:hover {
            background-color: #eef2f5;
        }

        .name {
            text-align: left;
            font-weight: bold;
        }

        .scores {
            white-space: nowrap;
        }

        .grade {
            font-weight: bold;
            font-size: 18px;
        }

        .grade-a {
            color: #198754;
        }

        .grade-b {
            color: #2e7d32;
        }

        .grade-c {
            color: #d68910;
        }

        .grade-d {
            color: #e67e22;
        }

        .grade-f {
            color: #dc3545;
        }

        .summary {
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            body {
                padding: 20px 10px;
            }

            .container {
                padding: 20px 10px;
            }

            th,
            td {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Student Grade Calculator</h1>

    <p class="description">
        Student results sorted by average score in descending order.
    </p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th>Scores</th>
                    <th>Average</th>
                    <th>Highest</th>
                    <th>Lowest</th>
                    <th>Grade</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($results as $index => $student): ?>
                    <tr>
                        <td>
                            <?= $index + 1 ?>
                        </td>

                        <td class="name">
                            <?= htmlspecialchars($student['name']) ?>
                        </td>

                        <td class="scores">
                            <?php
                            /*
                             * for loop requirement:
                             * Display each score individually.
                             */
                            for ($i = 0; $i < count($student['scores']); $i++):
                            ?>
                                <?= $student['scores'][$i] ?><?php
                                if ($i < count($student['scores']) - 1) {
                                    echo ', ';
                                }
                                ?>
                            <?php endfor; ?>
                        </td>

                        <td>
                            <?= number_format($student['average'], 1) ?>
                        </td>

                        <td>
                            <?= $student['highest'] ?>
                        </td>

                        <td>
                            <?= $student['lowest'] ?>
                        </td>

                        <td
                            class="grade <?= getGradeClass($student['grade']) ?>"
                        >
                            <?= $student['grade'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="summary">
        Total students: <?= count($results) ?>
    </p>

</div>

</body>
</html>