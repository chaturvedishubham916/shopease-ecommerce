-- ShopEase Database
-- Import via phpMyAdmin (Import tab) or: mysql -u root < shopease.sql

CREATE DATABASE IF NOT EXISTS shopease CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shopease;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reviews, payments, order_items, orders, wishlist, cart, products, categories, users, admin;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admin (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(15),
  address TEXT,
  city VARCHAR(50),
  pincode VARCHAR(10),
  status TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE products (
  product_id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  product_name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  image VARCHAR(255) DEFAULT 'no-image.png',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart (
  cart_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wishlist (
  wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wish (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  shipping_name VARCHAR(100) NOT NULL,
  shipping_phone VARCHAR(15) NOT NULL,
  shipping_address TEXT NOT NULL,
  order_status ENUM('Pending','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
  item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  payment_method ENUM('COD','Online') NOT NULL,
  payment_status ENUM('Pending','Paid','Failed') DEFAULT 'Pending',
  transaction_id VARCHAR(60),
  paid_at TIMESTAMP NULL,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reviews (
  review_id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Sample data ----------
-- Default admin: username = admin, password = admin123 (change after first login)
INSERT INTO admin (username, password) VALUES ('admin', '$2y$10$/gRpkVpjpSK7TVZIesyb4.KG2LQjEeQ1v7NDf7EwEnkXkWZtJ2YjS');

INSERT INTO categories (category_name, description) VALUES
('Electronics','Mobiles, headphones and gadgets'),
('Fashion','Clothing and accessories'),
('Books','Academic and general books'),
('Home & Kitchen','Home essentials and kitchenware');

INSERT INTO products (category_id, product_name, description, price, stock) VALUES
(1,'Wireless Bluetooth Headphones','Over-ear headphones with 20-hour battery life.',1499.00,25),
(1,'Smart Watch','Fitness tracking, heart-rate monitor and notifications.',2999.00,15),
(1,'Power Bank 10000mAh','Fast-charging portable power bank.',899.00,40),
(2,'Men''s Cotton T-Shirt','Soft, breathable everyday t-shirt.',399.00,60),
(2,'Women''s Handbag','Stylish handbag with multiple compartments.',1199.00,20),
(3,'Learning PHP, MySQL & JavaScript','Beginner-friendly guide to web development.',699.00,30),
(3,'Data Structures Using C','Textbook for BCA and B.Tech students.',450.00,35),
(4,'Stainless Steel Water Bottle','1 litre insulated bottle.',549.00,50),
(4,'Non-Stick Cookware Set','3-piece non-stick cookware set.',1899.00,12);
