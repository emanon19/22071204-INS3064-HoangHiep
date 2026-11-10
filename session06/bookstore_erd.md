# Online Bookstore ERD

## 1. Entities

### `authors`

| Attribute       | Data Type    | Key    |
| --------------- | ------------ | ------ |
| `author_id`     | INTEGER      | **PK** |
| `first_name`    | VARCHAR(100) |        |
| `last_name`     | VARCHAR(100) |        |
| `biography`     | TEXT         |        |
| `nationality`   | VARCHAR(100) |        |
| `date_of_birth` | DATE         |        |

---

### `publishers`

| Attribute      | Data Type    | Key    |
| -------------- | ------------ | ------ |
| `publisher_id` | INTEGER      | **PK** |
| `name`         | VARCHAR(200) | UNIQUE |
| `address`      | VARCHAR(300) |        |
| `website`      | VARCHAR(255) |        |
| `email`        | VARCHAR(255) | UNIQUE |

---

### `categories`

| Attribute            | Data Type    | Key                             |
| -------------------- | ------------ | ------------------------------- |
| `category_id`        | INTEGER      | **PK**                          |
| `name`               | VARCHAR(150) | UNIQUE                          |
| `description`        | TEXT         |                                 |
| `parent_category_id` | INTEGER      | **FK → categories.category_id** |

---

### `books`

| Attribute          | Data Type     | Key                              |
| ------------------ | ------------- | -------------------------------- |
| `book_id`          | INTEGER       | **PK**                           |
| `title`            | VARCHAR(300)  |                                  |
| `isbn`             | VARCHAR(20)   | UNIQUE                           |
| `publisher_id`     | INTEGER       | **FK → publishers.publisher_id** |
| `price`            | NUMERIC(10,2) |                                  |
| `stock`            | INTEGER       |                                  |
| `publication_date` | DATE          |                                  |
| `description`      | TEXT          |                                  |

---

### `book_authors`

| Attribute   | Data Type | Key                            |
| ----------- | --------- | ------------------------------ |
| `book_id`   | INTEGER   | **PK, FK → books.book_id**     |
| `author_id` | INTEGER   | **PK, FK → authors.author_id** |

**Primary Key:** `(book_id, author_id)`

---

### `book_categories`

| Attribute     | Data Type | Key                                 |
| ------------- | --------- | ----------------------------------- |
| `book_id`     | INTEGER   | **PK, FK → books.book_id**          |
| `category_id` | INTEGER   | **PK, FK → categories.category_id** |

**Primary Key:** `(book_id, category_id)`

---

### `customers`

| Attribute       | Data Type    | Key    |
| --------------- | ------------ | ------ |
| `customer_id`   | INTEGER      | **PK** |
| `first_name`    | VARCHAR(100) |        |
| `last_name`     | VARCHAR(100) |        |
| `email`         | VARCHAR(255) | UNIQUE |
| `password_hash` | VARCHAR(255) |        |
| `address`       | VARCHAR(300) |        |
| `phone`         | VARCHAR(30)  |        |
| `created_at`    | TIMESTAMP    |        |

---

### `orders`

| Attribute          | Data Type     | Key                            |
| ------------------ | ------------- | ------------------------------ |
| `order_id`         | INTEGER       | **PK**                         |
| `customer_id`      | INTEGER       | **FK → customers.customer_id** |
| `order_date`       | TIMESTAMP     |                                |
| `status`           | VARCHAR(30)   |                                |
| `total`            | NUMERIC(12,2) |                                |
| `shipping_address` | VARCHAR(300)  |                                |

---

### `order_items`

| Attribute       | Data Type     | Key                      |
| --------------- | ------------- | ------------------------ |
| `order_item_id` | INTEGER       | **PK**                   |
| `order_id`      | INTEGER       | **FK → orders.order_id** |
| `book_id`       | INTEGER       | **FK → books.book_id**   |
| `quantity`      | INTEGER       |                          |
| `unit_price`    | NUMERIC(10,2) |                          |
| `subtotal`      | NUMERIC(12,2) |                          |

---

### `reviews`

| Attribute     | Data Type | Key                            |
| ------------- | --------- | ------------------------------ |
| `review_id`   | INTEGER   | **PK**                         |
| `customer_id` | INTEGER   | **FK → customers.customer_id** |
| `book_id`     | INTEGER   | **FK → books.book_id**         |
| `rating`      | INTEGER   |                                |
| `comment`     | TEXT      |                                |
| `review_date` | TIMESTAMP |                                |

---

# 2. Relationship Diagram

```text
                              ┌─────────────────────┐
                              │     PUBLISHERS      │
                              ├─────────────────────┤
                              │ PK publisher_id     │
                              │    name             │
                              │    address          │
                              │    website          │
                              │    email            │
                              └──────────┬──────────┘
                                         │
                                         │ 1:N
                                         │
                                         ▼
                              ┌─────────────────────┐
                              │        BOOKS        │
                              ├─────────────────────┤
                              │ PK book_id          │
                              │    title            │
                              │    isbn             │
                              │ FK publisher_id     │
                              │    price            │
                              │    stock            │
                              │    publication_date │
                              │    description      │
                              └───────┬───────┬─────┘
                                      │       │
                              M:N     │       │     M:N
                                      │       │
                         ┌────────────▼─┐   ┌─▼──────────────┐
                         │ BOOK_AUTHORS │   │ BOOK_CATEGORIES│
                         ├──────────────┤   ├────────────────┤
                         │ PK book_id   │   │ PK book_id     │
                         │ PK author_id │   │ PK category_id │
                         └───────┬──────┘   └───────┬────────┘
                                 │                   │
                                 │ N:1               │ N:1
                                 ▼                   ▼
                      ┌─────────────────┐   ┌─────────────────────┐
                      │     AUTHORS     │   │     CATEGORIES      │
                      ├─────────────────┤   ├─────────────────────┤
                      │ PK author_id    │   │ PK category_id      │
                      │    first_name   │   │    name             │
                      │    last_name    │   │    description      │
                      │    biography    │   │ FK parent_category_id
                      │    nationality  │   └──────────┬──────────┘
                      │    date_of_birth│              │
                      └─────────────────┘              │ 1:N
                                                       │
                                                       ▼
                                                   Subcategories


┌──────────────────────┐
│      CUSTOMERS       │
├──────────────────────┤
│ PK customer_id       │
│    first_name        │
│    last_name         │
│    email             │
│    password_hash     │
│    address           │
│    phone              │
│    created_at         │
└──────────┬───────────┘
           │
           │ 1:N
           │
           ├───────────────────────────────┐
           │                               │
           ▼                               │
┌──────────────────────┐                   │
│       ORDERS         │                   │
├──────────────────────┤                   │
│ PK order_id          │                   │
│ FK customer_id       │                   │
│    order_date        │                   │
│    status             │                   │
│    total              │                   │
│    shipping_address  │                   │
└──────────┬───────────┘                   │
           │                               │
           │ 1:N                           │
           ▼                               │
┌──────────────────────┐                   │
│    ORDER_ITEMS       │                   │
├──────────────────────┤                   │
│ PK order_item_id     │                   │
│ FK order_id          │                   │
│ FK book_id           │                   │
│    quantity           │                   │
│    unit_price         │                   │
│    subtotal           │                   │
└──────────┬───────────┘                   │
           │                               │
           │ N:1                           │
           └───────────────────────► BOOKS│
                                           │
                                           │
           ┌───────────────────────────────┘
           │ 1:N
           ▼
┌──────────────────────┐
│       REVIEWS        │
├──────────────────────┤
│ PK review_id         │
│ FK customer_id       │
│ FK book_id           │
│    rating             │
│    comment            │
│    review_date        │
└──────────┬───────────┘
           │
           │ N:1
           ▼
         BOOKS
```

---

# 3. Relationship Summary

| Relationship           | Cardinality | Implementation                  |
| ---------------------- | ----------: | ------------------------------- |
| Publisher → Book       |     **1:N** | `books.publisher_id`            |
| Book ↔ Author          |     **M:N** | `book_authors`                  |
| Book ↔ Category        |     **M:N** | `book_categories`               |
| Customer → Order       |     **1:N** | `orders.customer_id`            |
| Order → Order Item     |     **1:N** | `order_items.order_id`          |
| Book → Order Item      |     **1:N** | `order_items.book_id`           |
| Order ↔ Book           |     **M:N** | `order_items`                   |
| Customer → Review      |     **1:N** | `reviews.customer_id`           |
| Book → Review          |     **1:N** | `reviews.book_id`               |
| Category → Subcategory |     **1:N** | `categories.parent_category_id` |

---

# 4. Foreign-Key Relationships

```text
books.publisher_id
    → publishers.publisher_id

categories.parent_category_id
    → categories.category_id

book_authors.book_id
    → books.book_id

book_authors.author_id
    → authors.author_id

book_categories.book_id
    → books.book_id

book_categories.category_id
    → categories.category_id

orders.customer_id
    → customers.customer_id

order_items.order_id
    → orders.order_id

order_items.book_id
    → books.book_id

reviews.customer_id
    → customers.customer_id

reviews.book_id
    → books.book_id
```

---

# 5. Conceptual M:N Relationships

The database contains three conceptual many-to-many relationships.

### Books ↔ Authors

```text
BOOKS M:N AUTHORS
       │
       ▼
 BOOK_AUTHORS
```

A book can have many authors, and an author can write many books.

### Books ↔ Categories

```text
BOOKS M:N CATEGORIES
       │
       ▼
BOOK_CATEGORIES
```

A book can belong to many categories, and a category can contain many books.

### Orders ↔ Books

```text
ORDERS M:N BOOKS
       │
       ▼
 ORDER_ITEMS
```

An order can contain many books, and a book can appear in many orders.

`order_items` additionally stores transaction-specific information:

* `quantity`
* `unit_price`
* `subtotal`

---

# 6. Cardinality Overview

```text
publishers  1 ─────────── N books

books       M ─────────── N authors
                │
                └── book_authors

books       M ─────────── N categories
                │
                └── book_categories

customers   1 ─────────── N orders

orders      1 ─────────── N order_items

books       1 ─────────── N order_items

orders      M ─────────── N books
                │
                └── order_items

customers   1 ─────────── N reviews

books       1 ─────────── N reviews

categories  1 ─────────── N categories
                │
                └── parent/subcategory
```

---

# 7. Entity Count

The ERD contains exactly **10 tables**:

```text
1. authors
2. publishers
3. categories
4. books
5. book_authors
6. book_categories
7. customers
8. orders
9. order_items
10. reviews
```

All required entities, attributes, primary keys, foreign keys, and relationship cardinalities are represented.
