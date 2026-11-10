-- Active: 1791174248300@@127.0.0.1@5432@campus_booking_midterm@public
-- ============================================================
-- CAMPUS BOOKING SYSTEM
-- Statistics Queries
-- Tables: resources (main), bookings (related)
-- Database: PostgreSQL
-- ============================================================

SELECT current_database() AS database_name,
       current_schema() AS schema_name;

-- ============================================================
-- Q1: INNER JOIN (6 points)
-- Question:
-- Which resources have bookings, and who booked them?
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
inner JOIN bookings b
    ON r.id = b.resource_id
ORDER BY r.id, b.id;

/*
Result: 12 rows, assuming the sample data from database.sql
has been inserted successfully.

Explanation: Each row shows a booking together with its
corresponding resource. INNER JOIN excludes resources
that do not have any bookings.
*/


-- ============================================================
-- Q2: LEFT JOIN + COUNT(column) + GROUP BY (7 points)
-- Question:
-- How many bookings does each resource have, including
-- resources that have never been booked?
-- ============================================================

SELECT
    r.id AS resource_id,
    r.name AS resource_name,
    r.code AS resource_code,
    COUNT(b.id) AS total_bookings
FROM resources r
left JOIN bookings b
    ON r.id = b.resource_id
GROUP BY
    r.id,
    r.name,
    r.code
ORDER BY
    total_bookings DESC,
    r.id;

/*
Result: 12 rows, one for each resource.

Explanation: LEFT JOIN includes all resources, even those
without bookings. COUNT(b.id) ignores NULL values, so
resources without bookings have a count of 0.
*/


-- ============================================================
-- Q3: GROUP BY + SUM / AVG / COUNT + HAVING (7 points)
-- Question:
-- Which resource types have at least 2 bookings, and what
-- are their total bookings and average bookings per resource?
-- ============================================================

SELECT
    r.resource_type_id,
    COUNT(DISTINCT r.id) AS total_resources,
    COUNT(b.id) AS total_bookings,
    ROUND(
        COUNT(b.id)::NUMERIC
        / NULLIF(COUNT(DISTINCT r.id), 0),
        2
    ) AS avg_bookings_per_resource
FROM resources r
LEFT JOIN bookings b
    ON r.id = b.resource_id
GROUP BY
    r.resource_type_id
HAVING
    COUNT(b.id) >= 2
ORDER BY
    total_bookings DESC;

/*
Result: With the sample data from database.sql, resource
type IDs 1, 2, 3, and 4 meet the requirement of at least
2 bookings per type.

Explanation: GROUP BY calculates booking statistics for
each resource type, and HAVING filters out types with
fewer than 2 bookings. COUNT(DISTINCT r.id) correctly
counts resources without double-counting those with
multiple bookings.
*/

-- ============================================================
-- END OF STATISTICS QUERIES
-- ============================================================