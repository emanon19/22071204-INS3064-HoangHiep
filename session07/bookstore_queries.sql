-- ============================================================
-- Online Bookstore Database
-- Homework 7: Advanced SQL Queries
-- ============================================================
-- Student Name: Hoang Hiep
-- Student ID: 22071204
-- Assignment: Homework07
-- Date: 2026-10-14
--
-- Prerequisite:
-- Run bookstore_db.sql from Homework 6 before executing
-- this file.
--
-- Database: PostgreSQL
-- ============================================================

-- ============================================================
-- QUERY 1: List all books with their authors
-- Technique: INNER JOIN (multiple tables)
-- Purpose: Display each book and its associated author(s).
-- ============================================================

SELECT
    b.book_id,
    b.title,
    a.first_name || ' ' || a.last_name AS author_name,
    b.price
FROM books AS b
INNER JOIN book_authors AS ba
    ON b.book_id = ba.book_id
INNER JOIN authors AS a
    ON ba.author_id = a.author_id
ORDER BY b.title, author_name;

/*
Expected output:
Returns one row for each book-author relationship.
Books with multiple authors appear in multiple rows.
The output contains the book ID, title, author name, and price.
*/


-- ============================================================
-- QUERY 2: Retrieve detailed information for every order
-- Technique: Multiple INNER JOINs
-- Purpose: Display order, customer, book, and item details.
-- Note: Cancelled orders are included.
-- ============================================================

SELECT
    o.order_id,
    o.order_date,
    o.status,
    c.first_name || ' ' || c.last_name AS customer_name,
    b.title AS book_title,
    oi.quantity,
    oi.unit_price,
    oi.subtotal
FROM orders AS o
INNER JOIN customers AS c
    ON o.customer_id = c.customer_id
INNER JOIN order_items AS oi
    ON o.order_id = oi.order_id
INNER JOIN books AS b
    ON oi.book_id = b.book_id
ORDER BY o.order_date DESC, o.order_id DESC, b.title;

/*
Expected output:
Returns 16 rows, one for each order item.
Order 6 is included even though its status is Cancelled.
Each row contains order details, customer name, book title,
quantity, unit price, and subtotal.
*/


-- ============================================================
-- QUERY 3: Display books with publishers and categories
-- Technique: Multiple JOINs, including LEFT JOIN
-- Purpose: Show book information even when a book has no
-- associated category.
-- ============================================================

SELECT
    b.book_id,
    b.title,
    p.name AS publisher_name,
    b.price,
    COALESCE(
        STRING_AGG(DISTINCT cat.name, ', ' ORDER BY cat.name),
        'Uncategorized'
    ) AS categories
FROM books AS b
LEFT JOIN publishers AS p
    ON b.publisher_id = p.publisher_id
LEFT JOIN book_categories AS bc
    ON b.book_id = bc.book_id
LEFT JOIN categories AS cat
    ON bc.category_id = cat.category_id
GROUP BY
    b.book_id,
    b.title,
    p.name,
    b.price
ORDER BY b.title;

/*
Expected output:
Returns one row per book.
Each row includes the publisher and a comma-separated list
of categories. Books without categories display Uncategorized.
*/


-- ============================================================
-- QUERY 4: Calculate sales quantity and revenue per book
-- Technique: Aggregation, GROUP BY, SUM, and COUNT
-- Purpose: Rank books by total quantity sold.
-- Note: All orders, including cancelled orders, are included
-- to report all recorded order-item quantities.
-- ============================================================

SELECT
    b.book_id,
    b.title,
    SUM(oi.quantity) AS total_quantity_sold,
    SUM(oi.quantity * oi.unit_price)::NUMERIC(12, 2)
        AS total_revenue
FROM books AS b
INNER JOIN order_items AS oi
    ON b.book_id = oi.book_id
GROUP BY
    b.book_id,
    b.title
ORDER BY
    total_quantity_sold DESC,
    total_revenue DESC,
    b.title;

/*
Expected output:

Book title                                  Quantity  Revenue
------------------------------------------  --------  -------
1984                                               3    47.97
Pride and Prejudice                               3    44.97
Animal Farm                                       3    38.97
Harry Potter and the Philosopher's Stone           2    37.98
To Kill a Mockingbird                             2    33.98
Sapiens                                           1    22.99
Harry Potter and the Chamber of Secrets            1    18.99
The Great Gatsby                                  1    13.99

Revenue here is calculated from order-item unit prices and
quantities, including items associated with cancelled orders.
*/


-- ============================================================
-- QUERY 5: Summarize orders and spending for every customer
-- Technique: LEFT JOIN, GROUP BY, CASE, and COALESCE
-- Purpose: Include customers with no orders and exclude
-- cancelled orders from spending.
-- ============================================================

SELECT
    c.customer_id,
    c.first_name || ' ' || c.last_name AS customer_name,
    COUNT(o.order_id) AS total_orders,
    COALESCE(
        SUM(
            CASE
                WHEN o.status <> 'Cancelled' THEN o.total
                ELSE 0
            END
        ),
        0
    )::NUMERIC(12, 2) AS total_amount_spent
FROM customers AS c
LEFT JOIN orders AS o
    ON c.customer_id = o.customer_id
GROUP BY
    c.customer_id,
    c.first_name,
    c.last_name
ORDER BY c.customer_id;

/*
Expected output:

ID  Customer name  Total orders  Amount spent
--  -------------  ------------  ------------
1   Nguyen An                 2         72.95
2   Tran Binh                 1         37.98
3   Le Chi                    1         53.97
4   Pham Duc                  1         41.98
5   Hoang Mai                 1          0.00

Total orders counts all orders, including cancelled ones.
Amount spent excludes cancelled orders. LEFT JOIN and COALESCE
ensure customers with no orders would still appear with zero.
*/


-- ============================================================
-- QUERY 6: Find books priced above the average book price
-- Technique: Scalar subquery
-- Purpose: Identify books whose price exceeds the average
-- price across all books.
-- ============================================================

SELECT
    book_id,
    title,
    price
FROM books
WHERE price > (
    SELECT AVG(price)
    FROM books
)
ORDER BY price DESC, title;

/*
Expected output:
The average book price in the supplied sample data is 16.99.

The query returns:
- Sapiens                                      22.99
- Harry Potter and the Philosopher's Stone      18.99
- Harry Potter and the Chamber of Secrets       18.99
*/


-- ============================================================
-- QUERY 7: Find the best-selling author or authors
-- Technique: Nested subqueries and aggregation
-- Purpose: Return every author tied for the greatest recorded
-- quantity sold across their books.
-- Note: All recorded order items are included.
-- ============================================================

SELECT
    a.author_id,
    a.first_name || ' ' || a.last_name AS author_name,
    SUM(oi.quantity) AS total_quantity_sold
FROM authors AS a
INNER JOIN book_authors AS ba
    ON a.author_id = ba.author_id
INNER JOIN order_items AS oi
    ON ba.book_id = oi.book_id
GROUP BY
    a.author_id,
    a.first_name,
    a.last_name
HAVING SUM(oi.quantity) = (
    SELECT MAX(author_sales.total_quantity)
    FROM (
        SELECT
            ba2.author_id,
            SUM(oi2.quantity) AS total_quantity
        FROM book_authors AS ba2
        INNER JOIN order_items AS oi2
            ON ba2.book_id = oi2.book_id
        GROUP BY ba2.author_id
    ) AS author_sales
)
ORDER BY author_name;

/*
Expected output:
George Orwell — 6 copies.

The subquery calculates each author's total quantity sold.
The outer query returns all authors tied for the maximum.
*/


-- ============================================================
-- QUERY 8: Find customers with more than two qualifying reviews
-- Technique: GROUP BY, HAVING, and conditional aggregation
-- Purpose: Identify customers who submitted more than two
-- reviews with a rating of at least 3.
-- ============================================================

SELECT
    c.customer_id,
    c.first_name || ' ' || c.last_name AS customer_name,
    COUNT(r.review_id) AS qualifying_review_count,
    ROUND(AVG(r.rating)::NUMERIC, 2) AS average_rating
FROM customers AS c
INNER JOIN reviews AS r
    ON c.customer_id = r.customer_id
WHERE r.rating >= 3
GROUP BY
    c.customer_id,
    c.first_name,
    c.last_name
HAVING COUNT(r.review_id) > 2
ORDER BY qualifying_review_count DESC, customer_name;

/*
Expected output:
No rows.

In the supplied sample data, each customer has only two reviews,
so no customer satisfies the condition.
*/


-- ============================================================
-- QUERY 9: Create a reusable book sales and review summary view
-- Technique: CREATE VIEW, CTEs, aggregation, and LEFT JOINs
-- Purpose: Combine sales and review statistics without
-- multiplying rows when a book has multiple sales and reviews.
-- ============================================================

CREATE OR REPLACE VIEW v_book_sales_summary AS
WITH sales_summary AS (
    SELECT
        oi.book_id,
        SUM(oi.quantity) AS total_quantity_sold,
        SUM(oi.quantity * oi.unit_price)::NUMERIC(12, 2)
            AS total_revenue
    FROM order_items AS oi
    INNER JOIN orders AS o
        ON oi.order_id = o.order_id
    WHERE o.status <> 'Cancelled'
    GROUP BY oi.book_id
),
review_summary AS (
    SELECT
        r.book_id,
        COUNT(r.review_id) AS review_count,
        ROUND(AVG(r.rating)::NUMERIC, 2) AS average_rating
    FROM reviews AS r
    GROUP BY r.book_id
)
SELECT
    b.book_id,
    b.title,
    b.price,
    COALESCE(s.total_quantity_sold, 0) AS total_quantity_sold,
    COALESCE(s.total_revenue, 0)::NUMERIC(12, 2) AS total_revenue,
    COALESCE(rs.review_count, 0) AS review_count,
    rs.average_rating
FROM books AS b
LEFT JOIN sales_summary AS s
    ON b.book_id = s.book_id
LEFT JOIN review_summary AS rs
    ON b.book_id = rs.book_id;


-- Retrieve the summary for all books.
SELECT
    book_id,
    title,
    price,
    total_quantity_sold,
    total_revenue,
    review_count,
    average_rating
FROM v_book_sales_summary
ORDER BY title;

/*
Expected output:
One row per book, containing price, non-cancelled sales quantity,
non-cancelled revenue, review count, and average rating.

Books with no sales have zero quantity and revenue.
Books with no reviews have zero review count and NULL average
rating. The view aggregates sales and reviews separately to
prevent incorrect totals from duplicated JOIN rows.
*/


-- ============================================================
-- QUERY 10: Generate a monthly revenue report for the current year
-- Technique: generate_series, CTEs, date functions, aggregation,
-- and LEFT JOIN
-- Purpose: Show all 12 months, including months without sales.
-- Cancelled orders are excluded.
-- ============================================================

WITH months AS (
    SELECT
        generate_series(
            DATE_TRUNC('year', CURRENT_DATE),
            DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '11 months',
            INTERVAL '1 month'
        )::DATE AS month_start
),
monthly_sales AS (
    SELECT
        DATE_TRUNC('month', o.order_date)::DATE AS month_start,
        COUNT(DISTINCT o.order_id) AS order_count,
        COALESCE(SUM(oi.quantity), 0) AS items_sold,
        COALESCE(
            SUM(oi.quantity * oi.unit_price),
            0
        )::NUMERIC(12, 2) AS revenue
    FROM orders AS o
    INNER JOIN order_items AS oi
        ON o.order_id = oi.order_id
    WHERE o.status <> 'Cancelled'
      AND o.order_date >= DATE_TRUNC('year', CURRENT_DATE)
      AND o.order_date < DATE_TRUNC('year', CURRENT_DATE)
                              + INTERVAL '1 year'
    GROUP BY DATE_TRUNC('month', o.order_date)::DATE
)
SELECT
    TO_CHAR(m.month_start, 'YYYY-MM') AS sales_month,
    COALESCE(ms.order_count, 0) AS order_count,
    COALESCE(ms.items_sold, 0) AS items_sold,
    COALESCE(ms.revenue, 0)::NUMERIC(12, 2) AS revenue
FROM months AS m
LEFT JOIN monthly_sales AS ms
    ON m.month_start = ms.month_start
ORDER BY m.month_start;

/*
Expected output for the supplied sample data when CURRENT_DATE
falls in 2026:

Month    Orders  Items sold  Revenue
-------  ------  ----------  -------
2026-01       0           0     0.00
2026-02       0           0     0.00
2026-03       0           0     0.00
2026-04       0           0     0.00
2026-05       0           0     0.00
2026-06       0           0     0.00
2026-07       0           0     0.00
2026-08       0           0     0.00
2026-09       5          14   223.86
2026-10       0           0     0.00
2026-11       0           0     0.00
2026-12       0           0     0.00

Revenue is calculated from non-cancelled order-item quantities
multiplied by unit prices. The report uses the current year
dynamically and always generates 12 monthly rows.
*/


-- ============================================================
-- END OF ASSIGNMENT
-- ============================================================
