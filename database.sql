-- Database: sparepart_db
CREATE DATABASE IF NOT EXISTS sparepart_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sparepart_db;

CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(100) UNIQUE NOT NULL,
  password   VARCHAR(255) NOT NULL,
  phone      VARCHAR(20),
  address    TEXT,
  role       ENUM('user','admin') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE products (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  name        VARCHAR(200) NOT NULL,
  description TEXT,
  price       DECIMAL(10,2) NOT NULL,
  stock       INT DEFAULT 0,
  image       VARCHAR(255),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE cart (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  product_id INT NOT NULL,
  quantity   INT DEFAULT 1,
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE orders (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  user_id           INT NOT NULL,
  total_price       DECIMAL(10,2) NOT NULL,
  status            ENUM('pending','paid','processing','shipped','completed','cancelled') DEFAULT 'pending',
  snap_token        VARCHAR(255),
  midtrans_order_id VARCHAR(100),
  shipping_address  TEXT,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  product_id INT NOT NULL,
  quantity   INT NOT NULL,
  price      DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Seed: admin account (password: admin123)
INSERT INTO users (name, email, password, role)
VALUES ('Administrator', 'admin@sparepart.com', '$2y$12$BlcduZ1FNd268pmzf1resOo9Nw.a2.W.Q8SAhV1k.cHDmsWODgzli', 'admin');

-- Seed: categories
INSERT INTO categories (name) VALUES ('Oli & Pelumas'), ('Filter'), ('Rem'), ('Kelistrikan'), ('Body & Aksesoris');

-- Seed: sample products
INSERT INTO products (category_id, name, description, price, stock) VALUES
(1, 'Oli Mesin Shell Helix 1L', 'Oli mesin premium untuk semua jenis kendaraan', 85000, 50),
(2, 'Filter Udara Universal', 'Filter udara berkualitas tinggi, cocok untuk berbagai motor', 45000, 30),
(3, 'Kampas Rem Depan Honda Beat', 'Kampas rem original aftermarket berkualitas', 35000, 40),
(4, 'Aki Motor GS Astra 5Ah', 'Aki maintenance free, tahan lama', 250000, 20),
(5, 'Cover Stang Motor Universal', 'Cover stang bahan kulit sintetis anti panas', 55000, 25);
