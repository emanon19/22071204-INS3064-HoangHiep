-- ============================================================
-- CAMPUS BOOKING SYSTEM
-- PostgreSQL
-- ============================================================

-- NOTE:
-- DROP DATABASE and CREATE DATABASE must run outside
-- a transaction block.
-- Connect to the postgres database before running this file.

-- 1. Disconnect existing connections and drop the database
DROP DATABASE IF EXISTS campus_booking_midterm WITH (FORCE);

-- 2. Create the database with UTF-8 encoding
CREATE DATABASE campus_booking_midterm
    WITH ENCODING = 'UTF8';

-- 3. Connect to the new database
\connect campus_booking_midterm

-- ============================================================
-- 4. CREATE RESOURCES TABLE (MAIN TABLE)
-- ============================================================

CREATE TABLE resources (
    id BIGSERIAL PRIMARY KEY,

    resource_type_id BIGINT NOT NULL,
    location_id BIGINT NOT NULL,

    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL,
    description TEXT,

    capacity INTEGER,

    status VARCHAR(30) NOT NULL DEFAULT 'available',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_resources_capacity
        CHECK (capacity IS NULL OR capacity > 0),

    CONSTRAINT chk_resources_status
        CHECK (
            status IN ('available', 'maintenance', 'inactive')
        ),

    CONSTRAINT uq_resources_code
        UNIQUE (code),

    CONSTRAINT uq_resources_location_name
        UNIQUE (location_id, name)
);

-- ============================================================
-- 5. CREATE BOOKINGS TABLE (RELATED TABLE)
-- ============================================================

CREATE TABLE bookings (
    id BIGSERIAL PRIMARY KEY,

    user_id BIGINT NOT NULL,
    resource_id BIGINT NOT NULL,
    time_slot_id BIGINT NOT NULL,

    purpose TEXT,

    status VARCHAR(30) NOT NULL DEFAULT 'pending',

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    cancelled_at TIMESTAMPTZ,

    CONSTRAINT chk_bookings_status
        CHECK (
            status IN (
                'pending',
                'approved',
                'rejected',
                'cancelled',
                'completed'
            )
        ),

    CONSTRAINT fk_bookings_resource
        FOREIGN KEY (resource_id)
        REFERENCES resources (id)
        ON DELETE RESTRICT
);

-- ============================================================
-- 6. INSERT SAMPLE DATA
-- Paste your INSERT INTO resources and
-- INSERT INTO bookings statements here.
-- ============================================================

INSERT INTO resources (
    resource_type_id,
    location_id,
    name,
    code,
    description,
    capacity,
    status,
    is_active
)
VALUES
(
    1, 1,
    'Computer Lab A',
    'LAB-001',
    'Computer laboratory with desktop computers for programming classes.',
    40,
    'available',
    TRUE
),
(
    1, 1,
    'Computer Lab B',
    'LAB-002',
    'Computer laboratory for software development and practical examinations.',
    35,
    'available',
    TRUE
),
(
    2, 2,
    'Lecture Hall A',
    'HALL-001',
    'Large lecture hall equipped with a projector and sound system.',
    120,
    'available',
    TRUE
),
(
    2, 2,
    'Lecture Hall B',
    'HALL-002',
    'Lecture hall suitable for seminars and student presentations.',
    100,
    'available',
    TRUE
),
(
    3, 3,
    'Meeting Room A',
    'ROOM-001',
    'Small meeting room for student group projects.',
    12,
    'available',
    TRUE
),
(
    3, 3,
    'Meeting Room B',
    'ROOM-002',
    'Meeting room with a whiteboard and conference table.',
    15,
    'available',
    TRUE
),
(
    4, 4,
    'Study Room A',
    'STUDY-001',
    'Quiet study room for individual and group learning.',
    8,
    'available',
    TRUE
),
(
    4, 4,
    'Study Room B',
    'STUDY-002',
    'Study room with desks and power outlets.',
    10,
    'available',
    TRUE
),
(
    5, 5,
    'Sports Court A',
    'SPORT-001',
    'Outdoor court for basketball and recreational activities.',
    30,
    'maintenance',
    TRUE
),
(
    5, 5,
    'Sports Court B',
    'SPORT-002',
    'Outdoor multipurpose sports court.',
    25,
    'available',
    TRUE
),
(
    3, 3,
    'Meeting Room C',
    'ROOM-003',
    'Additional meeting room for student organizations.',
    10,
    'available',
    TRUE
),
(
    4, 4,
    'Study Room C',
    'STUDY-003',
    'Small study room available for quiet individual study.',
    6,
    'available',
    TRUE
);


-- ============================================================
-- 6. INSERT BOOKINGS (RELATED TABLE)
-- At least 10 rows
-- ============================================================

INSERT INTO bookings (
    user_id,
    resource_id,
    time_slot_id,
    purpose,
    status
)
VALUES
(
    1, 1, 1,
    'Programming practice',
    'approved'
),
(
    2, 2, 2,
    'Software engineering assignment',
    'pending'
),
(
    3, 3, 3,
    'Computer science lecture',
    'approved'
),
(
    4, 4, 4,
    'Student seminar',
    'completed'
),
(
    5, 5, 5,
    'Group project discussion',
    'approved'
),
(
    6, 6, 6,
    'Club planning meeting',
    'pending'
),
(
    7, 7, 7,
    'Individual study session',
    'approved'
),
(
    8, 8, 8,
    'Exam preparation',
    'completed'
),
(
    9, 10, 9,
    'Basketball practice',
    'approved'
),
(
    10, 1, 10,
    'Programming workshop',
    'pending'
),
(
    2, 3, 11,
    'Guest lecture',
    'approved'
),
(
    4, 6, 12,
    'Student organization meeting',
    'cancelled'
);


-- ============================================================
-- 7. VIEW ALL RESOURCES
-- ============================================================

SELECT *
FROM resources
ORDER BY id;


-- ============================================================
-- 8. VIEW ALL BOOKINGS
-- ============================================================

SELECT *
FROM bookings
ORDER BY id;


-- ============================================================
-- 9. INNER JOIN
-- Display resources that have bookings
-- ============================================================

SELECT
    r.id AS resource_id,
    r.name AS resource_name,
    r.code AS resource_code,
    b.id AS booking_id,
    b.user_id,
    b.purpose,
    b.status AS booking_status
FROM resources r
INNER JOIN bookings b
    ON r.id = b.resource_id
ORDER BY r.id, b.id;


-- ============================================================
-- 10. LEFT JOIN
-- Display ALL resources, including resources without bookings
-- ============================================================

SELECT
    r.id AS resource_id,
    r.name AS resource_name,
    r.code AS resource_code,
    b.id AS booking_id,
    b.user_id,
    b.purpose,
    b.status AS booking_status
FROM resources r
LEFT JOIN bookings b
    ON r.id = b.resource_id
ORDER BY r.id, b.id;


-- ============================================================
-- 11. FIND RESOURCES WITHOUT BOOKINGS
-- ============================================================

SELECT
    r.id,
    r.name,
    r.code,
    r.status
FROM resources r
LEFT JOIN bookings b
    ON r.id = b.resource_id
WHERE b.id IS NULL
ORDER BY r.id;


-- ============================================================
-- 12. COUNT BOOKINGS FOR EACH RESOURCE
-- ============================================================

SELECT
    r.id,
    r.name,
    r.code,
    COUNT(b.id) AS total_bookings
FROM resources r
LEFT JOIN bookings b
    ON r.id = b.resource_id
GROUP BY r.id, r.name, r.code
ORDER BY r.id;


-- ============================================================
-- END OF DATABASE SCRIPT
-- ============================================================