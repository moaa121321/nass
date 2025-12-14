<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must log in.']);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token mismatch']);
    exit;
}

$sender = $_SESSION['user'];
$receiver = $_POST['receiver'] ?? 'admin'; // Default to admin
$message = trim($_POST['message'] ?? '');

if (!$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO chat (sender_username, receiver_username, message) VALUES (?, ?, ?)");
    $stmt->execute([$sender, $receiver, $message]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message: ' . $e->getMessage()]);
}
?>