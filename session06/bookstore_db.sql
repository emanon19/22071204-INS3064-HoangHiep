/*
===============================================================================
Online Bookstore Database
PostgreSQL
===============================================================================

3NF NORMALIZATION
-----------------

1NF:
- Every table has a primary key.
- All attributes contain atomic values.
- No repeating groups or multi-valued attributes are stored in a single column.
- M:N relationships are resolved using junction tables:
    book_authors
    book_categories

2NF:
- Every non-key attribute depends on the entire primary key.
- Junction tables use composite primary keys:
    (book_id, author_id)
    (book_id, category_id)
- Attributes in order_items depend on the complete line-item identity.

3NF:
- Non-key attributes depend only on their table's primary key.
- Author information is stored only in authors.
- Publisher information is stored only in publishers.
- Category information is stored only in categories.
- Customer information is stored only in customers.
- Book information is stored only in books.
- Order information is separated from order_items.
- No non-key attribute depends on another non-key attribute.

The database therefore satisfies Third Normal Form (3NF).
===============================================================================
*/

-- ============================================================================
-- DROP EXISTING TABLES
-- ============================================================================

DROP TABLE IF EXISTS reviews CASCADE;
DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS book_categories CASCADE;
DROP TABLE IF EXISTS book_authors CASCADE;
DROP TABLE IF EXISTS books CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS customers CASCADE;
DROP TABLE IF EXISTS authors CASCADE;
DROP TABLE IF EXISTS publishers CASCADE;


-- ============================================================================
-- AUTHORS
-- ============================================================================

CREATE TABLE authors (
    author_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    biography TEXT,
    nationality VARCHAR(100),
    date_of_birth DATE,

    CONSTRAINT uq_authors_name
        UNIQUE (first_name, last_name)
);


-- ============================================================================
-- PUBLISHERS
-- ============================================================================

CREATE TABLE publishers (
    publisher_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    address VARCHAR(300),
    website VARCHAR(255),
    email VARCHAR(255),

    CONSTRAINT uq_publishers_name
        UNIQUE (name),

    CONSTRAINT uq_publishers_email
        UNIQUE (email)
);


-- ============================================================================
-- CATEGORIES
-- ============================================================================

CREATE TABLE categories (
    category_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    parent_category_id INTEGER,

    CONSTRAINT uq_categories_name
        UNIQUE (name),

    CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_category_id)
        REFERENCES categories(category_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT chk_categories_not_self_parent
        CHECK (
            parent_category_id IS NULL
            OR parent_category_id <> category_id
        )
);


-- ============================================================================
-- BOOKS
-- ============================================================================

CREATE TABLE books (
    book_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    isbn VARCHAR(20) NOT NULL,
    publisher_id INTEGER NOT NULL,
    price NUMERIC(10, 2) NOT NULL,
    stock INTEGER NOT NULL DEFAULT 0,
    publication_date DATE,
    description TEXT,

    CONSTRAINT uq_books_isbn
        UNIQUE (isbn),

    CONSTRAINT fk_books_publisher
        FOREIGN KEY (publisher_id)
        REFERENCES publishers(publisher_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT chk_books_price
        CHECK (price > 0),

    CONSTRAINT chk_books_stock
        CHECK (stock >= 0)
);


-- ============================================================================
-- BOOK AUTHORS
-- ============================================================================

CREATE TABLE book_authors (
    book_id INTEGER NOT NULL,
    author_id INTEGER NOT NULL,

    PRIMARY KEY (book_id, author_id),

    CONSTRAINT fk_book_authors_book
        FOREIGN KEY (book_id)
        REFERENCES books(book_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_book_authors_author
        FOREIGN KEY (author_id)
        REFERENCES authors(author_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- ============================================================================
-- BOOK CATEGORIES
-- ============================================================================

CREATE TABLE book_categories (
    book_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,

    PRIMARY KEY (book_id, category_id),

    CONSTRAINT fk_book_categories_book
        FOREIGN KEY (book_id)
        REFERENCES books(book_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_book_categories_category
        FOREIGN KEY (category_id)
        REFERENCES categories(category_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- ============================================================================
-- CUSTOMERS
-- ============================================================================

CREATE TABLE customers (
    customer_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    address VARCHAR(300),
    phone VARCHAR(30),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_customers_email
        UNIQUE (email)
);


-- ============================================================================
-- ORDERS
-- ============================================================================

CREATE TABLE orders (
    order_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    customer_id INTEGER NOT NULL,
    order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    total NUMERIC(12, 2) NOT NULL,
    shipping_address VARCHAR(300) NOT NULL,

    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(customer_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT chk_orders_status
        CHECK (
            status IN (
                'Pending',
                'Processing',
                'Shipped',
                'Delivered',
                'Cancelled'
            )
        ),

    CONSTRAINT chk_orders_total
        CHECK (total >= 0)
);


-- ============================================================================
-- ORDER ITEMS
-- ============================================================================

CREATE TABLE order_items (
    order_item_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id INTEGER NOT NULL,
    book_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price NUMERIC(10, 2) NOT NULL,
    subtotal NUMERIC(12, 2) NOT NULL,

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_order_items_book
        FOREIGN KEY (book_id)
        REFERENCES books(book_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT chk_order_items_quantity
        CHECK (quantity > 0),

    CONSTRAINT chk_order_items_unit_price
        CHECK (unit_price > 0),

    CONSTRAINT chk_order_items_subtotal
        CHECK (subtotal > 0),

    CONSTRAINT uq_order_items_order_book
        UNIQUE (order_id, book_id)
);


-- ============================================================================
-- REVIEWS
-- ============================================================================

CREATE TABLE reviews (
    review_id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    customer_id INTEGER NOT NULL,
    book_id INTEGER NOT NULL,
    rating INTEGER NOT NULL,
    comment TEXT,
    review_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reviews_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(customer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_reviews_book
        FOREIGN KEY (book_id)
        REFERENCES books(book_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT chk_reviews_rating
        CHECK (rating BETWEEN 1 AND 5),

    CONSTRAINT uq_reviews_customer_book
        UNIQUE (customer_id, book_id)
);


-- ============================================================================
-- SAMPLE DATA: AUTHORS
-- ============================================================================

INSERT INTO authors
    (first_name, last_name, biography, nationality, date_of_birth)
VALUES
    (
        'George',
        'Orwell',
        'English novelist and essayist known for works exploring politics and society.',
        'British',
        '1903-06-25'
    ),
    (
        'Jane',
        'Austen',
        'English novelist known for her influential works of fiction.',
        'British',
        '1775-12-16'
    ),
    (
        'J.K.',
        'Rowling',
        'British author best known for the Harry Potter series.',
        'British',
        '1965-07-31'
    ),
    (
        'Harper',
        'Lee',
        'American novelist best known for To Kill a Mockingbird.',
        'American',
        '1926-04-28'
    ),
    (
        'F. Scott',
        'Fitzgerald',
        'American novelist and short story writer of the Jazz Age.',
        'American',
        '1896-09-24'
    ),
    (
        'Yuval Noah',
        'Harari',
        'Israeli historian and author known for works about humanity and civilization.',
        'Israeli',
        '1976-02-24'
    );


-- ============================================================================
-- SAMPLE DATA: PUBLISHERS
-- ============================================================================

INSERT INTO publishers
    (name, address, website, email)
VALUES
    (
        'Penguin Random House',
        '1745 Broadway, New York, NY',
        'https://www.penguinrandomhouse.com',
        'contact@penguinrandomhouse.com'
    ),
    (
        'Bloomsbury Publishing',
        '50 Bedford Square, London',
        'https://www.bloomsbury.com',
        'contact@bloomsbury.com'
    ),
    (
        'HarperCollins',
        '195 Broadway, New York, NY',
        'https://www.harpercollins.com',
        'contact@harpercollins.com'
    ),
    (
        'Vintage Books',
        '1745 Broadway, New York, NY',
        'https://www.penguinrandomhouse.com',
        'vintage@example.com'
    );


-- ============================================================================
-- SAMPLE DATA: CATEGORIES
-- ============================================================================

INSERT INTO categories
    (name, description, parent_category_id)
VALUES
    (
        'Fiction',
        'Fictional literature and novels.',
        NULL
    ),
    (
        'Science Fiction',
        'Fiction involving science and futuristic concepts.',
        1
    ),
    (
        'Fantasy',
        'Fiction involving magical or supernatural elements.',
        1
    ),
    (
        'Classic Literature',
        'Influential works of classic literature.',
        NULL
    ),
    (
        'History',
        'Books about historical events, societies, and civilizations.',
        NULL
    );


-- ============================================================================
-- SAMPLE DATA: BOOKS
-- ============================================================================

INSERT INTO books
    (title, isbn, publisher_id, price, stock, publication_date, description)
VALUES
    (
        '1984',
        '9780451524935',
        1,
        15.99,
        25,
        '1949-06-08',
        'A dystopian novel about surveillance and totalitarian government.'
    ),
    (
        'Animal Farm',
        '9780451526342',
        1,
        12.99,
        30,
        '1945-08-17',
        'An allegorical novella about a group of farm animals.'
    ),
    (
        'Pride and Prejudice',
        '9780141439518',
        1,
        14.99,
        20,
        '1813-01-28',
        'A classic novel about relationships, social class, and marriage.'
    ),
    (
        'Harry Potter and the Philosopher''s Stone',
        '9780747532699',
        2,
        18.99,
        40,
        '1997-06-26',
        'The first novel in the Harry Potter fantasy series.'
    ),
    (
        'To Kill a Mockingbird',
        '9780061120084',
        3,
        16.99,
        18,
        '1960-07-11',
        'A novel exploring justice, morality, and racial inequality.'
    ),
    (
        'The Great Gatsby',
        '9780743273565',
        4,
        13.99,
        22,
        '1925-04-10',
        'A novel depicting ambition, wealth, and the American Dream.'
    ),
    (
        'Sapiens',
        '9780062316097',
        3,
        22.99,
        15,
        '2011-01-01',
        'A history of humankind from early humans to the modern world.'
    ),
    (
        'Harry Potter and the Chamber of Secrets',
        '9780747549604',
        2,
        18.99,
        35,
        '1998-07-02',
        'The second novel in the Harry Potter series.'
    );


-- ============================================================================
-- SAMPLE DATA: BOOK AUTHORS
-- ============================================================================

INSERT INTO book_authors
    (book_id, author_id)
VALUES
    (1, 1),
    (2, 1),
    (3, 2),
    (4, 3),
    (5, 4),
    (6, 5),
    (7, 6),
    (8, 3);


-- ============================================================================
-- SAMPLE DATA: BOOK CATEGORIES
-- ============================================================================

INSERT INTO book_categories
    (book_id, category_id)
VALUES
    (1, 1),
    (1, 4),
    (2, 1),
    (2, 4),
    (3, 1),
    (3, 4),
    (4, 1),
    (4, 3),
    (5, 1),
    (5, 4),
    (6, 1),
    (6, 4),
    (7, 5),
    (8, 1),
    (8, 3);


-- ============================================================================
-- SAMPLE DATA: CUSTOMERS
-- ============================================================================

INSERT INTO customers
    (first_name, last_name, email, password_hash, address, phone)
VALUES
    (
        'Nguyen',
        'An',
        'an.nguyen@example.com',
        '$2b$12$examplehash001',
        '12 Nguyen Trai, Hanoi',
        '0901000001'
    ),
    (
        'Tran',
        'Binh',
        'binh.tran@example.com',
        '$2b$12$examplehash002',
        '25 Le Loi, Ho Chi Minh City',
        '0901000002'
    ),
    (
        'Le',
        'Chi',
        'chi.le@example.com',
        '$2b$12$examplehash003',
        '18 Tran Phu, Da Nang',
        '0901000003'
    ),
    (
        'Pham',
        'Duc',
        'duc.pham@example.com',
        '$2b$12$examplehash004',
        '42 Ba Trieu, Hanoi',
        '0901000004'
    ),
    (
        'Hoang',
        'Mai',
        'mai.hoang@example.com',
        '$2b$12$examplehash005',
        '8 Hai Ba Trung, Hai Phong',
        '0901000005'
    );


-- ============================================================================
-- SAMPLE DATA: ORDERS
-- ============================================================================

INSERT INTO orders
    (customer_id, order_date, status, total, shipping_address)
VALUES
    (
        1,
        '2026-09-01 10:15:00',
        'Delivered',
        41.97,
        '12 Nguyen Trai, Hanoi'
    ),
    (
        2,
        '2026-09-03 14:30:00',
        'Shipped',
        37.98,
        '25 Le Loi, Ho Chi Minh City'
    ),
    (
        3,
        '2026-09-05 09:20:00',
        'Processing',
        53.97,
        '18 Tran Phu, Da Nang'
    ),
    (
        1,
        '2026-09-08 16:45:00',
        'Delivered',
        30.98,
        '12 Nguyen Trai, Hanoi'
    ),
    (
        4,
        '2026-09-10 11:10:00',
        'Pending',
        41.98,
        '42 Ba Trieu, Hanoi'
    ),
    (
        5,
        '2026-09-12 19:05:00',
        'Cancelled',
        36.98,
        '8 Hai Ba Trung, Hai Phong'
    );


-- ============================================================================
-- SAMPLE DATA: ORDER ITEMS
-- ============================================================================

INSERT INTO order_items
    (order_id, book_id, quantity, unit_price, subtotal)
VALUES
    -- Order 1
    (1, 1, 1, 15.99, 15.99),
    (1, 3, 1, 14.99, 14.99),
    (1, 2, 1, 12.99, 12.99),

    -- Order 2
    (2, 4, 1, 18.99, 18.99),
    (2, 8, 1, 18.99, 18.99),

    -- Order 3
    (3, 5, 1, 16.99, 16.99),
    (3, 6, 1, 13.99, 13.99),
    (3, 3, 1, 14.99, 14.99),
    (3, 2, 1, 12.99, 12.99),

    -- Order 4
    (4, 1, 1, 15.99, 15.99),
    (4, 2, 1, 12.99, 12.99),

    -- Order 5
    (5, 7, 1, 22.99, 22.99),
    (5, 3, 1, 14.99, 14.99),
    (5, 1, 1, 15.99, 15.99),

    -- Order 6
    (6, 4, 1, 18.99, 18.99),
    (6, 5, 1, 16.99, 16.99);


-- ============================================================================
-- SAMPLE DATA: REVIEWS
-- ============================================================================

INSERT INTO reviews
    (customer_id, book_id, rating, comment, review_date)
VALUES
    (
        1,
        1,
        5,
        'Excellent dystopian novel with powerful ideas.',
        '2026-09-04 12:00:00'
    ),
    (
        2,
        4,
        5,
        'A wonderful introduction to the Harry Potter universe.',
        '2026-09-06 15:30:00'
    ),
    (
        3,
        3,
        4,
        'A very enjoyable classic romance.',
        '2026-09-07 10:20:00'
    ),
    (
        4,
        5,
        5,
        'Thought-provoking and beautifully written.',
        '2026-09-11 09:15:00'
    ),
    (
        5,
        6,
        4,
        'Interesting portrayal of the American Dream.',
        '2026-09-13 14:10:00'
    ),
    (
        1,
        2,
        4,
        'Short but surprisingly meaningful.',
        '2026-09-05 18:00:00'
    ),
    (
        2,
        8,
        5,
        'The sequel is just as entertaining as the first book.',
        '2026-09-09 11:40:00'
    ),
    (
        3,
        7,
        5,
        'Excellent overview of human history.',
        '2026-09-14 16:25:00'
    ),
    (
        4,
        1,
        5,
        'A classic that remains relevant today.',
        '2026-09-12 13:50:00'
    ),
    (
        5,
        3,
        4,
        'Well written and worth reading.',
        '2026-09-15 17:05:00'
    );


-- ============================================================================
-- INDEXES
-- ============================================================================

CREATE INDEX idx_books_publisher_id
    ON books(publisher_id);

CREATE INDEX idx_book_authors_author_id
    ON book_authors(author_id);

CREATE INDEX idx_book_categories_category_id
    ON book_categories(category_id);

CREATE INDEX idx_categories_parent_category_id
    ON categories(parent_category_id);

CREATE INDEX idx_orders_customer_id
    ON orders(customer_id);

CREATE INDEX idx_orders_order_date
    ON orders(order_date);

CREATE INDEX idx_order_items_book_id
    ON order_items(book_id);

CREATE INDEX idx_reviews_book_id
    ON reviews(book_id);

CREATE INDEX idx_reviews_customer_id
    ON reviews(customer_id);


-- ============================================================================
-- BASIC VALIDATION
-- ============================================================================

SELECT 'authors' AS table_name, COUNT(*) AS row_count FROM authors
UNION ALL
SELECT 'publishers', COUNT(*) FROM publishers
UNION ALL
SELECT 'categories', COUNT(*) FROM categories
UNION ALL
SELECT 'books', COUNT(*) FROM books
UNION ALL
SELECT 'book_authors', COUNT(*) FROM book_authors
UNION ALL
SELECT 'book_categories', COUNT(*) FROM book_categories
UNION ALL
SELECT 'customers', COUNT(*) FROM customers
UNION ALL
SELECT 'orders', COUNT(*) FROM orders
UNION ALL
SELECT 'order_items', COUNT(*) FROM order_items
UNION ALL
SELECT 'reviews', COUNT(*) FROM reviews;