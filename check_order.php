<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['hasOrder' => false]);
    exit;
}

$user = $_SESSION['user'];
$productId = $_POST['productId'] ?? '';

if (!$productId) {
    echo json_encode(['hasOrder' => false]);
    exit;
}

$userId = $pdo->query("SELECT id FROM users WHERE username = '$user'")->fetch()['id'];
$count = $pdo->query("SELECT COUNT(*) FROM orders WHERE user_id = $userId AND product_id = '$productId'")->fetchColumn();

echo json_encode(['hasOrder' => $count > 0]);
?>