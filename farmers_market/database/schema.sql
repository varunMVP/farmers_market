-- Database: farmer_direct_market

-- Table structure for users
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('farmer', 'customer') NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  address TEXT,
  registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for product categories
CREATE TABLE product_categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  description TEXT
);

-- Insert default product categories
INSERT INTO product_categories (name, description) VALUES
('Fruits', 'Fresh fruits from farms'),
('Vegetables', 'Fresh vegetables from farms'),
('Grains', 'Various grains and cereals'),
('Dairy', 'Dairy products from local farms');

-- Table structure for products
CREATE TABLE products (
  product_id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  category_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  quantity DECIMAL(10,2) NOT NULL,
  unit VARCHAR(20) NOT NULL, -- kg, dozen, etc.
  price DECIMAL(10,2) NOT NULL,
  status ENUM('available', 'sold_out') DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES product_categories(category_id)
);

-- Table structure for orders
CREATE TABLE orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
  total_amount DECIMAL(10,2) NOT NULL,
  payment_status ENUM('pending', 'paid') DEFAULT 'pending',
  delivery_address TEXT,
  FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table structure for order items
CREATE TABLE order_items (
  order_item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Table structure for preorders
CREATE TABLE preorders (
  preorder_id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  farmer_id INT NOT NULL,
  product_category_id INT NOT NULL,
  description TEXT NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  unit VARCHAR(20) NOT NULL,
  status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
  request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (farmer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (product_category_id) REFERENCES product_categories(category_id)
);

-- Table structure for feedback
CREATE TABLE feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  farmer_id INT NOT NULL,
  product_id INT,
  rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  feedback_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (farmer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
);