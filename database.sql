-- ============================================================
-- Duka Bora Inventory Management System
-- Database Schema & Seed Data
-- Author  : Duka Bora Dev Team
-- Version : 1.0.0
-- ============================================================

-- Create and select the database
CREATE DATABASE IF NOT EXISTS `dukabora_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `dukabora_db`;

-- ============================================================
-- TABLE: categories
-- Purpose: Stores product category names
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `category_id`   INT           NOT NULL AUTO_INCREMENT,
    `category_name` VARCHAR(100)  NOT NULL,
    PRIMARY KEY (`category_id`),
    UNIQUE KEY `uq_category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: suppliers
-- Purpose: Stores supplier contact details
-- ============================================================
CREATE TABLE IF NOT EXISTS `suppliers` (
    `supplier_id`   INT          NOT NULL AUTO_INCREMENT,
    `supplier_name` VARCHAR(150) NOT NULL,
    `phone`         VARCHAR(20)  NOT NULL,
    `location`      VARCHAR(200) NOT NULL,
    PRIMARY KEY (`supplier_id`),
    UNIQUE KEY `uq_supplier_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: products
-- Purpose: Core inventory records
-- ============================================================
CREATE TABLE IF NOT EXISTS `products` (
    `product_id`  INT            NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(200)   NOT NULL,
    `category_id` INT            NOT NULL,
    `supplier_id` INT            NOT NULL,
    `price`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `stock_qty`   INT            NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`product_id`),
    CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`)
        REFERENCES `categories`(`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_product_supplier` FOREIGN KEY (`supplier_id`)
        REFERENCES `suppliers`(`supplier_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `chk_price`     CHECK (`price`     >= 0),
    CONSTRAINT `chk_stock_qty` CHECK (`stock_qty` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: sales
-- Purpose: Records every sale transaction
-- ============================================================
CREATE TABLE IF NOT EXISTS `sales` (
    `sale_id`     INT            NOT NULL AUTO_INCREMENT,
    `product_id`  INT            NOT NULL,
    `qty_sold`    INT            NOT NULL,
    `sale_date`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `total_price` DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`sale_id`),
    CONSTRAINT `fk_sale_product` FOREIGN KEY (`product_id`)
        REFERENCES `products`(`product_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `chk_qty_sold`   CHECK (`qty_sold`    > 0),
    CONSTRAINT `chk_total_price` CHECK (`total_price` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA: categories (at least 3)
-- ============================================================
INSERT INTO `categories` (`category_name`) VALUES
    ('Electronics'),
    ('Clothing & Apparel'),
    ('Food & Beverages'),
    ('Home & Kitchen'),
    ('Beauty & Health');

-- ============================================================
-- SEED DATA: suppliers (at least 3)
-- ============================================================
INSERT INTO `suppliers` (`supplier_name`, `phone`, `location`) VALUES
    ('TechLink Supplies Ltd',    '+254701234567', 'Nairobi, Kenya'),
    ('FashionForward Traders',   '+254712345678', 'Mombasa, Kenya'),
    ('Savannah Foods Co.',       '+254723456789', 'Kisumu, Kenya'),
    ('HomePlus Distributors',    '+254734567890', 'Nakuru, Kenya'),
    ('GlowMart Wholesalers',     '+254745678901', 'Eldoret, Kenya');

-- ============================================================
-- SEED DATA: products (at least 10)
-- ============================================================
INSERT INTO `products` (`name`, `category_id`, `supplier_id`, `price`, `stock_qty`) VALUES
    ('Samsung Galaxy A54 Smartphone', 1, 1, 45000.00, 12),
    ('Wireless Bluetooth Earbuds',    1, 1,  3500.00, 25),
    ('Dell Laptop Bag 15.6"',         1, 1,  2200.00,  8),
    ('Men\'s Slim Fit Chinos',        2, 2,  1800.00, 30),
    ('Ladies Summer Dress',           2, 2,  2500.00, 20),
    ('Maize Flour 2kg Pack',          3, 3,   180.00,  3),  -- low stock
    ('Arabica Coffee 500g',           3, 3,   650.00,  4),  -- low stock
    ('Non-stick Frying Pan 28cm',     4, 4,  1400.00, 15),
    ('Vacuum Flask 1L',               4, 4,   850.00,  6),
    ('Sunscreen SPF 50+ 100ml',       5, 5,   950.00,  0),  -- out of stock
    ('Aloe Vera Moisturiser 200ml',   5, 5,   600.00,  9),
    ('USB-C Fast Charger 65W',        1, 1,  1950.00, 18);

-- ============================================================
-- SEED DATA: sales (sample history)
-- ============================================================
INSERT INTO `sales` (`product_id`, `qty_sold`, `total_price`, `sale_date`) VALUES
    (1, 2,  90000.00, NOW() - INTERVAL 3 DAY),
    (2, 5,  17500.00, NOW() - INTERVAL 2 DAY),
    (4, 3,   5400.00, NOW() - INTERVAL 2 DAY),
    (6, 10,  1800.00, NOW() - INTERVAL 1 DAY),
    (8, 2,   2800.00, NOW() - INTERVAL 1 DAY),
    (3, 1,   2200.00, NOW()),
    (5, 2,   5000.00, NOW()),
    (9, 3,   2550.00, NOW()),
    (11, 4,  2400.00, NOW() - INTERVAL 4 DAY),
    (12, 2,  3900.00, NOW());
