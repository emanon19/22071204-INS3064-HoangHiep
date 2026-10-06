/*
 * ============================================================
 * University Database Queries Assignment
 * Name: Hoang Hiep
 * Student ID: 22071204
 * Assignment: 05
 * Date: 2026-10-06
 * PostgreSQL
 * ============================================================
 *
 * Target database: university_db
 *
 * PostgreSQL note:
 * The original assignment mentions USE university_db;.
 * PostgreSQL does not support USE. This script therefore uses
 * the psql \c command to connect to the target database.
 *
 * If the script is executed in a SQL client that does not
 * support \c, connect to university_db before running it.
 * ============================================================
 */

\c university_db


/*
 * Query 1: Select All Students
 * Description: Retrieves all columns and records from the students table.
 * This demonstrates a basic SELECT query against one table.
 * Expected Output: All five students with their complete student information.
 */

SELECT *
FROM students;

/*
 * Expected Output:
 *
 * +------------+---------------+------------+------------+----------------------------+--------+---------------+------------------+---------------+
 * | student_id | student_code  | first_name | last_name  | email                      | gender | date_of_birth | enrollment_date  | department_id |
 * +------------+---------------+------------+------------+----------------------------+--------+---------------+------------------+---------------+
 * | 1          | SV2024001     | Hoang Long | Nguyen     | long.nguyen@student.edu    | Male   | 2006-03-15    | 2024-09-05       | 1             |
 * | 2          | SV2024002     | Minh Chau  | Tran       | chau.tran@student.edu      | Female | 2006-07-22    | 2024-09-05       | 1             |
 * | 3          | SV2024003     | Quang Minh | Le         | minh.le@student.edu        | Male   | 2005-11-10    | 2024-09-05       | 2             |
 * | 4          | SV2024004     | Thu Ha     | Pham       | ha.pham@student.edu        | Female | 2006-01-28    | 2024-09-05       | 3             |
 * | 5          | SV2024005     | Gia Hung   | Do         | hung.do@student.edu        | Male   | 2005-09-17    | 2024-09-05       | 4             |
 * +------------+---------------+------------+------------+----------------------------+--------+---------------+------------------+---------------+
 * (5 rows)
 */


/*
 * Query 2: Enrollments With Grade Above 3.00
 * Description: Retrieves enrollments where the student's grade points
 * are greater than 3.00. This demonstrates a WHERE comparison condition.
 * Expected Output: Enrollments with grade_points greater than 3.00,
 * excluding enrollments without a grade.
 */

SELECT
    enrollment_id,
    student_id,
    course_id,
    grade_points,
    grade_letter
FROM enrollments
WHERE grade_points > 3.00;

/*
 * Expected Output:
 *
 * +----------------+------------+-----------+--------------+--------------+
 * | enrollment_id  | student_id | course_id | grade_points  | grade_letter |
 * +----------------+------------+-----------+--------------+--------------+
 * | 1              | 1          | 1         | 3.70         | A            |
 * | 2              | 1          | 2         | 3.30         | B            |
 * | 3              | 2          | 1         | 3.90         | A            |
 * | 4              | 2          | 4         | 3.50         | A            |
 * | 5              | 3          | 3         | 3.20         | B            |
 * | 6              | 3          | 1         | 3.60         | A            |
 * | 7              | 4          | 4         | 3.80         | A            |
 * | 8              | 4          | 5         | 3.10         | B            |
 * | 9              | 5          | 5         | 3.40         | B            |
 * | 10             | 5          | 3         | 3.70         | A            |
 * +----------------+------------+-----------+--------------+--------------+
 * (10 rows)
 */


/*
 * Query 3: Computer Science Courses With At Least 3 Credits
 * Description: Retrieves courses from the Computer Science department
 * that have three or more credits. This demonstrates combining WHERE
 * conditions with AND.
 * Expected Output: The two Computer Science courses with at least
 * three credits.
 */

SELECT
    course_id,
    course_code,
    course_name,
    credits
FROM courses
WHERE department_id = 1
  AND credits >= 3;

/*
 * Expected Output:
 *
 * +-----------+-------------+----------------------------------+---------+
 * | course_id | course_code | course_name                      | credits |
 * +-----------+-------------+----------------------------------+---------+
 * | 1         | CS101       | Introduction to Computer Science | 3       |
 * | 2         | CS201       | Data Structures and Algorithms   | 4       |
 * +-----------+-------------+----------------------------------+---------+
 * (2 rows)
 */


/*
 * Query 4: Students Ordered By Last Name
 * Description: Retrieves students and sorts them alphabetically by
 * last name. This demonstrates the ORDER BY clause.
 * Expected Output: All students ordered alphabetically by last name.
 */

SELECT
    student_id,
    student_code,
    first_name,
    last_name
FROM students
ORDER BY last_name ASC;

/*
 * Expected Output:
 *
 * +------------+--------------+------------+-----------+
 * | student_id | student_code | first_name | last_name |
 * +------------+--------------+------------+-----------+
 * | 5          | SV2024005    | Gia Hung   | Do        |
 * | 3          | SV2024003    | Quang Minh | Le        |
 * | 1          | SV2024001    | Hoang Long | Nguyen    |
 * | 4          | SV2024004    | Thu Ha     | Pham      |
 * | 2          | SV2024002    | Minh Chau  | Tran      |
 * +------------+--------------+------------+-----------+
 * (5 rows)
 */


/*
 * Query 5: Top 3 Highest-Graded Enrollments
 * Description: Retrieves the three enrollments with the highest
 * grade points and sorts them from highest to lowest.
 * This demonstrates ORDER BY together with LIMIT.
 * Expected Output: The three enrollments with the highest grades.
 */

SELECT
    enrollment_id,
    student_id,
    course_id,
    grade_points
FROM enrollments
WHERE grade_points IS NOT NULL
ORDER BY grade_points DESC
LIMIT 3;

/*
 * Expected Output:
 *
 * +----------------+------------+-----------+--------------+
 * | enrollment_id  | student_id | course_id | grade_points  |
 * +----------------+------------+-----------+--------------+
 * | 3              | 2          | 1         | 3.90         |
 * | 7              | 4          | 4         | 3.80         |
 * | 1              | 1          | 1         | 3.70         |
 * +----------------+------------+-----------+--------------+
 * (3 rows)
 */


/*
 * Query 6: Courses Containing "Introduction"
 * Description: Finds courses whose names contain the word
 * "Introduction". This demonstrates pattern matching with LIKE.
 * Expected Output: Courses whose course_name contains "Introduction".
 */

SELECT
    course_id,
    course_code,
    course_name AS "Course Title"
FROM courses
WHERE course_name LIKE '%Introduction%';

/*
 * Expected Output:
 *
 * +-----------+-------------+----------------------------------+
 * | course_id | course_code | Course Title                     |
 * +-----------+-------------+----------------------------------+
 * | 1         | CS101       | Introduction to Computer Science |
 * +-----------+-------------+----------------------------------+
 * (1 row)
 */


/*
 * Query 7: Count Students By Department
 * Description: Counts the number of students belonging to each
 * department. This demonstrates the COUNT aggregate function,
 * JOIN, and GROUP BY.
 * Expected Output: Each department with its number of students.
 */

SELECT
    d.department_name AS "Department",
    COUNT(s.student_id) AS "Student Count"
FROM departments AS d
LEFT JOIN students AS s
    ON s.department_id = d.department_id
GROUP BY d.department_id, d.department_name
ORDER BY d.department_id;

/*
 * Expected Output:
 *
 * +------------------------+---------------+
 * | Department             | Student Count |
 * +------------------------+---------------+
 * | Computer Science       | 2             |
 * | Information Technology | 1             |
 * | Mathematics            | 1             |
 * | Business Administration| 1             |
 * | Electrical Engineering | 0             |
 * +------------------------+---------------+
 * (5 rows)
 */


/*
 * Query 8: Departments With At Least Two Enrolled Students
 * Description: Groups enrollment records by the student's department
 * and returns departments having at least two distinct enrolled students.
 * This demonstrates GROUP BY together with HAVING.
 * Expected Output: Departments with two or more distinct students
 * represented in the enrollment records.
 */

SELECT
    d.department_name AS "Department",
    COUNT(DISTINCT e.student_id) AS "Enrolled Students"
FROM departments AS d
JOIN students AS s
    ON s.department_id = d.department_id
JOIN enrollments AS e
    ON e.student_id = s.student_id
GROUP BY d.department_id, d.department_name
HAVING COUNT(DISTINCT e.student_id) >= 2
ORDER BY d.department_id;

/*
 * Expected Output:
 *
 * +------------------------+------------------+
 * | Department             | Enrolled Students|
 * +------------------------+------------------+
 * | Computer Science       | 2                |
 * +------------------------+------------------+
 * (1 row)
 */


/*
 * Query 9: Courses With Instructor Names
 * Description: Joins the courses and instructors tables to display
 * each course together with the first and last name of its instructor.
 * This demonstrates a two-table INNER JOIN.
 * Expected Output: Five courses with their assigned instructor names.
 */

SELECT
    c.course_code,
    c.course_name,
    i.first_name || ' ' || i.last_name AS "Instructor Name"
FROM courses AS c
JOIN instructors AS i
    ON c.instructor_id = i.instructor_id
ORDER BY c.course_id;

/*
 * Expected Output:
 *
 * +-------------+----------------------------------+----------------+
 * | course_code | course_name                      | Instructor Name|
 * +-------------+----------------------------------+----------------+
 * | CS101       | Introduction to Computer Science | Minh Anh Nguyen|
 * | CS201       | Data Structures and Algorithms   | Quang Huy Tran |
 * | IT202       | Database Systems                 | Thi Huong Le   |
 * | MATH201     | Discrete Mathematics             | Van Nam Pham   |
 * | BUS101      | Principles of Management         | Ngoc Lan Do    |
 * +-------------+----------------------------------+----------------+
 * (5 rows)
 */


/*
 * Query 10: Enrollment Details Across Multiple Tables
 * Description: Joins enrollments with students, courses, and semesters
 * to produce a complete enrollment report containing the student,
 * course, semester, and grade information.
 * This demonstrates a JOIN involving three or more tables.
 * Expected Output: All twelve enrollment records with student names,
 * course names, semester information, and grades.
 */

SELECT
    e.enrollment_id AS "Enrollment ID",
    s.first_name || ' ' || s.last_name AS "Student Name",
    c.course_code AS "Course Code",
    c.course_name AS "Course Name",
    se.semester_name AS "Semester",
    se.academic_year AS "Academic Year",
    e.grade_points AS "Grade Points",
    e.grade_letter AS "Grade"
FROM enrollments AS e
JOIN students AS s
    ON e.student_id = s.student_id
JOIN courses AS c
    ON e.course_id = c.course_id
JOIN semesters AS se
    ON e.semester_id = se.semester_id
ORDER BY e.enrollment_id;

/*
 * Expected Output:
 *
 * +----------------+----------------+-------------+----------------------------------+----------+---------------+--------------+-------+
 * | Enrollment ID  | Student Name   | Course Code | Course Name                      | Semester | Academic Year | Grade Points | Grade |
 * +----------------+----------------+-------------+----------------------------------+----------+---------------+--------------+-------+
 * | 1              | Hoang Long     | CS101       | Introduction to Computer Science | Fall     | 2024-2025    | 3.70         | A     |
 * | 2              | Hoang Long     | CS201       | Data Structures and Algorithms   | Fall     | 2024-2025    | 3.30         | B     |
 * | 3              | Minh Chau      | CS101       | Introduction to Computer Science | Fall     | 2024-2025    | 3.90         | A     |
 * | 4              | Minh Chau      | MATH201     | Discrete Mathematics             | Fall     | 2024-2025    | 3.50         | A     |
 * | 5              | Quang Minh     | IT202       | Database Systems                 | Fall     | 2024-2025    | 3.20         | B     |
 * | 6              | Quang Minh     | CS101       | Introduction to Computer Science | Spring   | 2024-2025    | 3.60         | A     |
 * | 7              | Thu Ha         | MATH201     | Discrete Mathematics             | Spring   | 2024-2025    | 3.80         | A     |
 * | 8              | Thu Ha         | BUS101      | Principles of Management         | Spring   | 2024-2025    | 3.10         | B     |
 * | 9              | Gia Hung       | BUS101      | Principles of Management         | Spring   | 2024-2025    | 3.40         | B     |
 * | 10             | Gia Hung       | IT202       | Database Systems                 | Spring   | 2024-2025    | 3.70         | A     |
 * | 11             | Hoang Long     | IT202       | Database Systems                 | Fall     | 2025-2026    | NULL         | NULL  |
 * | 12             | Minh Chau      | CS201       | Data Structures and Algorithms   | Fall     | 2025-2026    | NULL         | NULL  |
 * +----------------+----------------+-------------+----------------------------------+----------+---------------+--------------+-------+
 * (12 rows)
 */