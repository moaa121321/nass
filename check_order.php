<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['hasOrder' => false]);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['hasOrder' => false]);
    exit;
}

$user = $_SESSION['user'];
$productId = $_POST['productId'] ?? '';

if (!$productId) {
    echo json_encode(['hasOrder' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$user]);
$userRow = $stmt->fetch();
if (!$userRow) {
    echo json_encode(['hasOrder' => false]);
    exit;
}
$userId = $userRow['id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND product_id = ?");
$stmt->execute([$userId, $productId]);
$count = $stmt->fetchColumn();

echo json_encode(['hasOrder' => $count > 0]);
?>