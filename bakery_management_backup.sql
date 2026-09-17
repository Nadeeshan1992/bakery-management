-- Bakery Management System Database Dump
-- Exported at: 2026-09-15 03:39:05

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('1', 'Raw Material', 'Raw Material category', '2026-09-07 09:45:45');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('2', 'Catering Range', 'Catering Range category', '2026-09-07 09:45:45');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('3', 'Biscuits/Cookies', 'Biscuits/Cookies category', '2026-09-07 09:45:45');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('4', 'Rusks', 'Rusks category', '2026-09-07 09:45:45');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('5', 'Cakes', 'Cakes category', '2026-09-07 09:45:45');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('6', 'Breads', 'Breads category', '2026-09-07 09:45:45');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('7', 'Buns', 'Buns category', '2026-09-07 09:45:45');
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES ('8', 'None', 'None category', '2026-09-07 09:45:45');

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(20) DEFAULT 'Mr.',
  `profile_picture` varchar(255) DEFAULT 'default_avatar.png',
  `nic` varchar(30) DEFAULT NULL,
  `credit_limit` decimal(10,2) DEFAULT 0.00,
  `credit_period` varchar(50) DEFAULT '30 Days',
  `opening_balance` decimal(10,2) DEFAULT 0.00,
  `branch` varchar(100) DEFAULT 'Main Branch',
  `vat_enabled` tinyint(1) DEFAULT 0,
  `vat_number` varchar(50) DEFAULT NULL,
  `special_discount` decimal(5,2) DEFAULT 0.00,
  `discount_biscuits` decimal(5,2) DEFAULT 0.00,
  `discount_other` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`, `created_at`, `title`, `profile_picture`, `nic`, `credit_limit`, `credit_period`, `opening_balance`, `branch`, `vat_enabled`, `vat_number`, `special_discount`, `discount_biscuits`, `discount_other`) VALUES ('1', 'Walk-in Customer', 'N/A', 'guest@bakery.com', 'Store POS Counter', 'General POS Sales', '2026-09-02 08:33:32', 'Mr.', 'default_avatar.png', NULL, '0.00', '30 Days', '0.00', 'Main Branch', '0', NULL, '0.00', '0.00', '0.00');
INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`, `created_at`, `title`, `profile_picture`, `nic`, `credit_limit`, `credit_period`, `opening_balance`, `branch`, `vat_enabled`, `vat_number`, `special_discount`, `discount_biscuits`, `discount_other`) VALUES ('2', 'Emily Watson', '+1 555-0192', 'emily.w@example.com', '123 Maple Street, City', 'Prefers eggless options', '2026-09-02 08:33:32', 'Mr.', 'default_avatar.png', NULL, '0.00', '30 Days', '0.00', 'Main Branch', '0', NULL, '0.00', '0.00', '0.00');
INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`, `created_at`, `title`, `profile_picture`, `nic`, `credit_limit`, `credit_period`, `opening_balance`, `branch`, `vat_enabled`, `vat_number`, `special_discount`, `discount_biscuits`, `discount_other`) VALUES ('3', 'David Miller', '+1 555-0843', 'david.m@example.com', '742 Evergreen Terrace', 'Regular custom cake client', '2026-09-02 08:33:32', 'Mr.', 'default_avatar.png', NULL, '0.00', '30 Days', '0.00', 'Main Branch', '0', NULL, '0.00', '0.00', '0.00');
INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`, `created_at`, `title`, `profile_picture`, `nic`, `credit_limit`, `credit_period`, `opening_balance`, `branch`, `vat_enabled`, `vat_number`, `special_discount`, `discount_biscuits`, `discount_other`) VALUES ('4', 'Nanda Storse', '0711234567', '', '88 , silpolagama, badulla', '', '2026-09-02 10:06:44', 'Mr.', 'default_avatar.png', '', '100000.00', '30 Days', '1000.00', 'Main Branch', '0', '', '15.00', '15.00', '15.00');
INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`, `created_at`, `title`, `profile_picture`, `nic`, `credit_limit`, `credit_period`, `opening_balance`, `branch`, `vat_enabled`, `vat_number`, `special_discount`, `discount_biscuits`, `discount_other`) VALUES ('5', 'Nadeeshan Thilina', '0710475200', '', '', '', '2026-09-09 21:33:20', 'Mr.', 'default_avatar.png', '', '100000.00', '30 Days', '0.00', 'Main Branch', '0', '', '15.00', '15.00', '10.00');
INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`, `created_at`, `title`, `profile_picture`, `nic`, `credit_limit`, `credit_period`, `opening_balance`, `branch`, `vat_enabled`, `vat_number`, `special_discount`, `discount_biscuits`, `discount_other`) VALUES ('6', 'Test Mobile Customer', '0779951274', 'test@mobile.com', '123 Test St', NULL, '2026-09-10 10:46:26', 'Mr.', 'default_avatar.png', '123456789V', '50000.00', '30 Days', '0.00', 'Main Branch', '0', NULL, '0.00', '10.00', '5.00');
INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`, `created_at`, `title`, `profile_picture`, `nic`, `credit_limit`, `credit_period`, `opening_balance`, `branch`, `vat_enabled`, `vat_number`, `special_discount`, `discount_biscuits`, `discount_other`) VALUES ('7', 'Sanath', '0712587412', '', 'Badulla', 'Registered via Sales Mobile App', '2026-09-10 10:51:15', 'Mr.', 'default_avatar.png', '', '10000.00', '30 Days', '0.00', 'Main Branch', '0', '', '10.00', '10.00', '5.00');
INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`, `created_at`, `title`, `profile_picture`, `nic`, `credit_limit`, `credit_period`, `opening_balance`, `branch`, `vat_enabled`, `vat_number`, `special_discount`, `discount_biscuits`, `discount_other`) VALUES ('8', 'Test Mobile Customer', '0779969100', 'test@mobile.com', '123 Test St', NULL, '2026-09-10 10:55:08', 'Mr.', 'default_avatar.png', '123456789V', '50000.00', '30 Days', '0.00', 'Main Branch', '0', NULL, '0.00', '10.00', '5.00');

DROP TABLE IF EXISTS `ingredients`;
CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'kg',
  `current_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_stock_alert` decimal(10,2) NOT NULL DEFAULT 5.00,
  `cost_per_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `min_stock_alert`, `cost_per_unit`, `created_at`) VALUES ('1', 'All-Purpose Flour', 'kg', '45.00', '10.00', '1.20', '2026-09-02 08:33:32');
INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `min_stock_alert`, `cost_per_unit`, `created_at`) VALUES ('2', 'Granulated Sugar', 'kg', '30.00', '8.00', '1.50', '2026-09-02 08:33:32');
INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `min_stock_alert`, `cost_per_unit`, `created_at`) VALUES ('3', 'Unsalted Butter', 'kg', '12.00', '5.00', '6.50', '2026-09-02 08:33:32');
INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `min_stock_alert`, `cost_per_unit`, `created_at`) VALUES ('4', 'Cocoa Powder', 'kg', '53.50', '4.00', '8.00', '2026-09-02 08:33:32');
INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `min_stock_alert`, `cost_per_unit`, `created_at`) VALUES ('5', 'Fresh Eggs', 'pcs', '170.00', '30.00', '0.20', '2026-09-02 08:33:32');
INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `min_stock_alert`, `cost_per_unit`, `created_at`) VALUES ('6', 'Baking Powder', 'kg', '4.00', '1.00', '3.00', '2026-09-02 08:33:32');
INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `min_stock_alert`, `cost_per_unit`, `created_at`) VALUES ('7', 'Fresh Whole Milk', 'l', '18.00', '5.00', '1.10', '2026-09-02 08:33:32');
INSERT INTO `ingredients` (`id`, `name`, `unit`, `current_stock`, `min_stock_alert`, `cost_per_unit`, `created_at`) VALUES ('8', 'Dark Chocolate Chips', 'kg', '2.00', '3.00', '9.50', '2026-09-02 08:33:32');

DROP TABLE IF EXISTS `inventory_logs`;
CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ingredient_id` int(11) NOT NULL,
  `log_type` enum('in','out','waste','production') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ingredient_id` (`ingredient_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

INSERT INTO `inventory_logs` (`id`, `ingredient_id`, `log_type`, `quantity`, `reason`, `user_id`, `created_at`) VALUES ('1', '5', 'in', '50.00', '', '1', '2026-09-08 08:00:19');
INSERT INTO `inventory_logs` (`id`, `ingredient_id`, `log_type`, `quantity`, `reason`, `user_id`, `created_at`) VALUES ('2', '4', 'in', '50.00', '', '1', '2026-09-09 20:56:51');

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4;

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('1', '1', '3', 'French Crusty Baguette', '2', '3.00', '6.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('2', '1', '5', 'Flaky Butter Croissant', '1', '2.80', '2.80');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('3', '1', '8', 'Hot Cappuccino', '1', '3.80', '3.80');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('5', '3', '6', 'Chocolate Danish', '15', '3.50', '52.50');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('6', '3', '5', 'Flaky Butter Croissant', '10', '2.80', '28.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('7', '3', '5', 'Flaky Butter Croissant', '15', '2.80', '42.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('8', '4', '9', 'Tea bun', '3', '60.00', '180.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('9', '4', '11', 'Cream Bun', '2', '50.00', '100.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('10', '5', '9', 'Tea bun', '10', '60.00', '600.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('11', '5', '11', 'Cream Bun', '5', '50.00', '250.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('12', '6', '9', 'Tea bun', '10', '60.00', '600.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('13', '6', '4', 'Whole Wheat Honey Loaf', '5', '4.20', '21.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('14', '7', '9', 'Tea bun', '20', '60.00', '1200.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('15', '7', '11', 'Cream Bun', '15', '50.00', '750.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('16', '8', '9', 'Tea bun', '10', '60.00', '600.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('17', '8', '11', 'Cream Bun', '20', '50.00', '1000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('18', '9', '9', 'Tea bun', '10', '60.00', '600.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('19', '9', '12', 'Viskiringha Medium', '20', '150.00', '3000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('20', '10', '12', 'Viskiringha Medium', '100', '150.00', '15000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('21', '11', '12', 'Viskiringha Medium', '1', '150.00', '150.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('22', '12', '12', 'Viskiringha Medium', '10', '150.00', '1500.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('23', '13', '12', 'Viskiringha Medium', '20', '150.00', '3000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('24', '13', '11', 'Cream Bun', '50', '50.00', '2500.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('25', '13', '9', 'Tea bun', '50', '60.00', '3000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('26', '14', '11', 'Cream Bun', '10', '50.00', '500.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('27', '15', '13', 'Milki Rice', '20', '200.00', '4000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('28', '16', '13', 'Milki Rice', '10', '200.00', '2000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('29', '16', '11', 'Cream Bun', '50', '50.00', '2500.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('30', '16', '9', 'Tea bun', '50', '60.00', '3000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('31', '17', '13', 'Milki Rice', '5', '200.00', '1000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('32', '17', '9', 'Tea bun', '20', '60.00', '1200.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('33', '18', '12', 'Viskiringha Medium', '2', '150.00', '300.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('34', '18', '11', 'Cream Bun', '10', '50.00', '500.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('35', '18', '9', 'Tea bun', '20', '60.00', '1200.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('36', '23', '5', 'Flaky Butter Croissant', '10', '2.80', '28.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('37', '23', '12', 'Viskiringha Medium', '15', '150.00', '2250.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('38', '24', '9', 'Tea bun', '20', '60.00', '1200.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('39', '25', '11', 'Cream Bun', '5', '50.00', '250.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('40', '25', '3', 'French Crusty Baguette', '10', '3.00', '30.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('41', '26', '13', 'Milki Rice', '1', '200.00', '200.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('42', '27', '9', 'Tea bun', '10', '60.00', '600.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('43', '28', '12', 'Viskiringha Medium', '1', '150.00', '150.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES ('44', '29', '12', 'Viskiringha Medium', '1', '150.00', '150.00');

DROP TABLE IF EXISTS `order_payments`;
CREATE TABLE `order_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','cheque','online') NOT NULL DEFAULT 'cash',
  `cheque_ref` varchar(100) DEFAULT NULL,
  `payment_type` enum('advance','installment','settlement') NOT NULL DEFAULT 'settlement',
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `order_payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4;

INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('1', '1', '15.00', 'cash', NULL, 'settlement', 'Initial Booking Payment', '2', '2026-09-01 08:33:32');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('2', '2', '20.00', 'online', NULL, 'advance', 'Initial Booking Payment', '1', '2026-09-02 08:33:32');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('3', '4', '280.00', 'cash', NULL, 'settlement', 'Initial Booking Payment', '1', '2026-09-02 10:43:38');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('4', '5', '850.00', 'cash', NULL, 'settlement', 'Initial Booking Payment', '1', '2026-09-05 22:04:48');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('5', '6', '527.85', 'cash', NULL, 'settlement', 'Initial Booking Payment', '1', '2026-09-05 22:12:16');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('6', '7', '1950.00', 'cash', NULL, 'settlement', 'Initial Booking Payment', '1', '2026-09-05 22:24:30');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('7', '8', '1502.50', 'cash', NULL, 'settlement', 'Initial Booking Payment', '1', '2026-09-05 22:24:56');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('8', '9', '3600.00', 'cash', NULL, 'settlement', 'Initial Booking Payment', '1', '2026-09-07 22:15:16');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('9', '10', '15000.00', 'cash', NULL, 'settlement', 'Initial Booking Payment', '1', '2026-09-08 08:10:02');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('10', '11', '150.00', 'cash', NULL, 'settlement', 'Initial Booking Payment', '1', '2026-09-08 08:29:33');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('16', '13', '2000.00', 'cash', NULL, 'settlement', 'Full balance settlement on pickup', '1', '2026-09-09 13:03:37');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('17', '12', '1125.00', 'cash', NULL, 'settlement', 'Full balance settlement on pickup', '1', '2026-09-09 13:04:05');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('18', '3', '122.50', 'cash', NULL, 'settlement', 'Full balance settlement on pickup', '1', '2026-09-09 17:25:56');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('20', '16', '6650.00', 'cheque', '123', 'settlement', 'Full balance settlement on pickup', '6', '2026-09-09 21:54:11');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('21', '17', '2000.00', 'cash', NULL, 'settlement', 'Mobile pre-order settlement payment', '6', '2026-09-09 22:47:27');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('22', '17', '100.00', 'cash', NULL, 'settlement', 'Mobile pre-order settlement payment', '6', '2026-09-09 22:49:01');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('23', '17', '50.00', 'cash', NULL, 'settlement', 'Mobile pre-order settlement payment', '6', '2026-09-09 22:54:09');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('24', '18', '1000.00', 'cash', NULL, 'settlement', 'Mobile pre-order settlement payment', '6', '2026-09-10 10:59:08');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('25', '22', '200.00', 'cash', NULL, 'advance', 'Mobile app advance deposit on pre-order booking', '6', '2026-09-10 12:19:42');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('26', '22', '400.00', 'cash', NULL, 'settlement', 'Mobile pre-order settlement payment', '6', '2026-09-10 12:19:42');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('27', '23', '2278.00', 'cash', NULL, 'settlement', 'POS Counter checkout settlement', '1', '2026-09-10 22:43:27');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('28', '24', '1080.00', 'cash', NULL, 'settlement', 'POS Counter checkout settlement', '1', '2026-09-13 22:00:42');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('29', '21', '500.00', 'cash', NULL, 'settlement', 'Full balance settlement on pickup', '1', '2026-09-13 22:01:26');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('30', '20', '1000.00', 'cash', NULL, 'settlement', 'Full balance settlement on pickup', '1', '2026-09-14 22:18:19');
INSERT INTO `order_payments` (`id`, `order_id`, `payment_amount`, `payment_method`, `cheque_ref`, `payment_type`, `notes`, `created_by`, `created_at`) VALUES ('31', '29', '135.00', 'cash', NULL, 'settlement', 'Full balance settlement on pickup', '1', '2026-09-15 08:22:52');

DROP TABLE IF EXISTS `order_returns`;
CREATE TABLE `order_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reason` enum('expired','over_order') NOT NULL DEFAULT 'over_order',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `order_returns_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_returns_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

INSERT INTO `order_returns` (`id`, `order_id`, `customer_id`, `product_id`, `quantity`, `unit_price`, `total_value`, `reason`, `notes`, `created_by`, `created_at`) VALUES ('1', '12', '4', '12', '1.00', '150.00', '150.00', 'over_order', NULL, '1', '2026-09-08 10:20:11');
INSERT INTO `order_returns` (`id`, `order_id`, `customer_id`, `product_id`, `quantity`, `unit_price`, `total_value`, `reason`, `notes`, `created_by`, `created_at`) VALUES ('2', '13', '4', '11', '15.00', '50.00', '750.00', 'expired', NULL, '1', '2026-09-08 12:59:48');
INSERT INTO `order_returns` (`id`, `order_id`, `customer_id`, `product_id`, `quantity`, `unit_price`, `total_value`, `reason`, `notes`, `created_by`, `created_at`) VALUES ('3', '13', '4', '9', '10.00', '60.00', '600.00', 'expired', NULL, '1', '2026-09-08 12:59:48');
INSERT INTO `order_returns` (`id`, `order_id`, `customer_id`, `product_id`, `quantity`, `unit_price`, `total_value`, `reason`, `notes`, `created_by`, `created_at`) VALUES ('4', '14', '4', '13', '8.00', '200.00', '1600.00', 'over_order', NULL, '1', '2026-09-09 17:35:12');
INSERT INTO `order_returns` (`id`, `order_id`, `customer_id`, `product_id`, `quantity`, `unit_price`, `total_value`, `reason`, `notes`, `created_by`, `created_at`) VALUES ('5', '18', '7', '9', '5.00', '60.00', '300.00', 'expired', NULL, '6', '2026-09-10 10:58:41');

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `order_type` enum('pos','preorder') NOT NULL DEFAULT 'preorder',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `change_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','cheque','online') NOT NULL DEFAULT 'cash',
  `cheque_ref` varchar(100) DEFAULT NULL,
  `payment_status` enum('paid','partial','unpaid') NOT NULL DEFAULT 'paid',
  `order_status` enum('pending','in_production','ready','completed','cancelled') NOT NULL DEFAULT 'completed',
  `delivery_date` datetime DEFAULT NULL,
  `custom_notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `customer_id` (`customer_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('1', 'ORD-20260901-001', '1', 'pos', '11.80', '0.00', '0.00', '11.80', '15.00', '3.20', 'cash', NULL, 'paid', 'completed', NULL, 'Quick counter sale', '2', '2026-09-01 08:33:32');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('2', 'ORD-20260902-002', '3', 'preorder', '50.00', '5.00', '0.00', '45.00', '20.00', '0.00', 'online', NULL, 'partial', 'in_production', '2026-09-04 08:33:32', '2-Tier Chocolate Birthday Cake with \"Happy 10th Birthday Leo!\" message', '1', '2026-09-02 08:33:32');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('3', 'ORD-20260902-5507', '3', 'preorder', '122.50', '0.00', '0.00', '122.50', '122.50', '0.00', 'cash', NULL, 'paid', 'completed', '2026-09-03 08:45:00', '', '1', '2026-09-02 08:43:26');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('4', 'ORD-20260902-3052', '3', 'pos', '280.00', '0.00', '0.00', '280.00', '280.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-02 10:43:38');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('5', 'ORD-20260905-3939', '4', 'pos', '850.00', '0.00', '0.00', '850.00', '850.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-05 22:04:48');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('6', 'ORD-20260905-7272', '4', 'pos', '621.00', '93.15', '0.00', '527.85', '527.85', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-05 22:12:16');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('7', 'ORD-20260905-5500', '3', 'pos', '1950.00', '0.00', '0.00', '1950.00', '1950.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-05 22:24:30');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('8', 'ORD-20260905-4109', '4', 'pos', '1600.00', '97.50', '0.00', '1502.50', '1502.50', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-05 22:24:56');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('9', 'ORD-20260907-5888', '3', 'pos', '3600.00', '0.00', '0.00', '3600.00', '3600.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-07 22:15:16');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('10', 'ORD-20260908-3454', '3', 'pos', '15000.00', '0.00', '0.00', '15000.00', '15000.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-08 08:10:02');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('11', 'ORD-20260908-2393', '3', 'pos', '150.00', '0.00', '0.00', '150.00', '150.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-08 08:29:33');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('12', 'ORD-20260908-4846', '4', 'preorder', '1500.00', '375.00', '0.00', '1125.00', '1125.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, '', '1', '2026-09-08 10:20:11');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('13', 'ORD-20260908-7651', '4', 'preorder', '8500.00', '2625.00', '0.00', '5875.00', '2000.00', '0.00', 'cash', NULL, 'partial', 'pending', NULL, '', '1', '2026-09-08 12:59:48');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('14', 'ORD-20260909-7180', '4', 'preorder', '500.00', '1675.00', '0.00', '0.00', '0.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, '', '1', '2026-09-09 17:35:11');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('15', 'ORD-20260909-2952', '4', 'preorder', '4000.00', '600.00', '0.00', '3400.00', '0.00', '0.00', 'cash', NULL, 'unpaid', 'pending', NULL, '', '1', '2026-09-09 20:52:36');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('16', 'ORD-20260909-5067', '5', 'preorder', '7500.00', '850.00', '0.00', '6650.00', '6650.00', '0.00', 'cheque', '123', 'paid', 'completed', NULL, '', '6', '2026-09-09 21:34:31');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('17', 'ORD-20260909-3612', '5', 'preorder', '2200.00', '0.00', '0.00', '2200.00', '2150.00', '0.00', 'cash', NULL, 'partial', 'pending', NULL, '', '6', '2026-09-09 22:32:52');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('18', 'ORD-20260910-1634', '7', 'preorder', '2000.00', '300.00', '0.00', '1700.00', '1000.00', '0.00', 'cash', NULL, 'partial', 'pending', NULL, '', '6', '2026-09-10 10:58:41');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('19', 'ORD-TEST-1986', '1', 'preorder', '3000.00', '0.00', '0.00', '3000.00', '2000.00', '0.00', 'cash', NULL, 'partial', 'pending', NULL, NULL, '1', '2026-09-10 12:18:27');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('20', 'ORD-TEST-6816', '2', 'preorder', '3000.00', '0.00', '0.00', '3000.00', '3000.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-10 12:19:13');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('21', 'ORD-TEST-7059', '4', 'preorder', '3000.00', '0.00', '0.00', '3000.00', '2500.00', '0.00', 'cash', NULL, 'partial', 'pending', NULL, NULL, '1', '2026-09-10 12:19:25');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('22', 'ORD-20260910-7324', '4', 'preorder', '0.00', '0.00', '0.00', '0.00', '600.00', '600.00', 'cash', NULL, 'paid', 'completed', NULL, '', '6', '2026-09-10 12:19:42');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('23', 'ORD-20260910-9505', '3', 'pos', '2278.00', '0.00', '0.00', '2278.00', '2278.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-10 22:43:27');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('24', 'ORD-20260913-4909', '5', 'pos', '1200.00', '120.00', '0.00', '1080.00', '1080.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, NULL, '1', '2026-09-13 22:00:42');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('25', 'ORD-20260914-2927', '6', 'preorder', '280.00', '14.00', '0.00', '266.00', '0.00', '0.00', 'cash', NULL, 'unpaid', 'pending', NULL, '', '1', '2026-09-14 22:49:00');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('26', 'ORD-20260915-5007', '5', 'preorder', '200.00', '30.00', '0.00', '170.00', '0.00', '0.00', 'cash', NULL, 'unpaid', 'pending', NULL, '', '1', '2026-09-15 07:58:17');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('27', 'ORD-20260915-4439', '4', 'preorder', '600.00', '90.00', '0.00', '510.00', '0.00', '0.00', 'cash', NULL, 'unpaid', 'pending', NULL, '', '1', '2026-09-15 07:59:32');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('28', 'ORD-20260915-9116', '4', 'preorder', '150.00', '22.50', '0.00', '127.50', '0.00', '0.00', 'cash', NULL, 'unpaid', 'pending', NULL, '', '1', '2026-09-15 08:19:54');
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount`, `tax`, `total_amount`, `paid_amount`, `change_amount`, `payment_method`, `cheque_ref`, `payment_status`, `order_status`, `delivery_date`, `custom_notes`, `created_by`, `created_at`) VALUES ('29', 'ORD-20260915-8205', '7', 'preorder', '150.00', '15.00', '0.00', '135.00', '135.00', '0.00', 'cash', NULL, 'paid', 'completed', NULL, '', '1', '2026-09-15 08:21:07');

DROP TABLE IF EXISTS `product_stock_logs`;
CREATE TABLE `product_stock_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `log_type` enum('daily_batch','sale_deduction','adjustment','waste') NOT NULL DEFAULT 'daily_batch',
  `quantity` decimal(10,2) NOT NULL,
  `reference_order_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `product_stock_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stock_logs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4;

INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('1', '12', 'daily_batch', '50.00', NULL, 'Daily Morning Baking Stock Entry - 08 Sep 2026', '1', '2026-09-08 08:09:37');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('2', '11', 'daily_batch', '100.00', NULL, 'Daily Morning Baking Stock Entry - 08 Sep 2026', '1', '2026-09-08 08:09:37');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('3', '9', 'daily_batch', '150.00', NULL, 'Daily Morning Baking Stock Entry - 08 Sep 2026', '1', '2026-09-08 08:09:37');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('4', '12', 'sale_deduction', '100.00', '10', 'POS Counter Sale #ORD-20260908-3454', '1', '2026-09-08 08:10:02');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('5', '12', 'sale_deduction', '1.00', '11', 'POS Counter Sale #ORD-20260908-2393', '1', '2026-09-08 08:29:33');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('6', '12', 'daily_batch', '50.00', NULL, 'Daily Morning Baking Stock Entry - 08 Sep 2026', '1', '2026-09-08 10:19:04');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('7', '12', 'sale_deduction', '10.00', '12', 'Pre-Order / Custom Booking #ORD-20260908-4846', '1', '2026-09-08 10:20:11');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('8', '12', 'adjustment', '1.00', '12', 'Customer Over-Order Return restocked on Order #ORD-20260908-4846', '1', '2026-09-08 10:20:11');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('9', '12', 'sale_deduction', '20.00', '13', 'Pre-Order / Custom Booking #ORD-20260908-7651', '1', '2026-09-08 12:59:48');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('10', '11', 'sale_deduction', '50.00', '13', 'Pre-Order / Custom Booking #ORD-20260908-7651', '1', '2026-09-08 12:59:48');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('11', '9', 'sale_deduction', '50.00', '13', 'Pre-Order / Custom Booking #ORD-20260908-7651', '1', '2026-09-08 12:59:48');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('12', '11', 'waste', '15.00', '13', 'Customer Expired Goods Return written off on Order #ORD-20260908-7651', '1', '2026-09-08 12:59:48');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('13', '9', 'waste', '10.00', '13', 'Customer Expired Goods Return written off on Order #ORD-20260908-7651', '1', '2026-09-08 12:59:48');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('14', '13', 'daily_batch', '10.00', NULL, 'Fresh Bake Entry', '1', '2026-09-09 12:20:49');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('15', '13', 'daily_batch', '20.00', NULL, 'Fresh Bake Entry', '1', '2026-09-09 12:22:32');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('16', '11', 'sale_deduction', '10.00', '14', 'Pre-Order / Custom Booking #ORD-20260909-7180', '1', '2026-09-09 17:35:11');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('17', '13', 'adjustment', '8.00', '14', 'Customer Over-Order Return restocked on Order #ORD-20260909-7180', '1', '2026-09-09 17:35:12');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('18', '13', 'sale_deduction', '20.00', '15', 'Pre-Order Booking #ORD-20260909-2952', '1', '2026-09-09 20:52:36');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('19', '8', 'daily_batch', '100.00', NULL, 'Daily Baking Stock Entry - 09 Sep 2026', '1', '2026-09-09 20:55:24');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('20', '13', 'sale_deduction', '10.00', '16', 'Pre-Order Booking #ORD-20260909-5067', '6', '2026-09-09 21:34:31');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('21', '11', 'sale_deduction', '50.00', '16', 'Pre-Order Booking #ORD-20260909-5067', '6', '2026-09-09 21:34:31');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('22', '9', 'sale_deduction', '50.00', '16', 'Pre-Order Booking #ORD-20260909-5067', '6', '2026-09-09 21:34:31');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('23', '13', 'sale_deduction', '5.00', '17', 'Mobile Pre-Order #ORD-20260909-3612', '6', '2026-09-09 22:32:52');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('24', '9', 'sale_deduction', '20.00', '17', 'Mobile Pre-Order #ORD-20260909-3612', '6', '2026-09-09 22:32:52');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('25', '12', 'sale_deduction', '2.00', '18', 'Mobile Pre-Order #ORD-20260910-1634', '6', '2026-09-10 10:58:41');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('26', '11', 'sale_deduction', '10.00', '18', 'Mobile Pre-Order #ORD-20260910-1634', '6', '2026-09-10 10:58:41');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('27', '9', 'sale_deduction', '20.00', '18', 'Mobile Pre-Order #ORD-20260910-1634', '6', '2026-09-10 10:58:41');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('28', '9', 'waste', '5.00', '18', 'Customer Expired Goods written off via mobile app on #ORD-20260910-1634', '6', '2026-09-10 10:58:41');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('29', '5', 'sale_deduction', '10.00', '23', 'POS Counter Sale #ORD-20260910-9505', '1', '2026-09-10 22:43:27');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('30', '12', 'sale_deduction', '15.00', '23', 'POS Counter Sale #ORD-20260910-9505', '1', '2026-09-10 22:43:27');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('31', '9', 'sale_deduction', '20.00', '24', 'POS Counter Sale #ORD-20260913-4909', '1', '2026-09-13 22:00:42');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('32', '11', 'sale_deduction', '5.00', '25', 'Pre-Order Booking #ORD-20260914-2927', '1', '2026-09-14 22:49:00');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('33', '3', 'sale_deduction', '10.00', '25', 'Pre-Order Booking #ORD-20260914-2927', '1', '2026-09-14 22:49:00');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('34', '13', 'sale_deduction', '1.00', '26', 'Pre-Order Booking #ORD-20260915-5007', '1', '2026-09-15 07:58:17');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('35', '9', 'sale_deduction', '10.00', '27', 'Pre-Order Booking #ORD-20260915-4439', '1', '2026-09-15 07:59:32');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('36', '12', 'sale_deduction', '1.00', '28', 'Pre-Order Booking #ORD-20260915-9116', '1', '2026-09-15 08:19:54');
INSERT INTO `product_stock_logs` (`id`, `product_id`, `log_type`, `quantity`, `reference_order_id`, `notes`, `created_by`, `created_at`) VALUES ('37', '12', 'sale_deduction', '1.00', '29', 'Pre-Order Booking #ORD-20260915-8205', '1', '2026-09-15 08:21:07');

DROP TABLE IF EXISTS `production_logs`;
CREATE TABLE `production_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `batch_quantity` int(11) NOT NULL,
  `produced_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `produced_by` (`produced_by`),
  CONSTRAINT `production_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_logs_ibfk_2` FOREIGN KEY (`produced_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `price_retail` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_wholesale` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_stock_alert` decimal(10,2) NOT NULL DEFAULT 10.00,
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `image` varchar(255) DEFAULT 'default_product.jpg',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;

INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('2', '1', 'RED-VEL-01', 'Red Velvet Slice', '4.50', '4.05', 'Classic red velvet with cream cheese frosting', '4.50', '50.00', '10.00', 'slice', 'default_product.jpg', 'active', '2026-09-02 08:33:32');
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('3', '6', 'FRA-BAG-01', 'French Crusty Baguette', '3.00', '2.70', 'Traditional sourdough French baguette', '3.00', '40.00', '10.00', 'pcs', 'default_product.jpg', 'active', '2026-09-02 08:33:32');
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('4', '6', 'WHO-GRA-01', 'Whole Wheat Honey Loaf', '4.20', '3.78', 'Nutritious honey glazed multi-grain bread', '4.20', '50.00', '10.00', 'pcs', 'default_product.jpg', 'active', '2026-09-02 08:33:32');
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('5', '6', 'BUT-CRO-01', 'Flaky Butter Croissant', '2.80', '2.52', 'Golden baked layered butter croissant', '2.80', '40.00', '10.00', 'pcs', 'default_product.jpg', 'active', '2026-09-02 08:33:32');
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('6', '3', 'CHO-DAN-01', 'Chocolate Danish', '3.50', '3.15', 'Flaky pastry filled with rich belgian chocolate', '3.50', '30.00', '10.00', 'pcs', 'default_product.jpg', 'active', '2026-09-02 08:33:32');
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('7', '3', 'CHO-CHI-01', 'Choco Chip Cookie (Pack of 6)', '6.00', '5.40', 'Soft baked giant chocolate chip cookies', '6.00', '50.00', '10.00', 'pack', 'default_product.jpg', 'active', '2026-09-02 08:33:32');
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('8', '5', 'CAP-HOT-01', 'Hot Cappuccino', '3.80', '3.42', 'Double shot espresso with creamy foam', '3.80', '150.00', '10.00', 'cup', 'default_product.jpg', 'active', '2026-09-02 08:33:32');
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('9', '7', 'TB001', 'Tea bun', '60.00', '54.00', 'bun', '60.00', '30.00', '10.00', 'pcs', 'default_product.jpg', 'active', '2026-09-02 08:45:14');
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('11', '7', 'CB001', 'Cream Bun', '50.00', '45.00', '', '50.00', '3.00', '10.00', 'pcs', 'default_product.jpg', 'active', '2026-09-02 08:50:07');
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('12', '3', 'V001', 'Viskiringha Medium', '150.00', '130.00', NULL, '150.00', '2.00', '10.00', 'pack', 'default_product.jpg', 'active', '2026-09-07 09:49:33');
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price_retail`, `price_wholesale`, `description`, `price`, `current_stock`, `min_stock_alert`, `unit`, `image`, `status`, `created_at`) VALUES ('13', '3', 'MLK001', 'Milki Rice', '200.00', '180.00', NULL, '200.00', '0.00', '10.00', 'pcs', 'default_product.jpg', 'active', '2026-09-08 08:35:07');

DROP TABLE IF EXISTS `recipes`;
CREATE TABLE `recipes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `ingredient_id` (`ingredient_id`),
  CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recipes_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `sms_logs`;
CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `phone_number` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `event_type` varchar(50) NOT NULL DEFAULT 'general',
  `status` varchar(30) NOT NULL DEFAULT 'sent',
  `response_payload` text DEFAULT NULL,
  `sent_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_cust` (`customer_id`),
  KEY `idx_phone` (`phone_number`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;

INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('1', NULL, NULL, '94771122334', 'Test SMS from automated test suite', 'test_sms', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94771122334\"}', NULL, '2026-09-10 12:18:27');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('2', NULL, NULL, '94771122334', 'Test SMS from automated test suite', 'test_sms', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94771122334\"}', NULL, '2026-09-10 12:19:13');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('3', '20', '2', '+1 555-0192', 'Dear Mr. Emily Watson, your Pre-Order #ORD-TEST-6816 at MLB Bakery is confirmed! Total: Rs. 3,000.00, Paid: Rs. 1,000.00, Due: Rs. 2,000.00. Delivery: As scheduled. Thank you!', 'preorder_created', 'failed', 'Invalid or missing phone number.', '1', '2026-09-10 12:19:13');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('4', '20', '2', '+1 555-0192', 'Dear Mr. Emily Watson, payment of Rs. 1,000.00 received for Order #ORD-TEST-6816 via Cash. Remaining Balance: Rs. 1,000.00. Thank you for choosing MLB Bakery!', 'payment_received', 'failed', 'Invalid or missing phone number.', '1', '2026-09-10 12:19:13');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('5', NULL, NULL, '94771122334', 'Test SMS from automated test suite', 'test_sms', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94771122334\"}', NULL, '2026-09-10 12:19:25');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('6', '21', '4', '94711234567', 'Dear Mr. Nanda Storse, your Pre-Order #ORD-TEST-7059 at MLB Bakery is confirmed! Total: Rs. 3,000.00, Paid: Rs. 1,000.00, Due: Rs. 2,000.00. Delivery: As scheduled. Thank you!', 'preorder_created', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94711234567\"}', '1', '2026-09-10 12:19:25');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('7', '21', '4', '94711234567', 'Dear Mr. Nanda Storse, payment of Rs. 1,000.00 received for Order #ORD-TEST-7059 via Cash. Remaining Balance: Rs. 1,000.00. Thank you for choosing MLB Bakery!', 'payment_received', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94711234567\"}', '1', '2026-09-10 12:19:25');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('8', '22', '4', '94711234567', 'Dear Mr. Nanda Storse, your Pre-Order #ORD-20260910-7324 at MLB Bakery is confirmed! Total: Rs. 0.00, Paid: Rs. 200.00, Due: Rs. 0.00. Delivery: As scheduled. Thank you!', 'preorder_created', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94711234567\"}', '6', '2026-09-10 12:19:42');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('9', '22', '4', '94711234567', 'Dear Mr. Nanda Storse, payment of Rs. 400.00 received for Order #ORD-20260910-7324 via Cash. Remaining Balance: Rs. 0.00. Thank you for choosing MLB Bakery!', 'payment_received', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94711234567\"}', '6', '2026-09-10 12:19:42');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('10', '21', '4', '94711234567', 'Dear Mr. Nanda Storse, payment of Rs. 500.00 received for Order #ORD-TEST-7059 via Cash. Remaining Balance: Rs. 500.00. Thank you for choosing MLB Bakery!', 'payment_received', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94711234567\"}', '1', '2026-09-13 22:01:26');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('11', '20', '2', '+1 555-0192', 'Dear Mr. Emily Watson, payment of Rs. 1,000.00 received for Order #ORD-TEST-6816 via Cash. Remaining Balance: Rs. 0.00. Thank you for choosing MLB Bakery!', 'payment_received', 'failed', 'Invalid or missing phone number.', '1', '2026-09-14 22:18:19');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('12', '25', '6', '94779951274', 'Dear Mr. Test Mobile Customer, your Pre-Order #ORD-20260914-2927 at MLB Bakery is confirmed! Total: Rs. 266.00, Paid: Rs. 0.00, Due: Rs. 266.00. Delivery: As scheduled. Thank you!', 'preorder_created', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94779951274\"}', '1', '2026-09-14 22:49:00');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('13', '26', '5', '94710475200', 'Dear Mr. Nadeeshan Thilina, your Pre-Order #ORD-20260915-5007 at MLB Bakery is confirmed! Total: Rs. 170.00, Paid: Rs. 0.00, Due: Rs. 170.00. Delivery: As scheduled. Thank you!', 'preorder_created', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94710475200\"}', '1', '2026-09-15 07:58:17');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('14', '27', '4', '94711234567', 'Dear Mr. Nanda Storse, your Pre-Order #ORD-20260915-4439 at MLB Bakery is confirmed! Total: Rs. 510.00, Paid: Rs. 0.00, Due: Rs. 510.00. Delivery: As scheduled. Thank you!', 'preorder_created', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94711234567\"}', '1', '2026-09-15 07:59:32');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('15', '28', '4', '94711234567', 'Dear Mr. Nanda Storse, your Pre-Order #ORD-20260915-9116 at MLB Bakery is confirmed! Total: Rs. 127.50, Paid: Rs. 0.00, Due: Rs. 127.50. Delivery: As scheduled. Thank you!', 'preorder_created', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94711234567\"}', '1', '2026-09-15 08:19:54');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('16', '29', '7', '94712587412', 'Dear Mr. Sanath, your Pre-Order #ORD-20260915-8205 at MLB Bakery is confirmed! Total: Rs. 135.00, Paid: Rs. 0.00, Due: Rs. 135.00. Delivery: As scheduled. Thank you!', 'preorder_created', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94712587412\"}', '1', '2026-09-15 08:21:07');
INSERT INTO `sms_logs` (`id`, `order_id`, `customer_id`, `phone_number`, `message`, `event_type`, `status`, `response_payload`, `sent_by`, `created_at`) VALUES ('17', '29', '7', '94712587412', 'Dear Mr. Sanath, payment of Rs. 135.00 received for Order #ORD-20260915-8205 via Cash. Remaining Balance: Rs. 0.00. Thank you for choosing MLB Bakery!', 'payment_received', 'mock_sent', '{\"status\":\"success\",\"mock\":true,\"info\":\"Mock SMS delivered successfully in test mode\",\"provider\":\"mock_local\",\"recipient\":\"94712587412\"}', '1', '2026-09-15 08:22:52');

DROP TABLE IF EXISTS `sms_settings`;
CREATE TABLE `sms_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gateway_provider` varchar(50) NOT NULL DEFAULT 'mock_local',
  `api_url` varchar(255) NOT NULL DEFAULT 'https://app.notify.lk/api/v1/send',
  `api_key` varchar(255) DEFAULT '',
  `user_id` varchar(100) DEFAULT '',
  `sender_id` varchar(50) NOT NULL DEFAULT 'MLB-BAKERY',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `send_on_preorder` tinyint(1) NOT NULL DEFAULT 1,
  `send_on_payment` tinyint(1) NOT NULL DEFAULT 1,
  `preorder_template` text NOT NULL,
  `payment_template` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO `sms_settings` (`id`, `gateway_provider`, `api_url`, `api_key`, `user_id`, `sender_id`, `is_enabled`, `send_on_preorder`, `send_on_payment`, `preorder_template`, `payment_template`, `created_at`, `updated_at`) VALUES ('1', 'mock_local', 'https://app.notify.lk/api/v1/send', '', '', 'MLB-BAKERY', '1', '1', '1', 'Dear {customer_name}, your Pre-Order #{order_number} at MLB Bakery is confirmed! Total: Rs. {total_amount}, Paid: Rs. {paid_amount}, Due: Rs. {balance_due}. Delivery: {delivery_date}. Thank you!', 'Dear {customer_name}, payment of Rs. {paid_amount} received for Order #{order_number} via {payment_method}. Remaining Balance: Rs. {balance_due}. Thank you for choosing MLB Bakery!', '2026-09-10 12:15:36', '2026-09-10 12:15:36');

DROP TABLE IF EXISTS `staff`;
CREATE TABLE `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_number` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `designation` varchar(100) NOT NULL DEFAULT 'Staff',
  `phone` varchar(30) DEFAULT NULL,
  `nic` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `daily_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ot_rate_per_hour` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fixed_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `epf_no` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_no` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_number` (`emp_number`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

INSERT INTO `staff` (`id`, `emp_number`, `name`, `designation`, `phone`, `nic`, `address`, `basic_salary`, `daily_rate`, `ot_rate_per_hour`, `fixed_allowance`, `epf_no`, `bank_name`, `account_no`, `status`, `created_at`) VALUES ('1', 'EMP-001', 'Kamal Perera', 'Head Baker', '0771234567', '901234567V', NULL, '65000.00', '2500.00', '350.00', '0.00', 'EPF-8841', NULL, NULL, 'active', '2026-09-08 11:05:51');
INSERT INTO `staff` (`id`, `emp_number`, `name`, `designation`, `phone`, `nic`, `address`, `basic_salary`, `daily_rate`, `ot_rate_per_hour`, `fixed_allowance`, `epf_no`, `bank_name`, `account_no`, `status`, `created_at`) VALUES ('2', 'EMP-002', 'Nimali Silva', 'Sales Assistant', '0719876543', '955432100V', NULL, '45000.00', '1800.00', '250.00', '0.00', 'EPF-9932', NULL, NULL, 'active', '2026-09-08 11:05:51');
INSERT INTO `staff` (`id`, `emp_number`, `name`, `designation`, `phone`, `nic`, `address`, `basic_salary`, `daily_rate`, `ot_rate_per_hour`, `fixed_allowance`, `epf_no`, `bank_name`, `account_no`, `status`, `created_at`) VALUES ('3', 'EMP-003', 'Sunil Fernando', 'Helper / Driver', '0754433221', '883322110V', NULL, '40000.00', '1600.00', '220.00', '0.00', 'EPF-7710', NULL, NULL, 'active', '2026-09-08 11:05:51');
INSERT INTO `staff` (`id`, `emp_number`, `name`, `designation`, `phone`, `nic`, `address`, `basic_salary`, `daily_rate`, `ot_rate_per_hour`, `fixed_allowance`, `epf_no`, `bank_name`, `account_no`, `status`, `created_at`) VALUES ('4', 'EMP-004', 'Nadeeshan Thilina', 'Baker', '0710475214', '', 'badulla', '50000.00', '0.00', '120.00', '5000.00', '', '', '', 'active', '2026-09-08 12:50:10');

DROP TABLE IF EXISTS `staff_advances`;
CREATE TABLE `staff_advances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `advance_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `advance_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `staff_advances_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_advances_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

INSERT INTO `staff_advances` (`id`, `staff_id`, `advance_amount`, `advance_date`, `notes`, `created_by`, `created_at`) VALUES ('2', '4', '25000.00', '2026-09-13', '', '1', '2026-09-13 22:04:03');

DROP TABLE IF EXISTS `staff_attendance`;
CREATE TABLE `staff_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','half_day','leave') NOT NULL DEFAULT 'present',
  `ot_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_date_unique` (`staff_id`,`attendance_date`),
  CONSTRAINT `staff_attendance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `staff_food_consumption`;
CREATE TABLE `staff_food_consumption` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_no` varchar(50) DEFAULT NULL,
  `staff_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `consumption_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `product_id` (`product_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_bill_no` (`bill_no`),
  CONSTRAINT `staff_food_consumption_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_food_consumption_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_food_consumption_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;

INSERT INTO `staff_food_consumption` (`id`, `bill_no`, `staff_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `consumption_date`, `notes`, `created_by`, `created_at`) VALUES ('2', 'SFC-00002', '1', '11', 'Cream Bun', '1.00', '50.00', '50.00', '2026-09-08', '', '1', '2026-09-08 11:37:21');
INSERT INTO `staff_food_consumption` (`id`, `bill_no`, `staff_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `consumption_date`, `notes`, `created_by`, `created_at`) VALUES ('3', 'SFC-00003', '4', '11', 'Cream Bun', '10.00', '50.00', '500.00', '2026-09-13', '', '1', '2026-09-13 22:05:29');
INSERT INTO `staff_food_consumption` (`id`, `bill_no`, `staff_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `consumption_date`, `notes`, `created_by`, `created_at`) VALUES ('4', 'SFC-00004', '4', '13', 'Milki Rice', '2.00', '200.00', '400.00', '2026-09-14', '', '1', '2026-09-14 21:03:32');
INSERT INTO `staff_food_consumption` (`id`, `bill_no`, `staff_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `consumption_date`, `notes`, `created_by`, `created_at`) VALUES ('5', 'SFC-00005', '1', '11', 'Cream Bun', '5.00', '50.00', '250.00', '2026-09-14', '', '1', '2026-09-14 21:05:50');
INSERT INTO `staff_food_consumption` (`id`, `bill_no`, `staff_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `consumption_date`, `notes`, `created_by`, `created_at`) VALUES ('7', 'SFC-20260914-001', '4', '11', 'Cream Bun', '2.00', '50.00', '100.00', '2026-09-14', '', '1', '2026-09-14 21:27:22');
INSERT INTO `staff_food_consumption` (`id`, `bill_no`, `staff_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `consumption_date`, `notes`, `created_by`, `created_at`) VALUES ('8', 'SFC-20260914-002', '3', '11', 'Cream Bun', '2.00', '50.00', '100.00', '2026-09-14', '', '1', '2026-09-14 21:37:40');
INSERT INTO `staff_food_consumption` (`id`, `bill_no`, `staff_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `consumption_date`, `notes`, `created_by`, `created_at`) VALUES ('9', 'SFC-20260914-002', '3', '6', 'Chocolate Danish', '10.00', '3.50', '35.00', '2026-09-14', '', '1', '2026-09-14 21:37:40');
INSERT INTO `staff_food_consumption` (`id`, `bill_no`, `staff_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `consumption_date`, `notes`, `created_by`, `created_at`) VALUES ('10', 'SFC-20260914-003', '2', '11', 'Cream Bun', '2.00', '50.00', '100.00', '2026-09-14', '', '1', '2026-09-14 21:38:32');
INSERT INTO `staff_food_consumption` (`id`, `bill_no`, `staff_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `consumption_date`, `notes`, `created_by`, `created_at`) VALUES ('11', 'SFC-20260914-003', '2', '6', 'Chocolate Danish', '10.00', '3.50', '35.00', '2026-09-14', '', '1', '2026-09-14 21:38:32');

DROP TABLE IF EXISTS `staff_salaries`;
CREATE TABLE `staff_salaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `payroll_month` varchar(7) NOT NULL,
  `total_working_days` int(11) NOT NULL DEFAULT 30,
  `present_days` decimal(5,2) NOT NULL DEFAULT 0.00,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `earned_basic` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_ot_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `ot_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `advance_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `food_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `epf_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `paid_date` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_month_unique` (`staff_id`,`payroll_month`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `staff_salaries_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_salaries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4;

INSERT INTO `staff_salaries` (`id`, `staff_id`, `payroll_month`, `total_working_days`, `present_days`, `basic_salary`, `earned_basic`, `total_ot_hours`, `ot_pay`, `allowances`, `deductions`, `advance_deduction`, `food_deduction`, `epf_deduction`, `net_salary`, `payment_status`, `paid_date`, `created_by`, `created_at`) VALUES ('1', '1', '2026-09', '30', '30.00', '65000.00', '75000.00', '0.00', '0.00', '0.00', '0.00', '0.00', '50.00', '0.00', '74950.00', 'paid', '2026-09-14 21:02:00', '1', '2026-09-08 11:18:24');
INSERT INTO `staff_salaries` (`id`, `staff_id`, `payroll_month`, `total_working_days`, `present_days`, `basic_salary`, `earned_basic`, `total_ot_hours`, `ot_pay`, `allowances`, `deductions`, `advance_deduction`, `food_deduction`, `epf_deduction`, `net_salary`, `payment_status`, `paid_date`, `created_by`, `created_at`) VALUES ('2', '2', '2026-09', '30', '30.00', '45000.00', '54000.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '54000.00', 'paid', '2026-09-14 21:02:00', '1', '2026-09-08 11:18:24');
INSERT INTO `staff_salaries` (`id`, `staff_id`, `payroll_month`, `total_working_days`, `present_days`, `basic_salary`, `earned_basic`, `total_ot_hours`, `ot_pay`, `allowances`, `deductions`, `advance_deduction`, `food_deduction`, `epf_deduction`, `net_salary`, `payment_status`, `paid_date`, `created_by`, `created_at`) VALUES ('3', '3', '2026-09', '30', '30.00', '40000.00', '48000.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '48000.00', 'paid', '2026-09-14 21:02:00', '1', '2026-09-08 11:18:24');
INSERT INTO `staff_salaries` (`id`, `staff_id`, `payroll_month`, `total_working_days`, `present_days`, `basic_salary`, `earned_basic`, `total_ot_hours`, `ot_pay`, `allowances`, `deductions`, `advance_deduction`, `food_deduction`, `epf_deduction`, `net_salary`, `payment_status`, `paid_date`, `created_by`, `created_at`) VALUES ('16', '4', '2026-09', '30', '30.00', '50000.00', '50000.00', '0.00', '0.00', '5000.00', '0.00', '25000.00', '500.00', '4000.00', '25500.00', 'paid', '2026-09-14 21:02:00', '1', '2026-09-08 12:50:52');
INSERT INTO `staff_salaries` (`id`, `staff_id`, `payroll_month`, `total_working_days`, `present_days`, `basic_salary`, `earned_basic`, `total_ot_hours`, `ot_pay`, `allowances`, `deductions`, `advance_deduction`, `food_deduction`, `epf_deduction`, `net_salary`, `payment_status`, `paid_date`, `created_by`, `created_at`) VALUES ('17', '1', '2026-10', '30', '30.00', '65000.00', '75000.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '6000.00', '69000.00', 'paid', '2026-09-08 12:58:09', '1', '2026-09-08 12:58:09');
INSERT INTO `staff_salaries` (`id`, `staff_id`, `payroll_month`, `total_working_days`, `present_days`, `basic_salary`, `earned_basic`, `total_ot_hours`, `ot_pay`, `allowances`, `deductions`, `advance_deduction`, `food_deduction`, `epf_deduction`, `net_salary`, `payment_status`, `paid_date`, `created_by`, `created_at`) VALUES ('18', '2', '2026-10', '30', '30.00', '45000.00', '54000.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '4320.00', '49680.00', 'paid', '2026-09-08 12:58:09', '1', '2026-09-08 12:58:09');
INSERT INTO `staff_salaries` (`id`, `staff_id`, `payroll_month`, `total_working_days`, `present_days`, `basic_salary`, `earned_basic`, `total_ot_hours`, `ot_pay`, `allowances`, `deductions`, `advance_deduction`, `food_deduction`, `epf_deduction`, `net_salary`, `payment_status`, `paid_date`, `created_by`, `created_at`) VALUES ('19', '3', '2026-10', '30', '30.00', '40000.00', '48000.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '3840.00', '44160.00', 'paid', '2026-09-08 12:58:09', '1', '2026-09-08 12:58:09');
INSERT INTO `staff_salaries` (`id`, `staff_id`, `payroll_month`, `total_working_days`, `present_days`, `basic_salary`, `earned_basic`, `total_ot_hours`, `ot_pay`, `allowances`, `deductions`, `advance_deduction`, `food_deduction`, `epf_deduction`, `net_salary`, `payment_status`, `paid_date`, `created_by`, `created_at`) VALUES ('20', '4', '2026-10', '30', '30.00', '50000.00', '50000.00', '0.00', '0.00', '5000.00', '0.00', '0.00', '0.00', '4000.00', '51000.00', 'paid', '2026-09-08 12:58:09', '1', '2026-09-08 12:58:09');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','owner','sales_person','pos_operator') NOT NULL DEFAULT 'pos_operator',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `created_at`) VALUES ('1', 'admin', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'Manager Admin', 'admin@bakery.com', 'admin', 'active', '2026-09-02 08:33:32');
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `created_at`) VALUES ('2', 'cashier', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'John Sales', 'cashier@bakery.com', 'pos_operator', 'active', '2026-09-02 08:33:32');
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `created_at`) VALUES ('4', 'owner', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'Business Owner', 'owner@bakery.com', 'owner', 'active', '2026-09-08 10:51:14');
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `created_at`) VALUES ('6', 'sales', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'John Sales', 'sales@bakery.com', 'sales_person', 'active', '2026-09-09 21:05:33');
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `created_at`) VALUES ('7', 'sales_person', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'Sales Person User', 'salesperson@bakery.com', 'sales_person', 'active', '2026-09-09 21:05:33');
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `created_at`) VALUES ('8', 'pos', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'Sarah POS Operator', 'pos@bakery.com', 'pos_operator', 'active', '2026-09-09 21:05:33');
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `created_at`) VALUES ('9', 'pos_operator', '$2y$10$ygueWY.RMxvD6uPE6WemO.IyGdpBX7NGs67TorBnZHfmkILvTLmMa', 'POS Operator User', 'posoperator@bakery.com', 'pos_operator', 'active', '2026-09-09 21:05:33');

SET FOREIGN_KEY_CHECKS = 1;
