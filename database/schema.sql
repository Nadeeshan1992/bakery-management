-- Bakery Management System SQL Schema

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('admin', 'owner', 'sales_person', 'pos_operator') NOT NULL DEFAULT 'pos_operator',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `price_retail` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `price_wholesale` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `current_stock` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `min_stock_alert` DECIMAL(10,2) NOT NULL DEFAULT 10.00,
  `unit` VARCHAR(20) NOT NULL DEFAULT 'pcs',
  `image` VARCHAR(255) DEFAULT 'default_product.jpg',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Ingredients / Raw Materials Table
CREATE TABLE IF NOT EXISTS `ingredients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `unit` VARCHAR(20) NOT NULL DEFAULT 'kg',
  `current_stock` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `min_stock_alert` DECIMAL(10,2) NOT NULL DEFAULT 5.00,
  `cost_per_unit` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Recipes Table (Bill of Materials)
CREATE TABLE IF NOT EXISTS `recipes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `ingredient_id` INT NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Customers Table
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(20) DEFAULT 'Mr.',
  `profile_picture` VARCHAR(255) DEFAULT 'default_avatar.png',
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `nic` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `credit_limit` DECIMAL(10,2) DEFAULT 0.00,
  `credit_period` VARCHAR(50) DEFAULT '30 Days',
  `opening_balance` DECIMAL(10,2) DEFAULT 0.00,
  `branch` VARCHAR(100) DEFAULT 'Main Branch',
  `vat_enabled` TINYINT(1) DEFAULT 0,
  `vat_number` VARCHAR(50) DEFAULT NULL,
  `special_discount` DECIMAL(5,2) DEFAULT 0.00,
  `discount_biscuits` DECIMAL(5,2) DEFAULT 0.00,
  `discount_other` DECIMAL(5,2) DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT DEFAULT NULL,
  `order_type` ENUM('pos', 'preorder') NOT NULL DEFAULT 'preorder',
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `change_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash', 'card', 'upi', 'online') NOT NULL DEFAULT 'cash',
  `payment_status` ENUM('paid', 'partial', 'unpaid') NOT NULL DEFAULT 'paid',
  `order_status` ENUM('pending', 'in_production', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'completed',
  `delivery_date` DATETIME DEFAULT NULL,
  `custom_notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Order Returns Table (Expired vs Over Order Returns)
CREATE TABLE IF NOT EXISTS `order_returns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT DEFAULT NULL,
  `customer_id` INT DEFAULT NULL,
  `product_id` INT NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `reason` ENUM('expired', 'over_order') NOT NULL DEFAULT 'over_order',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Order Payments Table (Advance, Partial & Final Settlement Tracking)
CREATE TABLE IF NOT EXISTS `order_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `payment_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cash', 'card', 'upi', 'online') NOT NULL DEFAULT 'cash',
  `payment_type` ENUM('advance', 'installment', 'settlement') NOT NULL DEFAULT 'settlement',
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Inventory Logs Table
CREATE TABLE IF NOT EXISTS `inventory_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ingredient_id` INT NOT NULL,
  `log_type` ENUM('in', 'out', 'waste', 'production') NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `user_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Production Logs Table
CREATE TABLE IF NOT EXISTS `production_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `batch_quantity` INT NOT NULL,
  `produced_by` INT NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produced_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA

-- Default Users (Password for default users: password123)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`) VALUES
(1, 'owner', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'Business Owner', 'owner@bakery.com', 'owner', 'active'),
(2, 'admin', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'Manager Admin', 'admin@bakery.com', 'admin', 'active'),
(3, 'sales', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'John Sales', 'sales@bakery.com', 'sales_person', 'active'),
(4, 'pos', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'Sarah POS Operator', 'pos@bakery.com', 'pos_operator', 'active')
ON DUPLICATE KEY UPDATE `password`='$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa';

-- Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Raw Material', 'Raw ingredient items'),
(2, 'Catering Range', 'Catering & party platter range'),
(3, 'Biscuits/Cookies', 'Biscuits & cookie products'),
(4, 'Rusks', 'Crunchy baked rusks'),
(5, 'Cakes', 'Freshly baked cakes & tortes'),
(6, 'Breads', 'Artisan sourdough & loaves'),
(7, 'Buns', 'Freshly baked bakery buns'),
(8, 'None', 'Uncategorized items')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Ingredients
INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `min_stock_alert`, `cost_per_unit`) VALUES
(1, 'All-Purpose Flour', 'kg', 45.00, 10.00, 1.20),
(2, 'Granulated Sugar', 'kg', 30.00, 8.00, 1.50),
(3, 'Unsalted Butter', 'kg', 12.00, 5.00, 6.50),
(4, 'Cocoa Powder', 'kg', 3.50, 4.00, 8.00), -- Low stock alert!
(5, 'Fresh Eggs', 'pcs', 120.00, 30.00, 0.20),
(6, 'Baking Powder', 'kg', 4.00, 1.00, 3.00),
(7, 'Fresh Whole Milk', 'l', 18.00, 5.00, 1.10),
(8, 'Dark Chocolate Chips', 'kg', 2.00, 3.00, 9.50) -- Low stock alert!
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Products
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `description`, `price`, `unit`, `status`) VALUES
(1, 1, 'BLK-FOR-01', 'Black Forest Cake (1kg)', 'Rich chocolate sponge layered with whipped cream and cherries', 25.00, 'pcs', 'active'),
(2, 1, 'RED-VEL-01', 'Red Velvet Slice', 'Classic red velvet with cream cheese frosting', 4.50, 'slice', 'active'),
(3, 2, 'FRA-BAG-01', 'French Crusty Baguette', 'Traditional sourdough French baguette', 3.00, 'pcs', 'active'),
(4, 2, 'WHO-GRA-01', 'Whole Wheat Honey Loaf', 'Nutritious honey glazed multi-grain bread', 4.20, 'pcs', 'active'),
(5, 3, 'BUT-CRO-01', 'Flaky Butter Croissant', 'Golden baked layered butter croissant', 2.80, 'pcs', 'active'),
(6, 3, 'CHO-DAN-01', 'Chocolate Danish', 'Flaky pastry filled with rich belgian chocolate', 3.50, 'pcs', 'active'),
(7, 4, 'CHO-CHI-01', 'Choco Chip Cookie (Pack of 6)', 'Soft baked giant chocolate chip cookies', 6.00, 'pack', 'active'),
(8, 5, 'CAP-HOT-01', 'Hot Cappuccino', 'Double shot espresso with creamy foam', 3.80, 'cup', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Recipes (BOM for Black Forest Cake id=1)
INSERT INTO `recipes` (`product_id`, `ingredient_id`, `quantity`) VALUES
(1, 1, 0.50), -- 0.5kg Flour
(1, 2, 0.40), -- 0.4kg Sugar
(1, 3, 0.25), -- 0.25kg Butter
(1, 4, 0.15), -- 0.15kg Cocoa
(1, 5, 4.00)  -- 4 Eggs
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Customers
INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`) VALUES
(1, 'Walk-in Customer', 'N/A', 'guest@bakery.com', 'Store POS Counter', 'General POS Sales'),
(2, 'Emily Watson', '+1 555-0192', 'emily.w@example.com', '123 Maple Street, City', 'Prefers eggless options'),
(3, 'David Miller', '+1 555-0843', 'david.m@example.com', '742 Evergreen Terrace', 'Regular custom cake client')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Sample Orders
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES
(1, 'ORD-20260901-001', 1, 'pos', 11.80, 0.00, 0.00, 11.80, 15.00, 3.20, 'cash', 'paid', 'completed', NULL, 'Quick counter sale', 2, NOW() - INTERVAL 1 DAY),
(2, 'ORD-20260902-002', 3, 'custom_cake', 50.00, 5.00, 0.00, 45.00, 20.00, 0.00, 'upi', 'partial', 'in_production', NOW() + INTERVAL 2 DAY, '2-Tier Chocolate Birthday Cake with "Happy 10th Birthday Leo!" message', 1, NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 3, 'French Crusty Baguette', 2, 3.00, 6.00),
(2, 1, 5, 'Flaky Butter Croissant', 1, 2.80, 2.80),
(3, 1, 8, 'Hot Cappuccino', 1, 3.80, 3.80),
(4, 2, 1, 'Black Forest Cake (1kg)', 2, 25.00, 50.00)
ON DUPLICATE KEY UPDATE `id`=`id`;
