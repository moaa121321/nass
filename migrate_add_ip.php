<?php
// Migration: add ip_address column to users table if it doesn't exist
// Usage: php migrate_add_ip.php
try {
    $pdo = require __DIR__ . '/config.php';
    $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
    if (!$dbName) {
        throw new Exception('Could not determine database name from connection.');
    }

    $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'ip_address'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dbName]);
    $exists = (int)$stmt->fetchColumn();

    if ($exists) {
        echo "Column 'ip_address' already exists in `users` table.\n";
        exit(0);
    }

    // Add the column (VARCHAR(45) to accommodate IPv6)
    $alter = "ALTER TABLE users ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL";
    $pdo->exec($alter);
    echo "Added column 'ip_address' to `users` table.\n";
} catch (Exception $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
