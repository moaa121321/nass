<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
// Only admins care about order counts
if ($user !== 'admin') {
    echo json_encode(['orders' => 0]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();
    echo json_encode(['orders' => $count]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
