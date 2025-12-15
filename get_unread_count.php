<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
if (!$user) {
    echo json_encode(['unread' => 0]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat WHERE receiver_username = ? AND is_read = FALSE");
    $stmt->execute([$user]);
    $count = (int)$stmt->fetchColumn();
    echo json_encode(['unread' => $count]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
