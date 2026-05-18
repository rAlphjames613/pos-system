-- ============================================================
-- POS Database Setup (Complete - Database, Tables, Data, Procedures, Triggers)
-- ============================================================

CREATE DATABASE IF NOT EXISTS pos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_db;

-- ============================================================
-- DROP EXISTING PROCEDURES AND TRIGGERS (for clean re-run)
-- ============================================================
DROP PROCEDURE IF EXISTS process_sale;
DROP PROCEDURE IF EXISTS stock_in;
DROP TRIGGER IF EXISTS after_sale_insert;
DROP TRIGGER IF EXISTS after_sale_item_delete;

-- ============================================================
-- TABLES
-- ============================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','cashier') NOT NULL DEFAULT 'cashier',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    category VARCHAR(100) NOT NULL DEFAULT 'General',
    status ENUM('available','unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sales table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment VARCHAR(50) NOT NULL DEFAULT 'cash',
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    change_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Sales items table
CREATE TABLE IF NOT EXISTS sales_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Inventory logs table
CREATE TABLE IF NOT EXISTS inventory_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    type ENUM('IN','OUT') NOT NULL,
    quantity INT NOT NULL,
    reference_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DEFAULT DATA
-- ============================================================

-- Default admin user (password: admin123)
INSERT INTO users (first_name, last_name, username, password, role, status)
VALUES ('Admin', 'User', 'admin',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin', 'active')
ON DUPLICATE KEY UPDATE id = id;

-- Sample products
INSERT INTO products (name, price, stock, category, status) VALUES
    ('Coca-Cola 1.5L',         65.00,  50, 'Beverages',   'available'),
    ('Royal Tru-Orange 1.5L',  60.00,  40, 'Beverages',   'available'),
    ('Mineral Water 500ml',    15.00, 100, 'Beverages',   'available'),
    ('Nescafe 3-in-1 (box)',   75.00,  30, 'Beverages',   'available'),
    ('Skyflakes Crackers',     12.00,  80, 'Snacks',      'available'),
    ('Piattos Cheese',         20.00,  60, 'Snacks',      'available'),
    ('Nova Multigrain',        16.00,  55, 'Snacks',      'available'),
    ('Lucky Me Pancit Canton', 14.00, 120, 'Instant Food','available'),
    ('Nissin Cup Noodles',     25.00,  90, 'Instant Food','available'),
    ('Magic Sarap 8g',          6.00, 200, 'Condiments',  'available'),
    ('UFC Banana Ketchup',     35.00,  45, 'Condiments',  'available'),
    ('Silver Swan Soy Sauce',  28.00,  40, 'Condiments',  'available'),
    ('Champion Detergent 1kg', 75.00,  25, 'Household',   'available'),
    ('Safeguard Soap (bar)',   35.00,  70, 'Personal Care','available'),
    ('Head & Shoulders 12ml',  12.00, 150, 'Personal Care','available'),
    ('Marlboro Red',          140.00,   3, 'Tobacco',     'available'),
    ('Mighty Cigarette',       85.00,   2, 'Tobacco',     'available'),
    ('Bear Brand Milk 300g',   98.00,  20, 'Dairy',       'available'),
    ('Alaska Evap 370ml',      38.00,  35, 'Dairy',       'available'),
    ('Century Tuna 180g',      42.00,   5, 'Canned Goods','available')
ON DUPLICATE KEY UPDATE id = id;

-- ============================================================
-- STORED PROCEDURES
-- ============================================================

DELIMITER //

CREATE PROCEDURE process_sale(
    IN p_user_id INT,
    IN p_amount_paid DECIMAL(10,2),
    IN p_items JSON
)
BEGIN
    DECLARE v_sale_id INT;
    DECLARE v_total DECIMAL(10,2) DEFAULT 0.00;
    DECLARE v_change DECIMAL(10,2) DEFAULT 0.00;
    DECLARE v_item_count INT DEFAULT 0;
    DECLARE v_i INT DEFAULT 0;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_price DECIMAL(10,2);
    DECLARE v_stock INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Get item count
    SET v_item_count = JSON_LENGTH(p_items);

    -- Validate stock first
    SET v_i = 0;
    WHILE v_i < v_item_count DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].product_id')));
        SET v_quantity = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].quantity')));

        SELECT stock INTO v_stock FROM products WHERE id = v_product_id FOR UPDATE;

        IF v_stock < v_quantity THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock for product';
        END IF;

        SET v_i = v_i + 1;
    END WHILE;

    -- Calculate total
    SET v_i = 0;
    WHILE v_i < v_item_count DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].product_id')));
        SET v_quantity = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].quantity')));
        SET v_price = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].price')));
        SET v_total = v_total + (v_price * v_quantity);
        SET v_i = v_i + 1;
    END WHILE;

    SET v_change = p_amount_paid - v_total;

    -- Insert sale record
    INSERT INTO sales (user_id, total, payment, amount_paid, change_amount)
    VALUES (p_user_id, v_total, 'cash', p_amount_paid, v_change);

    SET v_sale_id = LAST_INSERT_ID();

    -- Insert sale items (trigger will handle stock deduction)
    SET v_i = 0;
    WHILE v_i < v_item_count DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].product_id')));
        SET v_quantity = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].quantity')));
        SET v_price = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].price')));

        INSERT INTO sales_items (sale_id, product_id, quantity, price)
        VALUES (v_sale_id, v_product_id, v_quantity, v_price);

        SET v_i = v_i + 1;
    END WHILE;

    COMMIT;

    -- Return sale info
    SELECT v_sale_id AS sale_id, v_total AS total, v_change AS change_amount;
END //

CREATE PROCEDURE stock_in(
    IN p_product_id INT,
    IN p_user_id INT,
    IN p_quantity INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    UPDATE products SET stock = stock + p_quantity WHERE id = p_product_id;

    INSERT INTO inventory_logs (product_id, user_id, type, quantity)
    VALUES (p_product_id, p_user_id, 'IN', p_quantity);

    COMMIT;
END //

DELIMITER ;

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER $$

CREATE TRIGGER after_sale_insert
AFTER INSERT ON sales_items
FOR EACH ROW
BEGIN
    DECLARE current_stock INT;

    -- Get current stock
    SELECT stock INTO current_stock
    FROM products
    WHERE id = NEW.product_id;

    -- Update stock
    UPDATE products
    SET stock = current_stock - NEW.quantity,
        status = IF(current_stock - NEW.quantity <= 0, 'unavailable', 'available')
    WHERE id = NEW.product_id;

    -- Log inventory movement
    INSERT INTO inventory_logs (product_id, user_id, type, quantity, reference_id)
    SELECT NEW.product_id, s.user_id, 'OUT', NEW.quantity, NEW.sale_id
    FROM sales s
    WHERE s.id = NEW.sale_id;

END $$

CREATE TRIGGER after_sale_item_delete
AFTER DELETE ON sales_items
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock = stock + OLD.quantity,
        status = 'available'
    WHERE id = OLD.product_id;
END $$

DELIMITER ;