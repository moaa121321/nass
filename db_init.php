<?php
// Run this script once to create the `users` table.
// Usage: from project root run `php db_init.php` (ensure DATABASE_URL env var set if needed)

/** This script will create the users table if it does not exist. **/
require __DIR__ . '/config.php';
$pdo = require __DIR__ . '/config.php';

$sql = "
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  address VARCHAR(255) DEFAULT NULL,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sql);
    echo "users tablosu oluşturuldu veya zaten mevcut.\n";
} catch (Exception $e) {
    echo "Tablo oluşturulurken hata: " . $e->getMessage() . "\n";
}

// Create orders table
$sqlOrders = "
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  product_id VARCHAR(255),
  features TEXT,
  total_price DECIMAL(10,2),
  contact_type ENUM('telegram', 'discord', 'whatsapp'),
  contact_value VARCHAR(255),
  status ENUM('pending', 'preparing', 'successful', 'declined', 'cancelled', 'paused') DEFAULT 'pending',
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sqlOrders);
    echo "orders tablosu oluşturuldu veya zaten mevcut.\n";
} catch (Exception $e) {
    echo "Orders tablo oluşturulurken hata: " . $e->getMessage() . "\n";
}

// Create chat table
$sqlChat = "
CREATE TABLE IF NOT EXISTS chat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_username VARCHAR(100) NOT NULL,
  receiver_username VARCHAR(100) NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sender_receiver (sender_username, receiver_username),
  INDEX idx_receiver (receiver_username)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sqlChat);
    echo "chat tablosu oluşturuldu veya zaten mevcut.\n";
} catch (Exception $e) {
    echo "Chat tablo oluşturulurken hata: " . $e->getMessage() . "\n";
}
