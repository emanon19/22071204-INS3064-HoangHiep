-- ============================================================
-- University Database Assignment
-- Name: Hoang Hiep
-- Student ID: 22071204
-- Assignment: 04
-- Date: 2026-10-02
-- PostgreSQL
-- ============================================================


-- ============================================================
-- DATABASE RECREATION
-- ============================================================

-- Drop the database if it already exists so the script can
-- be executed repeatedly without database-level conflicts.
DROP DATABASE IF EXISTS university_db;

-- Create a fresh university database.
CREATE DATABASE university_db;

-- Connect to the newly created database.
-- This command is supported by psql / SQL Shell.
\connect university_db


-- ============================================================
-- DROP EXISTING OBJECTS
-- ============================================================

-- Tables are dropped in dependency order.
-- CASCADE is avoided so that dependency problems are visible.

DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS instructors;
DROP TABLE IF EXISTS semesters;
DROP TABLE IF EXISTS departments;

-- Drop ENUM types if they exist.
DROP TYPE IF EXISTS gender_type;
DROP TYPE IF EXISTS grade_letter_type;


-- ============================================================
-- ENUM TYPES
-- ============================================================

-- Fixed set of gender values.
CREATE TYPE gender_type AS ENUM (
    'Male',
    'Female',
    'Other'
);

-- Fixed set of letter grades.
CREATE TYPE grade_letter_type AS ENUM (
    'A',
    'B',
    'C',
    'D',
    'F'
);


-- ============================================================
-- 1. DEPARTMENTS
-- ============================================================

-- Stores academic departments in the university.
CREATE TABLE departments (
    department_id INT GENERATED ALWAYS AS IDENTITY,

    -- Department name must be provided and must be unique.
    department_name VARCHAR(100) NOT NULL,

    -- Optional department description.
    description TEXT,

    -- Primary key constraint.
    CONSTRAINT pk_departments
        PRIMARY KEY (department_id),

    -- No two departments may have the same name.
    CONSTRAINT uq_departments_name
        UNIQUE (department_name)
);


-- ============================================================
-- 2. INSTRUCTORS
-- ============================================================

-- Stores instructor information.
-- Each instructor belongs to one academic department.
CREATE TABLE instructors (
    instructor_id INT GENERATED ALWAYS AS IDENTITY,

    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,

    email VARCHAR(150) NOT NULL,

    gender gender_type NOT NULL,

    hire_date DATE NOT NULL DEFAULT CURRENT_DATE,

    department_id INT NOT NULL,

    -- Primary key constraint.
    CONSTRAINT pk_instructors
        PRIMARY KEY (instructor_id),

    -- Instructor email must be unique.
    CONSTRAINT uq_instructors_email
        UNIQUE (email),

    -- Department reference.
    -- If a department ID is updated, update the instructor record.
    -- A department cannot be deleted while instructors belong to it.
    CONSTRAINT fk_instructors_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- ============================================================
-- 3. STUDENTS
-- ============================================================

-- Stores student information.
-- Each student has one department as their major.
CREATE TABLE students (
    student_id INT GENERATED ALWAYS AS IDENTITY,

    student_code VARCHAR(20) NOT NULL,

    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,

    email VARCHAR(150) NOT NULL,

    gender gender_type NOT NULL,

    date_of_birth DATE,

    enrollment_date DATE NOT NULL DEFAULT CURRENT_DATE,

    department_id INT NOT NULL,

    -- Primary key constraint.
    CONSTRAINT pk_students
        PRIMARY KEY (student_id),

    -- Student code must uniquely identify each student.
    CONSTRAINT uq_students_code
        UNIQUE (student_code),

    -- Student email must be unique.
    CONSTRAINT uq_students_email
        UNIQUE (email),

    -- Department reference.
    CONSTRAINT fk_students_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- ============================================================
-- 4. COURSES
-- ============================================================

-- Stores university courses.
-- Each course belongs to a department and has an instructor.
CREATE TABLE courses (
    course_id INT GENERATED ALWAYS AS IDENTITY,

    course_code VARCHAR(20) NOT NULL,

    course_name VARCHAR(150) NOT NULL,

    description TEXT,

    credits INT NOT NULL DEFAULT 3,

    department_id INT NOT NULL,

    instructor_id INT NOT NULL,

    -- Primary key constraint.
    CONSTRAINT pk_courses
        PRIMARY KEY (course_id),

    -- Course code must be unique.
    CONSTRAINT uq_courses_code
        UNIQUE (course_code),

    -- Course credits must be between 1 and 6.
    CONSTRAINT chk_courses_credits
        CHECK (credits BETWEEN 1 AND 6),

    -- Department reference.
    CONSTRAINT fk_courses_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    -- Instructor reference.
    CONSTRAINT fk_courses_instructor
        FOREIGN KEY (instructor_id)
        REFERENCES instructors(instructor_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- ============================================================
-- 5. SEMESTERS
-- ============================================================

-- Stores academic semesters such as Fall 2024 and Spring 2025.
CREATE TABLE semesters (
    semester_id INT GENERATED ALWAYS AS IDENTITY,

    semester_name VARCHAR(30) NOT NULL,

    academic_year VARCHAR(20) NOT NULL,

    start_date DATE NOT NULL,

    end_date DATE NOT NULL,

    -- Primary key constraint.
    CONSTRAINT pk_semesters
        PRIMARY KEY (semester_id),

    -- Prevent duplicate semester/year combinations.
    CONSTRAINT uq_semesters_name_year
        UNIQUE (semester_name, academic_year),

    -- End date must be after start date.
    CONSTRAINT chk_semesters_dates
        CHECK (end_date > start_date)
);


-- ============================================================
-- 6. ENROLLMENTS
-- ============================================================

-- Links students to courses in a particular semester.
-- A student can enroll in many courses, and a course can
-- have many students.
CREATE TABLE enrollments (
    enrollment_id INT GENERATED ALWAYS AS IDENTITY,

    student_id INT NOT NULL,

    course_id INT NOT NULL,

    semester_id INT NOT NULL,

    enrollment_date DATE NOT NULL DEFAULT CURRENT_DATE,

    -- Numeric grade on a 0.00 - 4.00 GPA scale.
    -- NULL means that the grade has not been assigned yet.
    grade_points DECIMAL(3,2),

    -- Optional letter grade.
    grade_letter grade_letter_type,

    -- Primary key constraint.
    CONSTRAINT pk_enrollments
        PRIMARY KEY (enrollment_id),

    -- Grade must be between 0.00 and 4.00.
    CONSTRAINT chk_enrollments_grade
        CHECK (
            grade_points IS NULL
            OR grade_points BETWEEN 0.00 AND 4.00
        ),

    -- A student cannot enroll in the same course twice
    -- during the same semester.
    CONSTRAINT uq_enrollments_student_course_semester
        UNIQUE (student_id, course_id, semester_id),

    -- Student foreign key.
    CONSTRAINT fk_enrollments_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    -- Course foreign key.
    CONSTRAINT fk_enrollments_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    -- Semester foreign key.
    CONSTRAINT fk_enrollments_semester
        FOREIGN KEY (semester_id)
        REFERENCES semesters(semester_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


-- ============================================================
-- 10. Sample data: Departments
-- ============================================================

INSERT INTO departments
    (department_name, description)
VALUES
    ('Computer Science',
     'Study of computer systems, software, algorithms, and programming.'),

    ('Information Technology',
     'Study of information systems, databases, networks, and IT services.'),

    ('Mathematics',
     'Study of mathematical theories, computation, and applied mathematics.'),

    ('Business Administration',
     'Study of business management, organizations, and administration.'),

    ('Electrical Engineering',
     'Study of electrical systems, electronics, and engineering technology.');

-- ============================================================
-- 11. Sample data: Instructors
-- ============================================================

INSERT INTO instructors
    (first_name, last_name, email, gender, hire_date, department_id)
VALUES
    ('Minh Anh', 'Nguyen',
     'minhanh.nguyen@university.edu',
     'Female', '2018-08-15', 1),

    ('Quang Huy', 'Tran',
     'quanghuy.tran@university.edu',
     'Male', '2019-09-01', 1),

    ('Thi Huong', 'Le',
     'thihuong.le@university.edu',
     'Female', '2020-01-10', 2),

    ('Van Nam', 'Pham',
     'vannam.pham@university.edu',
     'Male', '2017-08-20', 3),

    ('Ngoc Lan', 'Do',
     'ngoclan.do@university.edu',
     'Female', '2021-02-15', 4);

-- ============================================================
-- 12. Sample data: Students
-- ============================================================

INSERT INTO students
    (
        student_code,
        first_name,
        last_name,
        email,
        gender,
        date_of_birth,
        enrollment_date,
        department_id
    )
VALUES
    (
        'SV2024001',
        'Hoang Long',
        'Nguyen',
        'long.nguyen@student.edu',
        'Male',
        '2006-03-15',
        '2024-09-05',
        1
    ),

    (
        'SV2024002',
        'Minh Chau',
        'Tran',
        'chau.tran@student.edu',
        'Female',
        '2006-07-22',
        '2024-09-05',
        1
    ),

    (
        'SV2024003',
        'Quang Minh',
        'Le',
        'minh.le@student.edu',
        'Male',
        '2005-11-10',
        '2024-09-05',
        2
    ),

    (
        'SV2024004',
        'Thu Ha',
        'Pham',
        'ha.pham@student.edu',
        'Female',
        '2006-01-28',
        '2024-09-05',
        3
    ),

    (
        'SV2024005',
        'Gia Hung',
        'Do',
        'hung.do@student.edu',
        'Male',
        '2005-09-17',
        '2024-09-05',
        4
    );

-- ============================================================
-- 13. Sample data: Courses
-- ============================================================

INSERT INTO courses
    (
        course_code,
        course_name,
        description,
        credits,
        department_id,
        instructor_id
    )
VALUES
    (
        'CS101',
        'Introduction to Computer Science',
        'Fundamental concepts of computer science and programming.',
        3,
        1,
        1
    ),

    (
        'CS201',
        'Data Structures and Algorithms',
        'Data structures, algorithms, complexity analysis, and problem solving.',
        4,
        1,
        2
    ),

    (
        'IT202',
        'Database Systems',
        'Relational databases, SQL, normalization, and database design.',
        3,
        2,
        3
    ),

    (
        'MATH201',
        'Discrete Mathematics',
        'Logic, sets, relations, graphs, combinatorics, and discrete structures.',
        3,
        3,
        4
    ),

    (
        'BUS101',
        'Principles of Management',
        'Fundamental concepts of management and organizational behavior.',
        3,
        4,
        5
    );

-- ============================================================
-- 14. Sample data: Semesters
-- ============================================================

INSERT INTO semesters
    (
        semester_name,
        academic_year,
        start_date,
        end_date
    )
VALUES
    ('Fall', '2024-2025', '2024-09-01', '2025-01-15'),

    ('Spring', '2024-2025', '2025-02-01', '2025-06-15'),

    ('Summer', '2024-2025', '2025-07-01', '2025-08-15'),

    ('Fall', '2025-2026', '2025-09-01', '2026-01-15'),

    ('Spring', '2025-2026', '2026-02-01', '2026-06-15');

-- ============================================================
-- 15. Sample data: Enrollments
-- ============================================================

INSERT INTO enrollments
    (
        student_id,
        course_id,
        semester_id,
        enrollment_date,
        grade_points,
        grade_letter
    )
VALUES
    (1, 1, 1, '2024-09-05', 3.70, 'A'),
    (1, 2, 1, '2024-09-05', 3.30, 'B'),

    (2, 1, 1, '2024-09-06', 3.90, 'A'),
    (2, 4, 1, '2024-09-06', 3.50, 'A'),

    (3, 3, 1, '2024-09-06', 3.20, 'B'),
    (3, 1, 2, '2025-02-03', 3.60, 'A'),

    (4, 4, 2, '2025-02-03', 3.80, 'A'),
    (4, 5, 2, '2025-02-03', 3.10, 'B'),

    (5, 5, 2, '2025-02-04', 3.40, 'B'),
    (5, 3, 2, '2025-02-04', 3.70, 'A'),

    -- Current/future enrollment without grades
    (1, 3, 4, '2025-09-05', NULL, NULL),
    (2, 2, 4, '2025-09-05', NULL, NULL);

-- ============================================================
-- 16. Verification
-- ============================================================

SELECT 'departments' AS table_name, COUNT(*) AS record_count
FROM departments

UNION ALL

SELECT 'instructors', COUNT(*)
FROM instructors

UNION ALL

SELECT 'students', COUNT(*)
FROM students

UNION ALL

SELECT 'courses', COUNT(*)
FROM courses

UNION ALL

SELECT 'semesters', COUNT(*)
FROM semesters

UNION ALL

SELECT 'enrollments', COUNT(*)
FROM enrollments;