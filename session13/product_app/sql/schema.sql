-- Database: product_management
-- Connect to product_management before running this file.

DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price NUMERIC(10, 2) NOT NULL CHECK (price >= 0),
    category_id BIGINT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE SET NULL
);

CREATE INDEX idx_products_category_id
    ON products(category_id);

CREATE INDEX idx_products_created_at
    ON products(created_at DESC);

CREATE INDEX idx_users_remember_token
    ON users(remember_token);

INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and accessories.'),
('Books', 'Books, textbooks, and educational materials.'),
('Clothing', 'Clothing and fashion accessories.'),
('Home & Living', 'Furniture and household products.');

INSERT INTO products (name, description, price, category_id) VALUES
('Wireless Mouse', 'Ergonomic wireless mouse with adjustable DPI.', 19.99, 1),
('Mechanical Keyboard', 'Mechanical keyboard with RGB lighting.', 79.50, 1),
('USB-C Hub', 'Multi-port USB-C adapter.', 34.99, 1),
('Clean Code', 'A handbook of agile software craftsmanship.', 42.00, 2),
('Design Patterns', 'Elements of reusable object-oriented software.', 55.00, 2),
('Basic T-Shirt', 'Comfortable cotton T-shirt.', 12.50, 3),
('Desk Lamp', 'Adjustable LED desk lamp.', 25.00, 4);